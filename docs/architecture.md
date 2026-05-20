# Architecture

CommonPHP Docs is a small composition package. It keeps the major responsibilities separate so documentation behavior is easy to understand and replace.

## Flow

The normal request flow is:

1. `DocSurface::supports()` checks the configured URL prefix.
2. `DocSurface::handle()` accepts only `GET` and `HEAD`.
3. `Documentation::pathFromRequest()` turns the request path into a safe document path.
4. `DocumentationRegistry::resolvePath()` chooses a documentation set.
5. `Documentation::response()` asks the loader for a `DocumentPage`.
6. `NavigationBuilder::build()` creates navigation for all visible pages.
7. `DocumentationRenderer::render()` converts Markdown to HTML and wraps it in a simple page layout.
8. `DocResponse::fromPage()` returns an HTTP response with content headers.

## Main Object Roles

| Object | Responsibility |
| --- | --- |
| `Documentation` | Coordinates loading, navigation, rendering, responses, and route registration |
| `FilesystemDocumentLoader` | Loads Markdown files from one or more safe roots |
| `DocumentPage` | Holds normalized path, Markdown body, title, metadata, timestamps, and heading data |
| `MarkdownConverter` | Converts a practical subset of Markdown into escaped HTML |
| `NavigationBuilder` | Builds nested navigation from visible pages |
| `Navigation` | Holds navigation items and renders navigation HTML |
| `DocumentationRenderer` | Builds the full HTML document |
| `DocResponse` | Adapts rendered documentation into `CommonPHP\HTTP\Response` |
| `DocumentationRegistry` | Selects one documentation set from many |
| `DocSurface` | Serves documentation through the CommonPHP HTTP surface contract |
| `DocsServiceProvider` | Registers default collaborators in a Runtime container |

## Design Choices

The package favors straightforward defaults:

- filesystem storage is rooted and traversal-safe;
- Markdown rendering is dependency-free and escaped by default;
- navigation is generated from known documents, not from hand-written config;
- rendering is replaceable through `DocumentationRendererInterface`;
- HTTP behavior returns normal `Response` objects and stays outside the full HTTP kernel.

## Current Limits

The built-in Markdown converter is intentionally modest. Applications that need full CommonMark, syntax highlighting, tables, task lists, or extensions should provide a custom `MarkdownConverterInterface` or a custom `DocumentationRendererInterface`.
