# Multiple Documentation Sets

Use `DocumentationRegistry` when one mount should serve more than one manual.

```php
use CommonPHP\Docs\DocSurface;
use CommonPHP\Docs\Documentation;
use CommonPHP\Docs\DocumentationRegistry;

$registry = DocumentationRegistry::single(
    'manual',
    Documentation::fromRoot(__DIR__ . '/../docs/manual', 'Manual'),
);

$registry->register(
    'api',
    Documentation::fromRoot(__DIR__ . '/../docs/api', 'API Reference'),
);

$surface = new DocSurface($registry, '/docs');
```

Requests resolve as:

| Request path | Documentation set | Document path |
| --- | --- | --- |
| `/docs` | `manual` | empty string |
| `/docs/guide/install` | `manual` | `guide/install` |
| `/docs/api/reference` | `api` | `reference` |

The first registered documentation set is the default unless another set is registered or selected as default.
