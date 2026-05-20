<?php

declare(strict_types=1);

namespace CommonPHP\Docs\Tests\Unit;

use CommonPHP\Docs\Contracts\DocumentLoaderInterface;
use CommonPHP\Docs\Exceptions\DocumentParseException;
use CommonPHP\Docs\Exceptions\DocumentNotFoundException;
use CommonPHP\Docs\Exceptions\InvalidDocumentPathException;
use CommonPHP\Docs\FilesystemDocumentLoader;
use CommonPHP\Docs\Tests\Fixtures\TemporaryDocsTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FilesystemDocumentLoaderTest extends TestCase
{
    use TemporaryDocsTrait;

    public function testItLoadsMarkdownDocumentsFromRoot(): void
    {
        $root = $this->createTemporaryDirectory();
        $index = $this->writeDocument($root, 'index.md', "---\ntitle: Manual\nnavTitle: Home\norder: -10\n---\n# Ignored\n\nWelcome.");
        $guide = $this->writeDocument($root, 'guide/install.md', "# Install\n\nRun `composer install`.");
        $loader = new FilesystemDocumentLoader($root);

        $page = $loader->load('');
        $install = $loader->load('/guide/install.md?from=test');

        self::assertInstanceOf(DocumentLoaderInterface::class, $loader);
        self::assertSame('', $page->path());
        self::assertSame('Manual', $page->title());
        self::assertSame('Home', $page->navigationTitle());
        self::assertSame(-10, $page->order());
        self::assertSame(realpath($index), $page->realPath());
        self::assertStringNotContainsString('title: Manual', $page->markdown());
        self::assertSame('guide/install', $install->path());
        self::assertSame('Install', $install->title());
        self::assertSame(realpath($guide), $install->realPath());
        self::assertTrue($loader->exists('guide/install'));
        self::assertSame(['', 'guide/install'], $loader->paths());
    }

    public function testItResolvesPrefixedRoots(): void
    {
        $root = $this->createTemporaryDirectory();
        $this->writeDocument($root, 'index.md', '# Package Docs');
        $this->writeDocument($root, 'guide/index.md', '# Guide Index');
        $loader = new FilesystemDocumentLoader(['package' => $root]);

        $page = $loader->load('package');
        $guide = $loader->load('package/guide');

        self::assertSame('package', $page->path());
        self::assertSame('Package Docs', $page->title());
        self::assertSame('package/guide', $guide->path());
        self::assertSame(['package', 'package/guide'], $loader->paths());
    }

    public function testItSupportsMultipleRootsReadmeFallbackAndOrdering(): void
    {
        $first = $this->createTemporaryDirectory();
        $second = $this->createTemporaryDirectory();
        $this->writeDocument($first, 'README.md', "---\norder: 20\n---\n# First Home");
        $this->writeDocument($first, 'api/reference.md', "---\norder: 30\n---\n# Reference");
        $this->writeDocument($second, 'guide/README.md', "---\norder: 10\n---\n# Guide Home");
        $this->writeDocument($second, 'notes.txt', 'Ignored');
        $loader = new FilesystemDocumentLoader([$first, $second]);

        $pages = $loader->all();

        self::assertSame('First Home', $loader->load('readme')->title());
        self::assertSame('Guide Home', $loader->load('guide')->title());
        self::assertSame(['', 'api/reference', 'guide'], $loader->paths());
        self::assertSame(['guide', '', 'api/reference'], array_map(static fn ($page): string => $page->path(), $pages));
    }

    public function testItCanBeCreatedWithoutRootsAndThenConfigured(): void
    {
        $root = $this->createTemporaryDirectory();
        $loader = new FilesystemDocumentLoader();

        self::assertSame([], $loader->roots());
        self::assertFalse($loader->exists('missing'));

        $loader->addRoot($root, 'manual');
        $this->writeDocument($root, 'index.md', '# Manual');

        self::assertSame('Manual', $loader->load('manual')->title());
        self::assertSame('manual', $loader->roots()[0]['prefix']);
        self::assertSame(realpath($root), $loader->roots()[0]['root']);
    }

    public function testMissingDocumentsThrowDocumentNotFoundException(): void
    {
        $loader = new FilesystemDocumentLoader($this->createTemporaryDirectory());

        $this->expectException(DocumentNotFoundException::class);
        $this->expectExceptionMessage('Documentation page not found: "missing".');

        $loader->load('missing');
    }

    public function testExistsReturnsFalseForParseFailuresAndInvalidPaths(): void
    {
        $root = $this->createTemporaryDirectory();
        $this->writeDocument($root, 'broken.md', "---\nnot valid\n---\n# Broken");
        $loader = new FilesystemDocumentLoader($root);

        self::assertFalse($loader->exists('missing'));
        self::assertFalse($loader->exists('../secret'));
        self::assertFalse($loader->exists('broken'));

        $this->expectException(DocumentParseException::class);

        $loader->load('broken');
    }

    public function testItRejectsInvalidRoots(): void
    {
        $this->expectException(InvalidDocumentPathException::class);
        $this->expectExceptionMessage('document root must be an existing directory');

        new FilesystemDocumentLoader($this->createTemporaryDirectory() . '/missing');
    }

    public function testItRejectsEmptyRoots(): void
    {
        $this->expectException(InvalidDocumentPathException::class);
        $this->expectExceptionMessage('document root cannot be empty');

        new FilesystemDocumentLoader(' ');
    }

    #[DataProvider('invalidPathProvider')]
    public function testItRejectsUnsafeDocumentPaths(string $path): void
    {
        $this->expectException(InvalidDocumentPathException::class);

        FilesystemDocumentLoader::normalizeDocumentPath($path);
    }

    public function testItAllowsEmptyPathOnlyWhenRequested(): void
    {
        self::assertSame('', FilesystemDocumentLoader::normalizeDocumentPath('', true));

        $this->expectException(InvalidDocumentPathException::class);

        FilesystemDocumentLoader::normalizeDocumentPath('');
    }

    public function testItNormalizesDocumentPathsAndGeneralPaths(): void
    {
        self::assertSame('guide/install', FilesystemDocumentLoader::normalizeDocumentPath('/guide//install.md?ref=1'));
        self::assertSame('guide', FilesystemDocumentLoader::normalizeDocumentPath('guide/index.md'));
        self::assertSame('guide', FilesystemDocumentLoader::normalizeDocumentPath('guide/README.md'));
        self::assertSame('guide/install.md', FilesystemDocumentLoader::normalizePath('./guide/install.md?ref=1'));
        self::assertSame('', FilesystemDocumentLoader::normalizePath('', true));
    }

    public function testItDetectsExternalUrls(): void
    {
        self::assertTrue(FilesystemDocumentLoader::isExternalUrl('https://example.test/docs'));
        self::assertTrue(FilesystemDocumentLoader::isExternalUrl('//cdn.example.test/docs'));
        self::assertFalse(FilesystemDocumentLoader::isExternalUrl('/docs/index'));
    }

    public static function invalidPathProvider(): iterable
    {
        yield 'traversal' => ['../secret'];
        yield 'nested traversal' => ['guide/../secret'];
        yield 'encoded traversal' => ['guide/%2e%2e/secret'];
        yield 'null byte' => ["guide\0install"];
        yield 'http url' => ['https://example.test/docs'];
        yield 'scheme' => ['php://filter/resource=index.md'];
        yield 'windows drive' => ['C:\\docs\\index.md'];
    }
}
