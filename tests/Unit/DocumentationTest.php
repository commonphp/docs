<?php

declare(strict_types=1);

namespace CommonPHP\Docs\Tests\Unit;

use CommonPHP\Docs\Contracts\DocumentLoaderInterface;
use CommonPHP\Docs\Contracts\DocumentationRendererInterface;
use CommonPHP\Docs\Contracts\NavigationBuilderInterface;
use CommonPHP\Docs\DocResponse;
use CommonPHP\Docs\Documentation;
use CommonPHP\Docs\DocumentationRenderer;
use CommonPHP\Docs\DocumentPage;
use CommonPHP\Docs\Exceptions\InvalidDocumentPathException;
use CommonPHP\Docs\Navigation;
use CommonPHP\Docs\NavigationBuilder;
use CommonPHP\Docs\Tests\Fixtures\TemporaryDocsTrait;
use CommonPHP\HTTP\Request;
use CommonPHP\Router\Router;
use PHPUnit\Framework\TestCase;

final class DocumentationTest extends TestCase
{
    use TemporaryDocsTrait;

    public function testItDelegatesLoadingRenderingNavigationAndResponses(): void
    {
        $root = $this->createTemporaryDirectory();
        $this->writeDocument($root, 'index.md', '# Home');
        $this->writeDocument($root, 'guide.md', '# Guide');
        $docs = Documentation::fromRoot($root, 'Manual', '/manual');

        self::assertSame('Manual', $docs->title());
        self::assertSame('/manual', $docs->basePath());
        self::assertTrue($docs->exists('guide'));
        self::assertFalse($docs->exists('missing'));
        self::assertSame('Guide', $docs->load('guide')->title());
        self::assertSame(['', 'guide'], $docs->paths());
        self::assertSame(['', 'guide'], array_map(static fn (DocumentPage $page): string => $page->path(), $docs->pages()));
        self::assertStringContainsString('<title>Guide - Manual</title>', $docs->render('guide'));
        self::assertInstanceOf(Navigation::class, $docs->navigation('guide'));

        $response = $docs->response('guide');
        $notFound = $docs->notFoundResponse('missing', new Request('HEAD', '/manual/missing'));

        self::assertInstanceOf(DocResponse::class, $response);
        self::assertSame(200, $response->statusCode());
        self::assertSame(404, $notFound->statusCode());
        self::assertSame('', $notFound->body());
        self::assertGreaterThan(0, (int) $notFound->header('Content-Length'));
    }

    public function testItCanSwapCollaboratorsAndNormalizeTitleAndBasePath(): void
    {
        $loader = new class implements DocumentLoaderInterface {
            public function load(string $path = ''): DocumentPage
            {
                return new DocumentPage($path, '# Swapped');
            }

            public function exists(string $path = ''): bool
            {
                return true;
            }

            public function all(): array
            {
                return [$this->load('swapped')];
            }

            public function paths(): array
            {
                return ['swapped'];
            }
        };
        $renderer = new DocumentationRenderer();
        $navigationBuilder = new NavigationBuilder();
        $docs = new Documentation($loader, title: ' ');

        $result = $docs
            ->useLoader($loader)
            ->useRenderer($renderer)
            ->useNavigationBuilder($navigationBuilder)
            ->useTitle('  Manual  ')
            ->useBasePath('manual/docs');

        self::assertSame($docs, $result);
        self::assertSame($loader, $docs->loader());
        self::assertSame($renderer, $docs->renderer());
        self::assertSame($navigationBuilder, $docs->navigationBuilder());
        self::assertSame('Manual', $docs->title());
        self::assertSame('/manual/docs', $docs->basePath());
        self::assertSame('Swapped', $docs->load('swapped')->title());
    }

    public function testItRegistersRoutesOnCollectionsAndRouters(): void
    {
        $root = $this->createTemporaryDirectory();
        $this->writeDocument($root, 'index.md', '# Home');
        $this->writeDocument($root, 'topic.md', '# Topic');
        $docs = Documentation::fromRoot($root, 'Manual', '/manual');
        $router = new Router();

        self::assertSame($docs, $docs->registerRoutes($router));

        $index = $router->dispatch(new Request('GET', '/manual'));
        $topic = $router->dispatch(new Request('GET', '/manual/topic'));

        self::assertStringContainsString('Home', $index->body());
        self::assertStringContainsString('Topic', $topic->body());
        self::assertSame('docs.index', $router->routes()->named('docs.index')->name());
        self::assertSame('docs.page', $router->routes()->named('docs.page')->name());
    }

    public function testItNormalizesBasePathsAndExtractsRequestPaths(): void
    {
        self::assertSame('/', Documentation::normalizeBasePath(' / '));
        self::assertSame('/manual/docs', Documentation::normalizeBasePath('manual/docs'));
        self::assertSame('', Documentation::pathFromRequest(new Request('GET', '/manual'), '/manual'));
        self::assertSame('guide/install', Documentation::pathFromRequest(new Request('GET', '/manual/guide/install.md'), '/manual'));
        self::assertSame('guide', Documentation::pathFromRequest(new Request('GET', '/guide/index'), '/'));

        $this->expectException(InvalidDocumentPathException::class);

        Documentation::pathFromRequest(new Request('GET', '/outside'), '/manual');
    }
}
