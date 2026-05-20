# Markdown Rendering

`MarkdownConverter` provides a dependency-free Markdown subset. It is designed for package docs and application manuals where predictable escaping is more important than supporting every Markdown extension.

## Supported Blocks

- headings from `#` through `######`
- paragraphs
- unordered lists using `-`, `*`, or `+`
- ordered lists using `1.` or `1)`
- blockquotes
- fenced code blocks
- horizontal rules using `---`

## Supported Inline Markup

- backtick code
- links
- images
- strong text with `**text**` or `__text__`
- emphasis with `*text*` or `_text_`

## Escaping

Input text is escaped before inline markup is applied.

```php
use CommonPHP\Docs\MarkdownConverter;

$html = (new MarkdownConverter())->toHtml('# <Safe>');
```

The heading text is rendered as escaped text, and the generated anchor is deterministic.

## Custom Conversion

Use `MarkdownConverterInterface` when an application needs a richer parser.

```php
use CommonPHP\Docs\Contracts\MarkdownConverterInterface;

final class CommonMarkConverter implements MarkdownConverterInterface
{
    public function convert(string $markdown): string
    {
        return $this->toHtml($markdown);
    }

    public function toHtml(string $markdown): string
    {
        return $this->commonMark->convert($markdown);
    }
}
```

Then pass it into `DocumentationRenderer`.

```php
use CommonPHP\Docs\DocumentationRenderer;

$docs->useRenderer(new DocumentationRenderer(new CommonMarkConverter()));
```
