# Documents And Loaders

`DocumentPage` is the core value object. `DocumentLoaderInterface` is the storage contract.

## Document Paths

Document paths are virtual paths, not filesystem paths. They are normalized with these rules:

- leading and duplicate slashes are removed;
- backslashes are converted to slashes;
- query strings are ignored;
- `.md` is removed from the final segment;
- trailing `index` and `README` segments collapse to their parent;
- path traversal, null bytes, URLs, and schemes are rejected.

Examples:

| Input | Normalized document path |
| --- | --- |
| `/guide/install.md?ref=1` | `guide/install` |
| `guide/index.md` | `guide` |
| `README.md` | empty string |
| `../secret` | invalid |

## Filesystem Loader

```php
use CommonPHP\Docs\FilesystemDocumentLoader;

$loader = new FilesystemDocumentLoader(__DIR__ . '/docs');

$page = $loader->load('guide/install');
```

Accepted file candidates for `guide/install`:

- `guide/install.md`
- `guide/install/index.md`
- `guide/install/README.md`

For the root page:

- `index.md`
- `README.md`

## Multiple Roots And Prefixes

```php
$loader = new FilesystemDocumentLoader([
    __DIR__ . '/docs',
    'runtime' => __DIR__ . '/package/runtime/docs',
]);

$loader->load('runtime/kernel');
```

The prefix becomes part of the virtual document path.

## Front Matter

`DocumentPage::fromMarkdown()` removes simple front matter and stores values in metadata.

```markdown
---
title: Kernel
navTitle: Runtime Kernel
order: 20
hidden: false
---
# Kernel
```

Reserved metadata keys used by built-in behavior:

- `title`
- `navTitle`
- `nav_title`
- `order`
- `hidden`
- `slug`

All metadata stays available through `metadata()`, `meta()`, and read-only array access.
