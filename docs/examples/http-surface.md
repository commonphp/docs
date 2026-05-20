# HTTP Surface

```php
use CommonPHP\Docs\DocSurface;
use CommonPHP\Docs\Documentation;
use CommonPHP\HTTP\Request;

$docs = Documentation::fromRoot(__DIR__ . '/../docs', 'Project Manual', '/docs');
$surface = new DocSurface($docs, '/docs');

$request = new Request('GET', '/docs/getting-started');
$response = $surface->handle($request);

echo $response->statusCode();
echo $response->body();
```

## HEAD Requests

```php
$response = $surface->handle(new Request('HEAD', '/docs/getting-started'));

echo $response->header('Content-Length');
echo $response->body(); // empty string
```
