# Runtime Integration

`DocsServiceProvider` registers the default docs collaborators with a CommonPHP Runtime container.

Registered definitions:

- `CommonPHP\Docs\Contracts\MarkdownConverterInterface`
- `CommonPHP\Docs\Contracts\DocumentationRendererInterface`
- `CommonPHP\Docs\Contracts\NavigationBuilderInterface`
- `CommonPHP\Docs\DocumentationRegistry`
- `CommonPHP\Docs\DocSurface`

## Service Provider

```php
use CommonPHP\Docs\DocsServiceProvider;

$kernel->useServiceProvider(new DocsServiceProvider());
```

The provider registers defaults, but it does not automatically register document roots. Applications should configure a `DocumentationRegistry` or `DocSurface` with their chosen roots.

## Module Pattern

A downstream module can configure documentation like any other runtime service.

```php
use CommonPHP\Docs\Documentation;
use CommonPHP\Docs\DocumentationRegistry;
use CommonPHP\Runtime\Contracts\ServiceProviderInterface;
use DI\ContainerBuilder;

use function DI\factory;

final class ManualModule implements ServiceProviderInterface
{
    public function configure(ContainerBuilder $builder): void
    {
        $builder->addDefinitions([
            DocumentationRegistry::class => factory(static function (): DocumentationRegistry {
                return DocumentationRegistry::single(
                    'manual',
                    Documentation::fromRoot(__DIR__ . '/../docs', 'Manual'),
                );
            }),
        ]);
    }
}
```

## HTTP Runtime

When the HTTP package owns execution, add `DocSurface` to the application surface registry or HTTP application configuration.

```php
$app->surface('docs', new DocSurface($registry, '/docs'), '/docs');
```
