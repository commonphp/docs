# HTTP Surface

`DocSurface` serves documentation through `CommonPHP\HTTP\Contracts\HttpSurfaceInterface`.

```php
use CommonPHP\Docs\DocSurface;
use CommonPHP\Docs\Documentation;

$docs = Documentation::fromRoot(__DIR__ . '/docs', 'Project Manual', '/docs');
$surface = new DocSurface($docs, '/docs');
```

## Supported Methods

`GET` returns rendered HTML.

`HEAD` returns headers without the body.

Other methods return `405 Method Not Allowed` with:

```text
Allow: GET, HEAD
```

## Path Prefixes

```php
$surface = new DocSurface($docs, '/manual');
```

The surface supports:

- `/manual`
- `/manual/guide`
- `/manual/guide/install`

It does not support `/other`, although calling `handle()` directly with an outside path returns a bad request response.

## Response Mapping

| Condition | Status |
| --- | --- |
| Existing document | `200 OK` |
| Missing document | `404 Not Found` |
| Invalid path | `400 Bad Request` |
| Unsupported method | `405 Method Not Allowed` |
| Registry or rendering failure | `500 Internal Server Error` |

## Surface Registry

When used with `comphp/http`, register the surface with the HTTP surface registry.

```php
$surfaceRegistry->register('docs', $surface, '/docs');
```
