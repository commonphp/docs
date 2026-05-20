# CommonPHP Docs Documentation

CommonPHP Docs is the documentation loading, navigation, rendering, and HTTP surface package for CommonPHP applications. It turns Markdown files into `DocumentPage` objects, builds predictable navigation, renders simple HTML pages, and exposes documentation over the CommonPHP HTTP surface model.

The package is intentionally small. It does not own routing, the full HTTP application, template engines, asset publishing, authentication, authorization, search indexing, or static site generation.

## Start Here

- [Getting started](getting-started.md)
- [Usage](usage.md)
- [Architecture](architecture.md)
- [Package boundaries](package-boundaries.md)

## Docs Concepts

- [Documents and loaders](documents-and-loaders.md)
- [Markdown rendering](markdown-rendering.md)
- [Navigation](navigation.md)
- [HTTP surface](http-surface.md)
- [Registries](registries.md)
- [Runtime integration](runtime-integration.md)
- [Error handling](error-handling.md)

## Examples

- [Examples index](examples/index.md)
- [Basic documentation](examples/basic-documentation.md)
- [HTTP surface](examples/http-surface.md)
- [Multiple documentation sets](examples/multiple-docsets.md)
- [Custom renderer](examples/custom-renderer.md)

## Development

- [Testing and QA](testing.md)

## Public API Map

Entry points:

- `CommonPHP\Docs\Documentation`
- `CommonPHP\Docs\DocSurface`
- `CommonPHP\Docs\DocsServiceProvider`

Document loading:

- `CommonPHP\Docs\DocumentPage`
- `CommonPHP\Docs\FilesystemDocumentLoader`
- `CommonPHP\Docs\Contracts\DocumentLoaderInterface`

Rendering:

- `CommonPHP\Docs\MarkdownConverter`
- `CommonPHP\Docs\DocumentationRenderer`
- `CommonPHP\Docs\DocResponse`
- `CommonPHP\Docs\Contracts\MarkdownConverterInterface`
- `CommonPHP\Docs\Contracts\DocumentationRendererInterface`

Navigation:

- `CommonPHP\Docs\Navigation`
- `CommonPHP\Docs\NavigationItem`
- `CommonPHP\Docs\NavigationBuilder`
- `CommonPHP\Docs\Contracts\NavigationBuilderInterface`

Registries:

- `CommonPHP\Docs\DocumentationRegistry`

Exceptions:

- `CommonPHP\Docs\Exceptions\DocsException`
- `CommonPHP\Docs\Exceptions\DocumentNotFoundException`
- `CommonPHP\Docs\Exceptions\DocumentParseException`
- `CommonPHP\Docs\Exceptions\DocumentRenderException`
- `CommonPHP\Docs\Exceptions\InvalidDocumentPathException`
