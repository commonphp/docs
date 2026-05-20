# Package Boundaries

CommonPHP Docs owns documentation loading, navigation, rendering, registry selection, and HTTP surface adaptation.

## Belongs Here

- safe virtual document path normalization;
- Markdown file loading from configured roots;
- simple front matter parsing for document metadata;
- `DocumentPage` value objects;
- dependency-free Markdown-to-HTML conversion;
- generated navigation trees;
- documentation HTML rendering;
- `GET` and `HEAD` documentation responses;
- an HTTP surface for `/docs`-style mounts;
- service-provider definitions for default docs collaborators.

## Belongs Elsewhere

- full HTTP request creation and response emission belongs in `comphp/http`;
- route matching and controller dispatch belong in `comphp/router`;
- application pages and UI composition belong in `comphp/web` and `comphp/ui`;
- static asset resolution belongs in `comphp/assets`;
- authentication and authorization belong in `comphp/auth` and `comphp/security`;
- filesystem abstraction beyond Markdown roots belongs in `comphp/filesystem`;
- full static site generation, search indexing, and documentation publishing are outside this package.

## Design Rules

Docs objects should be simple to dump while debugging. A documentation path should always be a normalized virtual path, never an unchecked filesystem path.

The package may integrate with HTTP, Router, and Runtime, but it should remain usable without booting a kernel.
