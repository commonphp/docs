<?php

declare(strict_types=1);

namespace CommonPHP\Docs;

use CommonPHP\HTTP\Contracts\HttpSurfaceInterface;
use CommonPHP\HTTP\Request;
use CommonPHP\HTTP\Response;
use CommonPHP\HTTP\ResponseFactory;

class DocSurface implements HttpSurfaceInterface
{
    public function supports(Request $request): bool
    {
        return $request->path() === '/docs' || str_starts_with($request->path(), '/docs/');
    }

    public function handle(Request $request): Response
    {
        return (new ResponseFactory())->html('<h1>Documentation page not found</h1>', 404);
    }
}
