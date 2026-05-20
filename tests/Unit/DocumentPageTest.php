<?php

declare(strict_types=1);

namespace CommonPHP\Docs\Tests\Unit;

use CommonPHP\Docs\DocumentPage;
use CommonPHP\Docs\Exceptions\DocumentParseException;
use LogicException;
use PHPUnit\Framework\TestCase;

final class DocumentPageTest extends TestCase
{
    public function testItExposesDocumentMetadataAndDerivedValues(): void
    {
        $page = new DocumentPage(
            '/guide/install.md',
            "# Install\n\n## Requirements\n\n### PHP",
            '  Install Guide  ',
            '/tmp/install.md',
            1700000000,
            [
                'nav_title' => 'Setup',
                'order' => '20',
                'hidden' => 'yes',
                'owner' => 'docs',
            ],
        );

        self::assertSame('guide/install', $page->path());
        self::assertSame('guide-install', $page->slug());
        self::assertSame("# Install\n\n## Requirements\n\n### PHP", $page->markdown());
        self::assertSame('Install Guide', $page->title());
        self::assertSame('Setup', $page->navigationTitle());
        self::assertSame('/tmp/install.md', $page->realPath());
        self::assertSame(1700000000, $page->modifiedAt());
        self::assertSame('docs', $page->meta('owner'));
        self::assertSame('fallback', $page->meta('missing', 'fallback'));
        self::assertSame(20, $page->order());
        self::assertTrue($page->isHidden());
        self::assertFalse($page->isIndex());
        self::assertSame('guide', $page->parentPath());
        self::assertSame('/manual/guide/install', $page->href('/manual'));
        self::assertSame('/guide/install', $page->href('/'));
        self::assertSame([
            ['level' => 1, 'text' => 'Install', 'id' => 'install'],
            ['level' => 2, 'text' => 'Requirements', 'id' => 'requirements'],
            ['level' => 3, 'text' => 'PHP', 'id' => 'php'],
        ], $page->headings());
        self::assertSame('guide/install', (string) $page);
    }

    public function testItParsesFrontMatterTypesAndFallsBackToHeadingTitle(): void
    {
        $page = DocumentPage::fromMarkdown('README.md', <<<'MD'
---
hidden: false
order: 3
score: 1.5
nullable: null
label: "Public Docs"
---
# Heading Title

Body.
MD);

        self::assertSame('', $page->path());
        self::assertSame('Heading Title', $page->title());
        self::assertFalse($page->isHidden());
        self::assertSame(3, $page->order());
        self::assertSame(1.5, $page->meta('score'));
        self::assertNull($page->meta('nullable'));
        self::assertSame('Public Docs', $page->meta('label'));
        self::assertSame('/docs', $page->href());
        self::assertTrue($page->isIndex());
        self::assertNull($page->parentPath());
    }

    public function testFrontMatterTitleAndNavTitleTakePrecedence(): void
    {
        $page = DocumentPage::fromMarkdown('concepts.md', <<<'MD'
---
title: Canonical Title
navTitle: Short Title
hidden: on
---
# Heading Title
MD);

        self::assertSame('Canonical Title', $page->title());
        self::assertSame('Short Title', $page->navigationTitle());
        self::assertTrue($page->isHidden());
    }

    public function testItFallsBackToSlugOrBasenameWhenNoHeadingExists(): void
    {
        $fromSlug = new DocumentPage('deep/custom-page', 'Plain body', metadata: ['slug' => 'fallback-slug']);
        $fromPath = new DocumentPage('deep/custom-page', 'Plain body');

        self::assertSame('Fallback Slug', $fromSlug->title());
        self::assertSame('Custom Page', $fromPath->title());
    }

    public function testItSerializesToArrayAndSupportsReadOnlyArrayAccess(): void
    {
        $page = new DocumentPage('topic', '# Topic', realPath: '/tmp/topic.md', modifiedAt: 123, metadata: ['key' => 'value']);

        self::assertTrue(isset($page['key']));
        self::assertFalse(isset($page['missing']));
        self::assertSame('value', $page['key']);
        self::assertNull($page[1]);
        self::assertSame([
            'path' => 'topic',
            'title' => 'Topic',
            'navigationTitle' => 'Topic',
            'realPath' => '/tmp/topic.md',
            'modifiedAt' => 123,
            'metadata' => ['key' => 'value'],
        ], $page->toArray());

        $this->expectException(LogicException::class);

        $page['key'] = 'changed';
    }

    public function testItRejectsInvalidFrontMatterLines(): void
    {
        $this->expectException(DocumentParseException::class);
        $this->expectExceptionMessage('front matter entries must use "key: value" syntax');

        DocumentPage::fromMarkdown('broken', "---\nnot valid\n---\n# Broken");
    }

    public function testArrayUnsetIsNotAllowed(): void
    {
        $page = new DocumentPage('topic', '# Topic', metadata: ['key' => 'value']);

        $this->expectException(LogicException::class);

        unset($page['key']);
    }
}
