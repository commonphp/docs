<?php

declare(strict_types=1);

namespace CommonPHP\Docs;

use ArrayAccess;
use CommonPHP\Docs\Exceptions\DocumentParseException;
use LogicException;
use Stringable;

/**
 * @implements ArrayAccess<string, mixed>
 */
final class DocumentPage implements ArrayAccess, Stringable
{
    private string $path;

    private string $title;

    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        string $path,
        private readonly string $markdown,
        ?string $title = null,
        private readonly ?string $realPath = null,
        private readonly ?int $modifiedAt = null,
        private readonly array $metadata = [],
    ) {
        $this->path = FilesystemDocumentLoader::normalizeDocumentPath($path, true);
        $this->title = $this->resolveTitle($title, $this->markdown, $this->metadata);
    }

    public static function fromMarkdown(
        string $path,
        string $markdown,
        ?string $realPath = null,
        ?int $modifiedAt = null,
    ): self {
        [$metadata, $body] = self::splitFrontMatter($path, $markdown);
        $title = isset($metadata['title']) && is_scalar($metadata['title']) ? (string) $metadata['title'] : null;

        return new self($path, $body, $title, $realPath, $modifiedAt, $metadata);
    }

    public function path(): string
    {
        return $this->path;
    }

    public function slug(): string
    {
        return $this->path === '' ? 'index' : str_replace('/', '-', $this->path);
    }

    public function markdown(): string
    {
        return $this->markdown;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function navigationTitle(): string
    {
        $title = $this->metadata['navTitle'] ?? $this->metadata['nav_title'] ?? null;

        return is_scalar($title) && trim((string) $title) !== '' ? trim((string) $title) : $this->title;
    }

    public function realPath(): ?string
    {
        return $this->realPath;
    }

    public function modifiedAt(): ?int
    {
        return $this->modifiedAt;
    }

    /**
     * @return array<string, mixed>
     */
    public function metadata(): array
    {
        return $this->metadata;
    }

    public function meta(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    public function order(): int
    {
        $order = $this->metadata['order'] ?? null;

        return is_numeric($order) ? (int) $order : 0;
    }

    public function isHidden(): bool
    {
        $hidden = $this->metadata['hidden'] ?? false;

        if (is_bool($hidden)) {
            return $hidden;
        }

        if (is_string($hidden)) {
            return in_array(strtolower(trim($hidden)), ['1', 'true', 'yes', 'on'], true);
        }

        return (bool) $hidden;
    }

    public function isIndex(): bool
    {
        return $this->path === '';
    }

    public function parentPath(): ?string
    {
        if ($this->path === '' || !str_contains($this->path, '/')) {
            return null;
        }

        return dirname($this->path);
    }

    public function href(string $basePath = '/docs'): string
    {
        $basePath = Documentation::normalizeBasePath($basePath);

        if ($this->path === '') {
            return $basePath;
        }

        return rtrim($basePath, '/') . '/' . implode('/', array_map('rawurlencode', explode('/', $this->path)));
    }

    /**
     * @return list<array{level: int, text: string, id: string}>
     */
    public function headings(): array
    {
        $headings = [];

        foreach (preg_split('/\R/', $this->markdown) ?: [] as $line) {
            if (preg_match('/^(#{1,6})\s+(.+?)\s*#*$/', trim($line), $matches) !== 1) {
                continue;
            }

            $text = trim($matches[2]);
            $headings[] = [
                'level' => strlen($matches[1]),
                'text' => $text,
                'id' => MarkdownConverter::anchorId($text),
            ];
        }

        return $headings;
    }

    /**
     * @return array{
     *     path: string,
     *     title: string,
     *     navigationTitle: string,
     *     realPath: string|null,
     *     modifiedAt: int|null,
     *     metadata: array<string, mixed>
     * }
     */
    public function toArray(): array
    {
        return [
            'path' => $this->path,
            'title' => $this->title,
            'navigationTitle' => $this->navigationTitle(),
            'realPath' => $this->realPath,
            'modifiedAt' => $this->modifiedAt,
            'metadata' => $this->metadata,
        ];
    }

    public function offsetExists(mixed $offset): bool
    {
        return is_string($offset) && array_key_exists($offset, $this->metadata);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return is_string($offset) ? ($this->metadata[$offset] ?? null) : null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new LogicException('Document page metadata is immutable.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new LogicException('Document page metadata is immutable.');
    }

    public function __toString(): string
    {
        return $this->path;
    }

    /**
     * @return array{0: array<string, mixed>, 1: string}
     */
    private static function splitFrontMatter(string $path, string $markdown): array
    {
        if (preg_match('/\A---\s*\R(.*?)\R---\s*(?:\R|\z)(.*)\z/s', $markdown, $matches) !== 1) {
            return [[], $markdown];
        }

        $metadata = [];

        foreach (preg_split('/\R/', trim($matches[1])) ?: [] as $line) {
            if (trim($line) === '' || str_starts_with(ltrim($line), '#')) {
                continue;
            }

            if (preg_match('/^([A-Za-z0-9_.-]+)\s*:\s*(.*)$/', $line, $parts) !== 1) {
                throw DocumentParseException::forPath($path, 'front matter entries must use "key: value" syntax');
            }

            $metadata[$parts[1]] = self::parseMetadataValue($parts[2]);
        }

        return [$metadata, ltrim($matches[2])];
    }

    private static function parseMetadataValue(string $value): mixed
    {
        $value = trim($value);

        return match (strtolower($value)) {
            'true' => true,
            'false' => false,
            'null' => null,
            default => is_numeric($value) ? (str_contains($value, '.') ? (float) $value : (int) $value) : trim($value, '"\''),
        };
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function resolveTitle(?string $title, string $markdown, array $metadata): string
    {
        if ($title !== null && trim($title) !== '') {
            return trim($title);
        }

        foreach (preg_split('/\R/', $markdown) ?: [] as $line) {
            if (preg_match('/^#\s+(.+?)\s*#*$/', trim($line), $matches) === 1) {
                return trim($matches[1]);
            }
        }

        $fallback = $metadata['slug'] ?? basename($this->path === '' ? 'documentation' : $this->path);

        return ucwords(str_replace(['-', '_'], ' ', (string) $fallback));
    }
}
