<?php

declare(strict_types=1);

namespace CommonPHP\Docs\Tests\Unit;

use CommonPHP\Docs\DocumentPage;
use CommonPHP\Docs\Navigation;
use CommonPHP\Docs\NavigationBuilder;
use CommonPHP\Docs\NavigationItem;
use PHPUnit\Framework\TestCase;

final class NavigationBuilderTest extends TestCase
{
    public function testItBuildsNestedNavigationAndMarksCurrentPath(): void
    {
        $navigation = (new NavigationBuilder())->build([
            new DocumentPage('', '# Home', metadata: ['order' => -10]),
            new DocumentPage('guide/install', '# Install'),
            new DocumentPage('guide/configuration', '# Configuration'),
            new DocumentPage('internal', '# Internal', metadata: ['hidden' => true]),
        ], '/docs', 'guide/install');

        $home = $navigation->find('');
        $guide = $navigation->find('guide');
        $install = $navigation->find('guide/install');

        self::assertNotNull($home);
        self::assertNotNull($guide);
        self::assertNotNull($install);
        self::assertSame('/docs', $home->href());
        self::assertSame('Guide', $guide->title());
        self::assertTrue($guide->isActive());
        self::assertTrue($install->isCurrent());
        self::assertNull($navigation->find('internal'));
        self::assertSame('/docs', $navigation->basePath());
        self::assertSame('guide/install', $navigation->currentPath());
        self::assertCount(4, $navigation->flatten());
        self::assertSame($navigation->items(), $navigation->all());
        self::assertStringContainsString('aria-current="page"', $navigation->toHtml());
    }

    public function testNavigationObjectSupportsIterationArraysEscapingAndEmptyState(): void
    {
        $child = new NavigationItem('child', '<Child>', '/docs/child', true, true);
        $item = new NavigationItem('parent', '<Parent>', '/docs/parent?x=<y>', children: [$child]);
        $navigation = new Navigation([$item], '/docs', 'child');

        self::assertFalse($navigation->isEmpty());
        self::assertSame(1, count($navigation));
        self::assertSame([$item], iterator_to_array($navigation));
        self::assertSame($child, $navigation->find('child'));
        self::assertSame([
            [
                'path' => 'parent',
                'title' => '<Parent>',
                'href' => '/docs/parent?x=<y>',
                'active' => false,
                'current' => false,
                'children' => [
                    [
                        'path' => 'child',
                        'title' => '<Child>',
                        'href' => '/docs/child',
                        'active' => true,
                        'current' => true,
                        'children' => [],
                    ],
                ],
            ],
        ], $navigation->toArray());
        self::assertStringContainsString('&lt;Parent&gt;', $navigation->toHtml());
        self::assertStringContainsString('/docs/parent?x=&lt;y&gt;', $navigation->toHtml());
        self::assertSame('<Parent>', (string) $item);
        self::assertSame([$child], $item->children());
        self::assertNotSame($item, $item->withChildren([]));

        $empty = new Navigation();
        self::assertTrue($empty->isEmpty());
        self::assertSame('', $empty->toHtml());
        self::assertNull($empty->find('missing'));
    }

    public function testBuilderIgnoresNonPageValuesAndSupportsRootBasePath(): void
    {
        $navigation = (new NavigationBuilder())->build([
            new DocumentPage('space path', '# Space'),
            'ignored',
        ], '/', 'space path');

        $item = $navigation->find('space path');

        self::assertNotNull($item);
        self::assertSame('/space%20path', $item->href());
        self::assertTrue($item->isCurrent());
    }
}
