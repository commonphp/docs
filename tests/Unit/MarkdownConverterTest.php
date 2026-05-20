<?php

declare(strict_types=1);

namespace CommonPHP\Docs\Tests\Unit;

use CommonPHP\Docs\Contracts\MarkdownConverterInterface;
use CommonPHP\Docs\MarkdownConverter;
use PHPUnit\Framework\TestCase;

final class MarkdownConverterTest extends TestCase
{
    public function testItConvertsCommonMarkdownBlocksAndInlineMarkup(): void
    {
        $converter = new MarkdownConverter();

        $html = $converter->toHtml(<<<'MD'
# Hello **Docs**

Paragraph with `code` and [a link](/docs).

- One
- Two

1. First
2. Second

> Useful note.

```php
<?php echo "safe";
```
MD);

        self::assertInstanceOf(MarkdownConverterInterface::class, $converter);
        self::assertStringContainsString('<h1 id="hello-docs">Hello <strong>Docs</strong></h1>', $html);
        self::assertStringContainsString('<p>Paragraph with <code>code</code> and <a href="/docs">a link</a>.</p>', $html);
        self::assertStringContainsString('<ul><li>One</li><li>Two</li></ul>', $html);
        self::assertStringContainsString('<ol><li>First</li><li>Second</li></ol>', $html);
        self::assertStringContainsString('<blockquote><p>Useful note.</p></blockquote>', $html);
        self::assertStringContainsString('<pre><code class="language-php">&lt;?php echo "safe";</code></pre>', $html);
    }

    public function testAnchorIdsArePredictable(): void
    {
        self::assertSame('api-reference-v2', MarkdownConverter::anchorId('API Reference (v2)'));
        self::assertSame('section', MarkdownConverter::anchorId('!!!'));
    }

    public function testItEscapesHtmlAndConvertsImagesHorizontalRulesAndUnclosedCodeBlocks(): void
    {
        $html = (new MarkdownConverter())->convert(<<<'MD'
<script>alert(1)</script>

![Logo](/assets/logo.svg)

---

```php!!!
<danger>
MD);

        self::assertStringContainsString('<p>&lt;script&gt;alert(1)&lt;/script&gt;</p>', $html);
        self::assertStringContainsString('<p><img src="/assets/logo.svg" alt="Logo"></p>', $html);
        self::assertStringContainsString('<hr>', $html);
        self::assertStringContainsString('<pre><code class="language-php">&lt;danger&gt;</code></pre>', $html);
    }

    public function testItFlushesDifferentListTypesSeparately(): void
    {
        $html = (new MarkdownConverter())->toHtml("- One\n1. Two\n- Three");

        self::assertSame("<ul><li>One</li></ul>\n<ol><li>Two</li></ol>\n<ul><li>Three</li></ul>", $html);
    }

    public function testEmptyMarkdownReturnsEmptyHtml(): void
    {
        self::assertSame('', (new MarkdownConverter())->toHtml('   '));
    }
}
