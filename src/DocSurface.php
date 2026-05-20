<?php

declare(strict_types=1);

namespace CommonPHP\Docs;

use CommonPHP\Docs\Exceptions\DocsException;
use CommonPHP\Docs\Exceptions\DocumentNotFoundException;
use CommonPHP\Docs\Exceptions\InvalidDocumentPathException;
use CommonPHP\HTTP\Contracts\HttpSurfaceInterface;
use CommonPHP\HTTP\Enums\RequestMethod;
use CommonPHP\HTTP\Enums\ResponseStatus;
use CommonPHP\HTTP\Request;
use CommonPHP\HTTP\Response;

final class DocSurface implements HttpSurfaceInterface
{
    private DocumentationRegistry $registry;

    private string $pathPrefix;

    public function __construct(Documentation|DocumentationRegistry|null $documentation = null, string $pathPrefix = '/docs')
    {
        $this->registry = $documentation instanceof DocumentationRegistry
            ? $documentation
            : new DocumentationRegistry();
        $this->pathPrefix = Documentation::normalizeBasePath($pathPrefix);

        if ($documentation instanceof Documentation) {
            $this->registry->set('default', $documentation, true);
        }
    }

    public function supports(Request $request): bool
    {
        return $this->pathPrefix === '/'
            || $request->path() === $this->pathPrefix
            || str_starts_with($request->path(), $this->pathPrefix . '/');
    }

    public function handle(Request $request): Response
    {
        if (!in_array($request->method(), [RequestMethod::GET, RequestMethod::HEAD], true)) {
            return new Response('Method Not Allowed', ResponseStatus::METHOD_NOT_ALLOWED, [
                'Allow' => 'GET, HEAD',
                'Content-Type' => 'text/plain; charset=utf-8',
            ]);
        }

        try {
            $path = Documentation::pathFromRequest($request, $this->pathPrefix);
            [$documentation, $documentPath] = $this->registry->resolvePath($path);

            return $documentation->response($documentPath, $request);
        } catch (DocumentNotFoundException) {
            return $this->notFound($request, $path ?? '');
        } catch (InvalidDocumentPathException) {
            return new Response('Invalid documentation path.', ResponseStatus::BAD_REQUEST, [
                'Content-Type' => 'text/plain; charset=utf-8',
            ]);
        } catch (DocsException) {
            return new Response('Unable to serve documentation.', ResponseStatus::INTERNAL_SERVER_ERROR, [
                'Content-Type' => 'text/plain; charset=utf-8',
            ]);
        }
    }

    public function registry(): DocumentationRegistry
    {
        return $this->registry;
    }

    public function pathPrefix(): string
    {
        return $this->pathPrefix;
    }

    private function notFound(Request $request, string $path): Response
    {
        try {
            return $this->registry->get()->notFoundResponse($path, $request);
        } catch (DocsException) {
            return new Response('Documentation page not found.', ResponseStatus::NOT_FOUND, [
                'Content-Type' => 'text/plain; charset=utf-8',
            ]);
        }
    }
}
