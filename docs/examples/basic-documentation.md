# Basic Documentation

```php
use CommonPHP\Docs\Documentation;

$docs = Documentation::fromRoot(
    root: __DIR__ . '/../docs',
    title: 'Project Manual',
    basePath: '/docs',
);

$page = $docs->load('getting-started');

echo $page->title();
echo $docs->render($page->path());
```

## Directory

```text
docs/
  index.md
  getting-started.md
  guide/
    configuration.md
```

## Page Metadata

```markdown
---
title: Getting Started
navTitle: Start
order: 10
---
# Getting Started
```
