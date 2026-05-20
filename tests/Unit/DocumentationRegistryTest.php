<?php

declare(strict_types=1);

namespace CommonPHP\Docs\Tests\Unit;

use CommonPHP\Docs\Documentation;
use CommonPHP\Docs\DocumentationRegistry;
use CommonPHP\Docs\Exceptions\DocsException;
use CommonPHP\Docs\FilesystemDocumentLoader;
use CommonPHP\Docs\Tests\Fixtures\TemporaryDocsTrait;
use PHPUnit\Framework\TestCase;

final class DocumentationRegistryTest extends TestCase
{
    use TemporaryDocsTrait;

    public function testItRegistersGetsRemovesAndIteratesDocumentationSets(): void
    {
        $manual = $this->documentation('Manual');
        $api = $this->documentation('API');
        $registry = DocumentationRegistry::single('Manual Docs', $manual)
            ->register('api', $api, true);

        self::assertTrue($registry->has('manual-docs'));
        self::assertTrue($registry->has('API'));
        self::assertSame($api, $registry->get());
        self::assertSame($manual, $registry->get('manual-docs'));
        self::assertSame('api', $registry->defaultName());
        self::assertSame(['manual-docs', 'api'], $registry->names());
        self::assertSame(['manual-docs' => $manual, 'api' => $api], $registry->all());
        self::assertSame(['manual-docs' => $manual, 'api' => $api], iterator_to_array($registry));
        self::assertSame(2, count($registry));

        $registry->setDefault('manual-docs')->remove('manual-docs');

        self::assertSame('api', $registry->defaultName());

        $registry->clear();

        self::assertSame([], $registry->all());
        self::assertSame(0, count($registry));
    }

    public function testConstructorValidatesEntries(): void
    {
        $this->expectException(DocsException::class);
        $this->expectExceptionMessage('Documentation registry values must be Documentation instances.');

        new DocumentationRegistry(['bad' => new \stdClass()]);
    }

    public function testConstructorRequiresStringNames(): void
    {
        $this->expectException(DocsException::class);
        $this->expectExceptionMessage('Documentation registry names must be strings.');

        new DocumentationRegistry([$this->documentation('Manual')]);
    }

    public function testItRejectsDuplicateMissingAndEmptyNames(): void
    {
        $manual = $this->documentation('Manual');
        $registry = DocumentationRegistry::single('manual', $manual);

        $this->expectException(DocsException::class);
        $this->expectExceptionMessage('already registered');

        $registry->register('manual', $manual);
    }

    public function testItThrowsForMissingDefaultAndEntries(): void
    {
        $registry = new DocumentationRegistry();

        $this->expectException(DocsException::class);
        $this->expectExceptionMessage('No default documentation registry entry is configured.');

        $registry->get();
    }

    public function testItThrowsWhenSettingMissingDefault(): void
    {
        $registry = new DocumentationRegistry();

        $this->expectException(DocsException::class);
        $this->expectExceptionMessage('was not found');

        $registry->setDefault('missing');
    }

    public function testItNormalizesNamesAndRejectsEmptyResults(): void
    {
        self::assertSame('manual-docs-v1', DocumentationRegistry::normalizeName(' Manual Docs v1! '));

        $this->expectException(DocsException::class);
        $this->expectExceptionMessage('Documentation registry name cannot be empty.');

        DocumentationRegistry::normalizeName('!!!');
    }

    public function testItResolvesNamedDefaultAndEmptyPaths(): void
    {
        $manual = $this->documentation('Manual');
        $api = $this->documentation('API');
        $registry = DocumentationRegistry::single('manual', $manual)
            ->set('api', $api);

        self::assertSame([$manual, '', 'manual'], $registry->resolvePath(''));
        self::assertSame([$api, 'reference', 'api'], $registry->resolvePath('api/reference'));
        self::assertSame([$manual, 'guide', 'manual'], $registry->resolvePath('guide'));
    }

    private function documentation(string $title): Documentation
    {
        $root = $this->createTemporaryDirectory();
        $this->writeDocument($root, 'index.md', '# ' . $title);

        return new Documentation(new FilesystemDocumentLoader($root), title: $title);
    }
}
