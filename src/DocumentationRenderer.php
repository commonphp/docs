<?php

declare(strict_types=1);

namespace CommonPHP\Docs;

use CommonPHP\Docs\Contracts\DocumentationRendererInterface;
use CommonPHP\Docs\Contracts\MarkdownConverterInterface;

final class DocumentationRenderer implements DocumentationRendererInterface
{
    public function __construct(
        private readonly MarkdownConverterInterface $markdown = new MarkdownConverter(),
    ) {
    }

    public function render(DocumentPage $page, ?Navigation $navigation = null, ?string $title = null): string
    {
        $siteTitle = $title === null || trim($title) === '' ? 'Documentation' : trim($title);
        $documentTitle = $page->title();
        $body = $this->markdown->toHtml($page->markdown());
        $navigationHtml = $navigation?->toHtml() ?? '';
        $updatedAt = $page->modifiedAt() === null
            ? ''
            : '<p class="docs-updated">Updated ' . $this->escape(gmdate('Y-m-d H:i', $page->modifiedAt())) . ' UTC</p>';

        return $this->layout(
            $siteTitle,
            $documentTitle,
            $navigationHtml,
            '<article class="docs-content">' . $body . $updatedAt . '</article>',
        );
    }

    public function renderNotFound(string $path = '', ?string $title = null): string
    {
        $siteTitle = $title === null || trim($title) === '' ? 'Documentation' : trim($title);
        $path = $path === '' ? 'index' : $path;

        return $this->layout(
            $siteTitle,
            'Documentation page not found',
            '',
            '<article class="docs-content"><h1>Documentation page not found</h1><p>No documentation page exists for <code>'
                . $this->escape($path) . '</code>.</p></article>',
        );
    }

    private function layout(string $siteTitle, string $pageTitle, string $navigation, string $content): string
    {
        $title = $pageTitle === $siteTitle ? $siteTitle : $pageTitle . ' - ' . $siteTitle;

        return '<!doctype html>'
            . '<html lang="en">'
            . '<head>'
            . '<meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>' . $this->escape($title) . '</title>'
            . '<style>' . $this->styles() . '</style>'
            . '</head>'
            . '<body>'
            . '<header class="docs-header"><div><strong>' . $this->escape($siteTitle) . '</strong></div></header>'
            . '<div class="docs-shell">'
            . ($navigation === '' ? '' : '<aside class="docs-sidebar">' . $navigation . '</aside>')
            . '<main class="docs-main">' . $content . '</main>'
            . '</div>'
            . '</body>'
            . '</html>';
    }

    private function styles(): string
    {
        return <<<'CSS'
:root{color-scheme:light;--docs-bg:#f7f8fa;--docs-panel:#fff;--docs-text:#1f2937;--docs-muted:#687385;--docs-border:#d8dde6;--docs-link:#0f5ca8;--docs-code:#f0f3f7}
*{box-sizing:border-box}
body{margin:0;background:var(--docs-bg);color:var(--docs-text);font:16px/1.6 system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}
a{color:var(--docs-link);text-decoration:none}
a:hover{text-decoration:underline}
.docs-header{position:sticky;top:0;z-index:2;border-bottom:1px solid var(--docs-border);background:rgba(255,255,255,.96);padding:14px 24px}
.docs-shell{display:grid;grid-template-columns:minmax(220px,280px) minmax(0,1fr);gap:28px;max-width:1180px;margin:0 auto;padding:28px 24px}
.docs-sidebar{border-right:1px solid var(--docs-border);padding-right:18px}
.docs-nav ul{list-style:none;margin:0;padding-left:0}
.docs-nav ul ul{border-left:1px solid var(--docs-border);margin:4px 0 8px 12px;padding-left:12px}
.docs-nav a{display:block;border-radius:6px;padding:5px 7px;color:var(--docs-muted)}
.docs-nav .is-active>a{background:#eef5fc;color:var(--docs-link)}
.docs-nav .is-current>a{font-weight:700}
.docs-main{min-width:0}
.docs-content{max-width:860px;background:var(--docs-panel);border:1px solid var(--docs-border);border-radius:8px;padding:32px}
.docs-content h1,.docs-content h2,.docs-content h3{line-height:1.25;margin:1.6em 0 .5em}
.docs-content h1:first-child,.docs-content h2:first-child,.docs-content h3:first-child{margin-top:0}
.docs-content p,.docs-content ul,.docs-content ol,.docs-content blockquote,.docs-content pre{margin:0 0 1em}
.docs-content code{background:var(--docs-code);border-radius:4px;padding:2px 4px}
.docs-content pre{overflow:auto;background:#111827;color:#f8fafc;border-radius:8px;padding:16px}
.docs-content pre code{background:transparent;padding:0}
.docs-content blockquote{border-left:4px solid var(--docs-border);color:var(--docs-muted);padding-left:16px}
.docs-content img{max-width:100%;height:auto}
.docs-updated{color:var(--docs-muted);font-size:14px;margin-top:32px}
@media (max-width:800px){.docs-shell{display:block;padding:18px}.docs-sidebar{border-right:0;border-bottom:1px solid var(--docs-border);margin-bottom:18px;padding:0 0 18px}.docs-content{padding:22px}}
CSS;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
