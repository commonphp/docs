# Custom Renderer

Replace the renderer when an application needs a different layout, CSS, Markdown library, or template engine.

```php
use CommonPHP\Docs\Contracts\DocumentationRendererInterface;
use CommonPHP\Docs\DocumentPage;
use CommonPHP\Docs\Navigation;

final class AppDocumentationRenderer implements DocumentationRendererInterface
{
    public function render(DocumentPage $page, ?Navigation $navigation = null, ?string $title = null): string
    {
        return $this->layout->render('docs/page.php', [
            'siteTitle' => $title ?? 'Documentation',
            'page' => $page,
            'navigation' => $navigation,
        ]);
    }

    public function renderNotFound(string $path = '', ?string $title = null): string
    {
        return $this->layout->render('docs/not-found.php', [
            'siteTitle' => $title ?? 'Documentation',
            'path' => $path,
        ]);
    }
}
```

```php
$docs->useRenderer(new AppDocumentationRenderer($layout));
```

`DocResponse::fromPage()` still wraps renderer failures in `DocumentRenderException`, so callers can handle rendering failures consistently.
