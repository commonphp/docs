# Registries

`DocumentationRegistry` lets one `DocSurface` serve multiple documentation sets.

```php
use CommonPHP\Docs\DocumentationRegistry;

$registry = DocumentationRegistry::single('manual', $manualDocs)
    ->register('api', $apiDocs);
```

## Named Resolution

When the first path segment matches a registry name, that set is selected and the remaining path is loaded from it.

```php
[$docs, $path, $name] = $registry->resolvePath('api/reference');

// $name is "api"
// $path is "reference"
```

When no named set matches, the default documentation set is used.

```php
[$docs, $path, $name] = $registry->resolvePath('guide/install');
```

## Default Set

The first registered set becomes the default unless another set is registered or set as default.

```php
$registry->setDefault('manual');
```

Removing the default moves the default to the first remaining registered set.

## Name Normalization

Names are lowercased and normalized to URL-safe identifiers.

```php
DocumentationRegistry::normalizeName('Manual Docs v1!');
// manual-docs-v1
```

Empty normalized names throw `DocsException`.
