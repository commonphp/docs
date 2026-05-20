<?php

declare(strict_types=1);

namespace CommonPHP\Docs\Tests\Unit;

use CommonPHP\Docs\DocResponse;
use CommonPHP\Docs\DocSurface;
use CommonPHP\Docs\Documentation;
use CommonPHP\Docs\DocumentationRegistry;
use CommonPHP\Docs\Tests\Fixtures\TemporaryDocsTrait;
use CommonPHP\HTTP\Request;
use PHPUnit\Framework\TestCase;

final class DocumentationSurfaceTest extends TestCase
{
    use TemporaryDocsTrait;

    public function testDocumentationRendersResponsesAndHeadRequests(): void
    {
        $root = $this->createTemporaryDirectory();
        $this->writeDocument($root, 'index.md', '# Home');
        $this->writeDocument($root, 'guide.md', '# Guide');
        $documentation = Documentation::fromRoot($root, 'Manual', '/docs');

        $response = $documentation->response('guide', new Request('GET', '/docs/guide'));
        $head = $documentation->response('guide', new Request('HEAD', '/docs/guide'));

        self::assertInstanceOf(DocResponse::class, $response);
        self::assertSame(200, $response->statusCode());
        self::assertSame('guide', $response->page()?->path());
        self::assertStringContainsString('<h1 id="guide">Guide</h1>', $response->body());
        self::assertSame('', $head->body());
        self::assertGreaterThan(0, (int) $head->header('Content-Length'));
    }

    public function testDocSurfaceServesDocumentationAndHandlesErrors(): void
    {
        $root = $this->createTemporaryDirectory();
        $this->writeDocument($root, 'index.md', '# Home');
        $this->writeDocument($root, 'guide.md', '# Guide');
        $surface = new DocSurface(Documentation::fromRoot($root, 'Manual', '/docs'), '/docs');

        $response = $surface->handle(new Request('GET', '/docs/guide'));
        $missing = $surface->handle(new Request('GET', '/docs/missing'));
        $invalid = $surface->handle(new Request('GET', '/docs/%2e%2e/secret'));
        $method = $surface->handle(new Request('POST', '/docs/guide'));

        self::assertTrue($surface->supports(new Request('GET', '/docs/guide')));
        self::assertSame(200, $response->statusCode());
        self::assertStringContainsString('Guide', $response->body());
        self::assertSame(404, $missing->statusCode());
        self::assertStringContainsString('Documentation page not found', $missing->body());
        self::assertSame(400, $invalid->statusCode());
        self::assertSame(405, $method->statusCode());
        self::assertSame('GET, HEAD', $method->header('Allow'));
    }

    public function testDocSurfaceSupportsRootMountNamedRegistriesHeadAndFallbackErrors(): void
    {
        $root = $this->createTemporaryDirectory();
        $this->writeDocument($root, 'index.md', '# Home');
        $this->writeDocument($root, 'guide.md', '# Guide');
        $docs = Documentation::fromRoot($root, 'Manual', '/');
        $registry = DocumentationRegistry::single('manual', $docs);
        $surface = new DocSurface($registry, '/');

        $named = $surface->handle(new Request('GET', '/manual/guide'));
        $head = $surface->handle(new Request('HEAD', '/manual/guide'));
        $unconfigured = (new DocSurface())->handle(new Request('GET', '/docs'));

        self::assertSame('/', $surface->pathPrefix());
        self::assertSame($registry, $surface->registry());
        self::assertTrue($surface->supports(new Request('GET', '/anything')));
        self::assertSame(200, $named->statusCode());
        self::assertStringContainsString('Guide', $named->body());
        self::assertSame(200, $head->statusCode());
        self::assertSame('', $head->body());
        self::assertSame(500, $unconfigured->statusCode());
        self::assertSame('Unable to serve documentation.', $unconfigured->body());
    }

    public function testDocSurfaceReportsUnsupportedPathsWhenAskedDirectly(): void
    {
        $root = $this->createTemporaryDirectory();
        $this->writeDocument($root, 'index.md', '# Home');
        $surface = new DocSurface(Documentation::fromRoot($root), '/docs');

        $response = $surface->handle(new Request('GET', '/outside'));

        self::assertFalse($surface->supports(new Request('GET', '/outside')));
        self::assertSame(400, $response->statusCode());
        self::assertSame('Invalid documentation path.', $response->body());
    }

    public function testRegistryCanResolveNamedDocumentationSets(): void
    {
        $root = $this->createTemporaryDirectory();
        $this->writeDocument($root, 'guide.md', '# Guide');
        $documentation = Documentation::fromRoot($root, 'Manual', '/docs');
        $registry = DocumentationRegistry::single('manual', $documentation);

        [$resolved, $path, $name] = $registry->resolvePath('manual/guide');
        [$default, $defaultPath, $defaultName] = $registry->resolvePath('guide');

        self::assertSame($documentation, $resolved);
        self::assertSame('guide', $path);
        self::assertSame('manual', $name);
        self::assertSame($documentation, $default);
        self::assertSame('guide', $defaultPath);
        self::assertSame('manual', $defaultName);
    }
}
