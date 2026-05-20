# Getting Started

Use `Documentation::fromRoot()` when Markdown files live in one directory and should be served from one URL prefix.

```php
use CommonPHP\Docs\Documentation;

$docs = Documentation::fromRoot(
    root: __DIR__ . '/docs',
    title: 'Project Manual',
    basePath: '/docs',
);

$html = $docs->render('getting-started');
```

The loader accepts these file shapes for a requested document path:

- `/docs/index.md` for `/docs`
- `/docs/README.md` for `/docs`
- `/docs/guide.md` for `/docs/guide`
- `/docs/guide/index.md` for `/docs/guide`
- `/docs/guide/README.md` for `/docs/guide`

## Directory Example

```text
docs/
  index.md
  getting-started.md
  guide/
    index.md
    configuration.md
```

```php
$home = $docs->load('');
$configuration = $docs->load('guide/configuration');

echo $home->title();
echo $configuration->href('/docs');
```

## Front Matter

Pages may include small YAML-like front matter. The parser supports simple `key: value` lines only.

```markdown
---
title: Installation
navTitle: Install
order: 10
hidden: false
---
# Install CommonPHP Docs
```

Supported values include strings, numbers, booleans, and `null`.

## HTTP Surface

`DocSurface` adapts documentation to `CommonPHP\HTTP\Contracts\HttpSurfaceInterface`.

```php
use CommonPHP\Docs\DocSurface;

$surface = new DocSurface($docs, '/docs');

if ($surface->supports($request)) {
    $response = $surface->handle($request);
}
```
