<?php

declare(strict_types=1);

namespace CommonPHP\Docs;

use CommonPHP\Docs\Contracts\DocumentLoaderInterface;
use CommonPHP\Docs\Contracts\DocumentationRendererInterface;
use CommonPHP\Docs\Contracts\NavigationBuilderInterface;
use CommonPHP\Docs\Exceptions\InvalidDocumentPathException;
use CommonPHP\HTTP\Enums\RequestMethod;
use CommonPHP\HTTP\Request;
use CommonPHP\Router\RouteCollection;
use CommonPHP\Router\RouteMatch;
use CommonPHP\Router\Router;

final class Documentation
{
    public function __construct(
        private DocumentLoaderInterface $loader,
        private DocumentationRendererInterface $renderer = new DocumentationRenderer(),
        private NavigationBuilderInterface $navigationBuilder = new NavigationBuilder(),
        private string $title = 'Documentation',
        private string $basePath = '/docs',
    ) {
        $this->title = trim($title) === '' ? 'Documentation' : trim($title);
        $this->basePath = self::normalizeBasePath($basePath);
    }

    public static function fromRoot(
        string $root,
        string $title = 'Documentation',
        string $basePath = '/docs',
        string $prefix = '',
    ): self {
        return new self(FilesystemDocumentLoader::fromRoot($root, $prefix), title: $title, basePath: $basePath);
    }

    public function useLoader(DocumentLoaderInterface $loader): self
    {
        $this->loader = $loader;

        return $this;
    }

    public function loader(): DocumentLoaderInterface
    {
        return $this->loader;
    }

    public function useRenderer(DocumentationRendererInterface $renderer): self
    {
        $this->renderer = $renderer;

        return $this;
    }

    public function renderer(): DocumentationRendererInterface
    {
        return $this->renderer;
    }

    public function useNavigationBuilder(NavigationBuilderInterface $navigationBuilder): self
    {
        $this->navigationBuilder = $navigationBuilder;

        return $this;
    }

    public function navigationBuilder(): NavigationBuilderInterface
    {
        return $this->navigationBuilder;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function useTitle(string $title): self
    {
        $this->title = trim($title) === '' ? 'Documentation' : trim($title);

        return $this;
    }

    public function basePath(): string
    {
        return $this->basePath;
    }

    public function useBasePath(string $basePath): self
    {
        $this->basePath = self::normalizeBasePath($basePath);

        return $this;
    }

    public function load(string $path = ''): DocumentPage
    {
        return $this->loader->load($path);
    }

    public function exists(string $path = ''): bool
    {
        return $this->loader->exists($path);
    }

    /**
     * @return list<DocumentPage>
     */
    public function pages(): array
    {
        return $this->loader->all();
    }

    /**
     * @return list<string>
     */
    public function paths(): array
    {
        return $this->loader->paths();
    }

    public function navigation(?string $currentPath = null): Navigation
    {
        return $this->navigationBuilder->build($this->pages(), $this->basePath, $currentPath);
    }

    public function render(string $path = ''): string
    {
        $page = $this->load($path);

        return $this->renderer->render($page, $this->navigation($page->path()), $this->title);
    }

    public function response(string $path = '', ?Request $request = null): DocResponse
    {
        $page = $this->load($path);
        $includeBody = $request === null || $request->method() !== RequestMethod::HEAD;

        return DocResponse::fromPage($page, $this->renderer, $this->navigation($page->path()), $this->title, $includeBody);
    }

    public function notFoundResponse(string $path = '', ?Request $request = null): DocResponse
    {
        $includeBody = $request === null || $request->method() !== RequestMethod::HEAD;

        return DocResponse::notFound($path, $this->renderer, $this->title, $includeBody);
    }

    public function registerRoutes(RouteCollection|Router $routes, ?string $basePath = null): self
    {
        $basePath = self::normalizeBasePath($basePath ?? $this->basePath);
        $collection = $routes instanceof Router ? $routes->routes() : $routes;
        $handler = function (Request $request, RouteMatch $match): DocResponse {
            return $this->response((string) $match->parameter('path', ''), $request);
        };

        $collection->get($basePath, $handler, 'docs.index');
        $collection->get(rtrim($basePath, '/') . '/{path*}', $handler, 'docs.page');

        return $this;
    }

    public static function normalizeBasePath(string $basePath): string
    {
        $basePath = trim($basePath);

        if ($basePath === '' || $basePath === '/') {
            return '/';
        }

        return '/' . FilesystemDocumentLoader::normalizePath($basePath);
    }

    public static function pathFromRequest(Request $request, string $basePath = '/docs'): string
    {
        $basePath = self::normalizeBasePath($basePath);

        if ($basePath === '/') {
            return FilesystemDocumentLoader::normalizeDocumentPath($request->path(), true);
        }

        $path = rtrim($request->path(), '/');

        if ($path === $basePath) {
            return '';
        }

        if (str_starts_with($request->path(), $basePath . '/')) {
            return FilesystemDocumentLoader::normalizeDocumentPath(substr($request->path(), strlen($basePath) + 1), true);
        }

        throw InvalidDocumentPathException::forPath($request->path(), 'request path is outside mount "' . $basePath . '"');
    }
}
