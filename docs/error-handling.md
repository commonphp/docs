# Error Handling

CommonPHP Docs separates expected document misses from invalid setup or rendering failures.

## Exceptions

Base exception:

- `DocsException`

Specific exceptions:

- `DocumentNotFoundException`
- `DocumentParseException`
- `DocumentRenderException`
- `InvalidDocumentPathException`

## Loader Errors

`FilesystemDocumentLoader::load()` throws:

- `InvalidDocumentPathException` for unsafe paths or invalid roots;
- `DocumentNotFoundException` when no Markdown file exists;
- `DocumentParseException` when a file exists but cannot be read or parsed.

`exists()` catches these and returns `false`.

## Renderer Errors

`DocResponse::fromPage()` wraps renderer failures in `DocumentRenderException`.

```php
try {
    $response = $docs->response('guide/install');
} catch (DocumentRenderException $exception) {
    // Log renderer failure.
}
```

## HTTP Surface Errors

`DocSurface` converts package exceptions to HTTP responses:

- missing documents become `404`;
- invalid paths become `400`;
- unsupported methods become `405`;
- unresolved registry or generic docs failures become `500`.

Direct use of `Documentation` keeps exceptions available to the caller.
