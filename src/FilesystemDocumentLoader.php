<?php

declare(strict_types=1);

namespace CommonPHP\Docs;

use CommonPHP\Docs\Contracts\DocumentLoaderInterface;
use CommonPHP\Docs\Exceptions\DocumentNotFoundException;
use CommonPHP\Docs\Exceptions\DocumentParseException;
use CommonPHP\Docs\Exceptions\InvalidDocumentPathException;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class FilesystemDocumentLoader implements DocumentLoaderInterface
{
    /**
     * @var list<array{root: string, prefix: string}>
     */
    private array $roots = [];

    /**
     * @param string|iterable<string|int, string>|null $roots
     */
    public function __construct(string|iterable|null $roots = null)
    {
        if ($roots === null) {
            return;
        }

        if (is_string($roots)) {
            $this->addRoot($roots);

            return;
        }

        foreach ($roots as $prefix => $root) {
            $this->addRoot($root, is_string($prefix) ? $prefix : '');
        }
    }

    public static function fromRoot(string $root, string $prefix = ''): self
    {
        return new self([$prefix => $root]);
    }

    public function addRoot(string $root, string $prefix = ''): self
    {
        $this->roots[] = [
            'root' => $this->normalizeRoot($root),
            'prefix' => self::normalizeDocumentPath($prefix, true),
        ];

        return $this;
    }

    public function load(string $path = ''): DocumentPage
    {
        $path = self::normalizeDocumentPath($path, true);

        foreach ($this->roots as $entry) {
            $relativePath = $this->relativePathForRoot($path, $entry['prefix']);

            if ($relativePath === null) {
                continue;
            }

            foreach ($this->candidateFiles($relativePath) as $candidatePath) {
                $candidate = $this->resolveCandidate($entry['root'], $candidatePath);
                $realPath = realpath($candidate);

                if ($realPath === false || !is_file($realPath)) {
                    continue;
                }

                $this->assertWithinRoot($realPath, $entry['root']);

                if (!is_readable($realPath)) {
                    throw DocumentParseException::forPath($path, 'file is not readable');
                }

                $markdown = @file_get_contents($realPath);

                if (!is_string($markdown)) {
                    throw DocumentParseException::forPath($path, 'file could not be read');
                }

                $modifiedAt = filemtime($realPath);
                $documentPath = $this->joinPaths($entry['prefix'], $this->documentPathFromFile($candidatePath));

                return DocumentPage::fromMarkdown(
                    $documentPath,
                    $markdown,
                    $realPath,
                    $modifiedAt === false ? null : $modifiedAt,
                );
            }
        }

        throw DocumentNotFoundException::forPath($path);
    }

    public function exists(string $path = ''): bool
    {
        try {
            $this->load($path);

            return true;
        } catch (DocumentNotFoundException | InvalidDocumentPathException | DocumentParseException) {
            return false;
        }
    }

    /**
     * @return list<DocumentPage>
     */
    public function all(): array
    {
        $pages = [];

        foreach ($this->paths() as $path) {
            try {
                $pages[] = $this->load($path);
            } catch (DocumentNotFoundException | DocumentParseException | InvalidDocumentPathException) {
                continue;
            }
        }

        usort($pages, static fn (DocumentPage $left, DocumentPage $right): int => [
            $left->order(),
            $left->path(),
        ] <=> [
            $right->order(),
            $right->path(),
        ]);

        return $pages;
    }

    /**
     * @return list<string>
     */
    public function paths(): array
    {
        $paths = [];

        foreach ($this->roots as $entry) {
            foreach ($this->markdownFiles($entry['root']) as $file) {
                $realPath = $file->getRealPath();

                if (!is_string($realPath) || !is_file($realPath)) {
                    continue;
                }

                $this->assertWithinRoot($realPath, $entry['root']);
                $relative = $this->relativeFilePath($entry['root'], $realPath);
                $path = $this->joinPaths($entry['prefix'], $this->documentPathFromFile($relative));
                $paths[$path] = $path;
            }
        }

        $paths = array_values($paths);
        sort($paths);

        return $paths;
    }

    /**
     * @return list<array{root: string, prefix: string}>
     */
    public function roots(): array
    {
        return $this->roots;
    }

    public static function normalizeDocumentPath(string $path, bool $allowEmpty = false): string
    {
        $normalized = self::normalizePath($path, true);

        if ($normalized !== '') {
            $segments = explode('/', $normalized);
            $last = array_pop($segments);

            if (is_string($last)) {
                $last = preg_replace('/\.md$/i', '', $last) ?? $last;
                $segments[] = $last;
            }

            while ($segments !== [] && in_array(strtolower((string) end($segments)), ['index', 'readme'], true)) {
                array_pop($segments);
            }

            $normalized = implode('/', $segments);
        }

        if ($normalized === '' && !$allowEmpty) {
            throw InvalidDocumentPathException::forPath($path, 'path cannot be empty');
        }

        return $normalized;
    }

    public static function normalizePath(string $path, bool $allowEmpty = false): string
    {
        $original = $path;

        if (str_contains($original, "\0")) {
            throw InvalidDocumentPathException::forPath($original, 'null bytes are not allowed');
        }

        $path = trim($path);

        if (self::isExternalUrl($path) || preg_match('/^[A-Za-z][A-Za-z0-9+.-]*:/', $path) === 1) {
            throw InvalidDocumentPathException::forPath($original, 'URLs and schemes are not valid document paths');
        }

        $parsedPath = parse_url($path, PHP_URL_PATH);

        if (is_string($parsedPath)) {
            $path = $parsedPath;
        }

        $path = rawurldecode(str_replace('\\', '/', $path));
        $segments = [];

        foreach (explode('/', trim($path, '/')) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                throw InvalidDocumentPathException::forPath($original, 'path traversal is not allowed');
            }

            $segments[] = $segment;
        }

        $normalized = implode('/', $segments);

        if ($normalized === '' && !$allowEmpty) {
            throw InvalidDocumentPathException::forPath($original, 'path cannot be empty');
        }

        return $normalized;
    }

    public static function isExternalUrl(string $path): bool
    {
        return preg_match('#^(?:https?:)?//#i', trim($path)) === 1;
    }

    private function normalizeRoot(string $root): string
    {
        if (str_contains($root, "\0")) {
            throw InvalidDocumentPathException::forPath($root, 'null bytes are not allowed');
        }

        $root = trim($root);

        if ($root === '') {
            throw InvalidDocumentPathException::forPath($root, 'document root cannot be empty');
        }

        $realPath = realpath($root);

        if ($realPath === false || !is_dir($realPath)) {
            throw InvalidDocumentPathException::forPath($root, 'document root must be an existing directory');
        }

        return rtrim($realPath, DIRECTORY_SEPARATOR);
    }

    private function relativePathForRoot(string $path, string $prefix): ?string
    {
        if ($prefix === '') {
            return $path;
        }

        if ($path === $prefix) {
            return '';
        }

        if (!str_starts_with($path, $prefix . '/')) {
            return null;
        }

        return substr($path, strlen($prefix) + 1);
    }

    /**
     * @return list<string>
     */
    private function candidateFiles(string $relativePath): array
    {
        if ($relativePath === '') {
            return ['index.md', 'README.md'];
        }

        return [
            $relativePath . '.md',
            $relativePath . '/index.md',
            $relativePath . '/README.md',
        ];
    }

    private function resolveCandidate(string $root, string $relativePath): string
    {
        if ($relativePath === '') {
            return $root;
        }

        $candidate = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $this->assertWithinRoot($candidate, $root);

        return $candidate;
    }

    private function documentPathFromFile(string $relativePath): string
    {
        $relativePath = self::normalizePath($relativePath, true);
        $relativePath = preg_replace('/\.md$/i', '', $relativePath) ?? $relativePath;
        $segments = $relativePath === '' ? [] : explode('/', $relativePath);

        while ($segments !== [] && in_array(strtolower((string) end($segments)), ['index', 'readme'], true)) {
            array_pop($segments);
        }

        return implode('/', $segments);
    }

    private function joinPaths(string $prefix, string $path): string
    {
        $prefix = self::normalizeDocumentPath($prefix, true);
        $path = self::normalizeDocumentPath($path, true);

        if ($prefix === '') {
            return $path;
        }

        if ($path === '') {
            return $prefix;
        }

        return $prefix . '/' . $path;
    }

    /**
     * @return list<SplFileInfo>
     */
    private function markdownFiles(string $root): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo || !$file->isFile()) {
                continue;
            }

            if (strtolower($file->getExtension()) !== 'md') {
                continue;
            }

            $files[] = $file;
        }

        return $files;
    }

    private function relativeFilePath(string $root, string $realPath): string
    {
        $root = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $root), DIRECTORY_SEPARATOR);
        $path = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $realPath), DIRECTORY_SEPARATOR);

        if ($path === $root) {
            return '';
        }

        return str_replace(DIRECTORY_SEPARATOR, '/', substr($path, strlen($root) + 1));
    }

    private function assertWithinRoot(string $path, string $root): void
    {
        $path = $this->comparablePath($path);
        $root = $this->comparablePath($root);

        if ($path !== $root && !str_starts_with($path, $root . DIRECTORY_SEPARATOR)) {
            throw InvalidDocumentPathException::forPath($path, 'resolved path escapes the document root');
        }
    }

    private function comparablePath(string $path): string
    {
        $path = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);

        if (DIRECTORY_SEPARATOR === '\\') {
            $path = strtolower($path);
        }

        return $path;
    }
}
