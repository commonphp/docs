# Testing And QA

CommonPHP Docs includes a package-local PHPUnit configuration and unit tests.

## Run PHPUnit

From the monorepo root:

```bash
vendor/bin/phpunit -c package/docs/phpunit.xml.dist
```

From `package/docs`:

```bash
../../vendor/bin/phpunit -c phpunit.xml.dist
```

The package bootstrap checks both package and workspace autoloaders.

## Current Test Coverage

The unit suite covers:

- `DocumentPage` path normalization, titles, front matter parsing, metadata, hidden flags, ordering, headings, href generation, serialization, string conversion, and read-only array access;
- `FilesystemDocumentLoader` root setup, prefixed roots, multiple roots, index and README fallback, path listing, document ordering, safe path normalization, unsafe paths, missing files, parse failures, invalid roots, and external URL detection;
- `MarkdownConverter` headings, anchors, paragraphs, links, images, emphasis, strong text, code spans, code blocks, list switching, blockquotes, horizontal rules, HTML escaping, empty documents, and unclosed fences;
- `NavigationBuilder`, `Navigation`, and `NavigationItem` nesting, synthetic parents, hidden pages, active/current states, root base paths, iteration, flattening, arrays, HTML escaping, and empty navigation;
- `Documentation` loading, collaborator swapping, base path normalization, request path extraction, rendering, responses, not-found responses, route registration, and router dispatch;
- `DocumentationRegistry` registration, default selection, name normalization, named path resolution, removal, clearing, iteration, missing values, duplicate names, and invalid constructors;
- `DocumentationRenderer` page layout, navigation inclusion, default titles, HTML escaping, not-found rendering, and updated timestamps;
- `DocResponse` content headers, page references, not-found responses, omitted bodies for `HEAD`, and render failure wrapping;
- `DocSurface` support checks, root and nested mounts, registry-backed serving, named documentation sets, method restrictions, missing documents, invalid paths, and unconfigured registry failures;
- `DocsServiceProvider` Runtime service definitions;
- package exception factory messages and previous exception preservation.

## Manual Review Areas

Manual review should still cover:

- visual polish of rendered documentation in real applications;
- custom Markdown converters;
- application-specific authorization around docs routes;
- integration with HTTP surface ordering when several surfaces share similar prefixes.
