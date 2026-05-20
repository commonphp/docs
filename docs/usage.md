# Usage

CommonPHP Docs has four common usage styles:

- direct `Documentation` loading and rendering;
- `DocSurface` for HTTP serving;
- `DocumentationRegistry` for multiple documentation sets;
- route registration when an application already uses `comphp/router`.

## Direct Rendering

```php
use CommonPHP\Docs\Documentation;

$docs = Documentation::fromRoot(__DIR__ . '/docs', 'Project Manual');

$page = $docs->load('guide/install');
$navigation = $docs->navigation($page->path());
$html = $docs->render($page->path());
```

## Responses

`Documentation::response()` returns a `DocResponse`, which extends `CommonPHP\HTTP\Response`.

```php
$response = $docs->response('guide/install', $request);

echo $response->statusCode();
echo $response->header('Content-Type');
```

`HEAD` requests omit the body while preserving the computed `Content-Length`.

## Route Registration

When `comphp/router` is already in use, `Documentation::registerRoutes()` adds an index route and a wildcard page route.

```php
use CommonPHP\Router\Router;

$router = new Router();

$docs->registerRoutes($router, '/manual');

$response = $router->dispatch($request);
```

The registered names are:

- `docs.index`
- `docs.page`

## Multiple Roots

`FilesystemDocumentLoader` can load from several roots. String array keys become path prefixes.

```php
use CommonPHP\Docs\FilesystemDocumentLoader;

$loader = new FilesystemDocumentLoader([
    __DIR__ . '/docs',
    'packages/runtime' => __DIR__ . '/package/runtime/docs',
]);

$docs = new Documentation($loader, title: 'All Docs');
```

## Replacing Collaborators

The package is built around small contracts. Replace collaborators when an application needs another storage backend, renderer, Markdown converter, or navigation policy.

```php
$docs
    ->useLoader($loader)
    ->useRenderer($renderer)
    ->useNavigationBuilder($navigationBuilder)
    ->useTitle('Internal Docs')
    ->useBasePath('/internal/docs');
```
