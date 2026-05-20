<?php

declare(strict_types=1);

namespace CommonPHP\Docs\Tests\Unit;

use CommonPHP\Docs\Contracts\DocumentationRendererInterface;
use CommonPHP\Docs\DocResponse;
use CommonPHP\Docs\DocumentationRenderer;
use CommonPHP\Docs\DocumentPage;
use CommonPHP\Docs\Exceptions\DocumentRenderException;
use CommonPHP\Docs\Navigation;
use CommonPHP\Docs\NavigationItem;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class DocResponseRendererTest extends TestCase
{
    public function testRendererProducesFullHtmlWithNavigationAndUpdatedTimestamp(): void
    {
        $page = new DocumentPage('guide', '# Guide <Danger>', modifiedAt: 1700000000);
        $navigation = new Navigation([
            new NavigationItem('guide', 'Guide', '/docs/guide', true, true),
        ], '/docs', 'guide');
        $html = (new DocumentationRenderer())->render($page, $navigation, 'Manual <Docs>');

        self::assertStringStartsWith('<!doctype html>', $html);
        self::assertStringContainsString('<title>Guide &lt;Danger&gt; - Manual &lt;Docs&gt;</title>', $html);
        self::assertStringContainsString('<strong>Manual &lt;Docs&gt;</strong>', $html);
        self::assertStringContainsString('<aside class="docs-sidebar">', $html);
        self::assertStringContainsString('<h1 id="guide-danger">Guide &lt;Danger&gt;</h1>', $html);
        self::assertStringContainsString('Updated 2023-11-14 22:13 UTC', $html);
    }

    public function testRendererUsesDefaultTitlesAndEscapesNotFoundPaths(): void
    {
        $renderer = new DocumentationRenderer();

        $html = $renderer->render(new DocumentPage('', '# Documentation'), null, ' ');
        $notFound = $renderer->renderNotFound('<missing>', ' ');

        self::assertStringContainsString('<title>Documentation</title>', $html);
        self::assertStringNotContainsString('<aside class="docs-sidebar">', $html);
        self::assertStringContainsString('<code>&lt;missing&gt;</code>', $notFound);
        self::assertStringContainsString('<title>Documentation page not found - Documentation</title>', $notFound);
    }

    public function testDocResponseBuildsHeadersAndCanOmitBody(): void
    {
        $page = new DocumentPage('guide', '# Guide', modifiedAt: 1700000000);
        $response = DocResponse::fromPage($page, new DocumentationRenderer(), includeBody: false);

        self::assertSame($page, $response->page());
        self::assertSame(200, $response->statusCode());
        self::assertSame('', $response->body());
        self::assertSame('text/html; charset=utf-8', $response->header('Content-Type'));
        self::assertGreaterThan(0, (int) $response->header('Content-Length'));
        self::assertSame('Tue, 14 Nov 2023 22:13:20 GMT', $response->header('Last-Modified'));
    }

    public function testNotFoundResponseCanOmitBody(): void
    {
        $response = DocResponse::notFound('missing', new DocumentationRenderer(), includeBody: false);

        self::assertNull($response->page());
        self::assertSame(404, $response->statusCode());
        self::assertSame('', $response->body());
        self::assertGreaterThan(0, (int) $response->header('Content-Length'));
    }

    public function testRendererFailuresAreWrappedInDocumentRenderException(): void
    {
        $renderer = new class implements DocumentationRendererInterface {
            public function render(DocumentPage $page, ?\CommonPHP\Docs\Navigation $navigation = null, ?string $title = null): string
            {
                throw new RuntimeException('template exploded');
            }

            public function renderNotFound(string $path = '', ?string $title = null): string
            {
                return '';
            }
        };

        $this->expectException(DocumentRenderException::class);
        $this->expectExceptionMessage('Unable to render documentation page "guide": template exploded');

        DocResponse::fromPage(new DocumentPage('guide', '# Guide'), $renderer);
    }
}
