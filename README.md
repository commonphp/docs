# CommonPHP Docs

CommonPHP Docs provides Markdown-powered documentation rendering for CommonPHP applications. It turns application, package, or module documentation into browsable documentation pages with predictable navigation.

The package helps CommonPHP applications ship useful documentation alongside the code that provides the behavior.

## Requirements

- PHP `^8.5`
- `comphp/runtime:^0.3`

## Installation

Once this package is available through your Composer repositories, install it with:

```bash
composer require comphp/docs
```

## Usage

```php
<?php

// TODO: Write usage
```

## Package Notes

This package should render Markdown documentation, build navigation trees, and expose documentation through the appropriate HTTP/web integration. It should not own the full router or HTTP stack.

## Error Handling

Missing documents, invalid paths, parse failures, and rendering errors should throw CommonPHP docs exceptions or return appropriate HTTP responses through the docs surface.

## Documentation

- [Documentation index](docs/index.md)
- [Usage](docs/usage.md)
- [Testing](TESTING.md)
- [Contributing](CONTRIBUTING.md)
- [Security](SECURITY.md)

## License

MIT. See [LICENSE.md](LICENSE.md).
