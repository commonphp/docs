<?php

declare(strict_types=1);

namespace CommonPHP\Docs;

use CommonPHP\Docs\Contracts\MarkdownConverterInterface;

final class MarkdownConverter implements MarkdownConverterInterface
{
    public function convert(string $markdown): string
    {
        return $this->toHtml($markdown);
    }

    public function toHtml(string $markdown): string
    {
        $lines = preg_split('/\R/', trim($markdown)) ?: [];
        $html = [];
        $paragraph = [];
        $list = null;
        $blockquote = [];
        $inCode = false;
        $code = [];
        $language = '';

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if (str_starts_with($trimmed, '```')) {
                if ($inCode) {
                    $html[] = $this->renderCodeBlock($code, $language);
                    $code = [];
                    $language = '';
                    $inCode = false;
                } else {
                    $this->flushParagraph($html, $paragraph);
                    $this->flushList($html, $list);
                    $this->flushBlockquote($html, $blockquote);
                    $language = trim(substr($trimmed, 3));
                    $inCode = true;
                }

                continue;
            }

            if ($inCode) {
                $code[] = $line;
                continue;
            }

            if ($trimmed === '') {
                $this->flushParagraph($html, $paragraph);
                $this->flushList($html, $list);
                $this->flushBlockquote($html, $blockquote);
                continue;
            }

            if (preg_match('/^---+$/', $trimmed) === 1) {
                $this->flushParagraph($html, $paragraph);
                $this->flushList($html, $list);
                $this->flushBlockquote($html, $blockquote);
                $html[] = '<hr>';
                continue;
            }

            if (preg_match('/^(#{1,6})\s+(.+?)\s*#*$/', $trimmed, $matches) === 1) {
                $this->flushParagraph($html, $paragraph);
                $this->flushList($html, $list);
                $this->flushBlockquote($html, $blockquote);
                $level = strlen($matches[1]);
                $text = trim($matches[2]);
                $id = self::anchorId($text);
                $html[] = '<h' . $level . ' id="' . $id . '">' . $this->inline($text) . '</h' . $level . '>';
                continue;
            }

            if (preg_match('/^>\s?(.*)$/', $trimmed, $matches) === 1) {
                $this->flushParagraph($html, $paragraph);
                $this->flushList($html, $list);
                $blockquote[] = $matches[1];
                continue;
            }

            if (preg_match('/^[-*+]\s+(.+)$/', $trimmed, $matches) === 1) {
                $this->flushParagraph($html, $paragraph);
                $this->flushBlockquote($html, $blockquote);
                if ($list !== null && $list['type'] !== 'ul') {
                    $this->flushList($html, $list);
                }
                $this->appendListItem($list, 'ul', $matches[1]);
                continue;
            }

            if (preg_match('/^\d+[.)]\s+(.+)$/', $trimmed, $matches) === 1) {
                $this->flushParagraph($html, $paragraph);
                $this->flushBlockquote($html, $blockquote);
                if ($list !== null && $list['type'] !== 'ol') {
                    $this->flushList($html, $list);
                }
                $this->appendListItem($list, 'ol', $matches[1]);
                continue;
            }

            $this->flushList($html, $list);
            $this->flushBlockquote($html, $blockquote);
            $paragraph[] = $trimmed;
        }

        if ($inCode) {
            $html[] = $this->renderCodeBlock($code, $language);
        }

        $this->flushParagraph($html, $paragraph);
        $this->flushList($html, $list);
        $this->flushBlockquote($html, $blockquote);

        return implode("\n", $html);
    }

    public static function anchorId(string $text): string
    {
        $id = strtolower(html_entity_decode($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        $id = preg_replace('/[^a-z0-9]+/i', '-', $id) ?? '';
        $id = trim($id, '-');

        return $id === '' ? 'section' : $id;
    }

    private function inline(string $text): string
    {
        $escaped = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $code = [];
        $escaped = preg_replace_callback('/`([^`]+)`/', static function (array $matches) use (&$code): string {
            $key = "\0CODE" . count($code) . "\0";
            $code[$key] = '<code>' . $matches[1] . '</code>';

            return $key;
        }, $escaped) ?? $escaped;

        $escaped = preg_replace('/!\[([^\]]*)]\(([^)\s]+)\)/', '<img src="$2" alt="$1">', $escaped) ?? $escaped;
        $escaped = preg_replace('/\[([^\]]+)]\(([^)\s]+)\)/', '<a href="$2">$1</a>', $escaped) ?? $escaped;
        $escaped = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $escaped) ?? $escaped;
        $escaped = preg_replace('/__(.+?)__/s', '<strong>$1</strong>', $escaped) ?? $escaped;
        $escaped = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/s', '<em>$1</em>', $escaped) ?? $escaped;
        $escaped = preg_replace('/(?<!_)_(?!_)(.+?)(?<!_)_(?!_)/s', '<em>$1</em>', $escaped) ?? $escaped;

        return strtr($escaped, $code);
    }

    /**
     * @param list<string> $html
     * @param list<string> $paragraph
     */
    private function flushParagraph(array &$html, array &$paragraph): void
    {
        if ($paragraph === []) {
            return;
        }

        $html[] = '<p>' . $this->inline(implode(' ', $paragraph)) . '</p>';
        $paragraph = [];
    }

    /**
     * @param list<string> $html
     * @param array{type: 'ol'|'ul', items: list<string>}|null $list
     */
    private function flushList(array &$html, ?array &$list): void
    {
        if ($list === null || $list['items'] === []) {
            $list = null;
            return;
        }

        $html[] = '<' . $list['type'] . '>' . implode('', $list['items']) . '</' . $list['type'] . '>';
        $list = null;
    }

    /**
     * @param array{type: 'ol'|'ul', items: list<string>}|null $list
     */
    private function appendListItem(?array &$list, string $type, string $text): void
    {
        $list ??= ['type' => $type, 'items' => []];
        $list['items'][] = '<li>' . $this->inline(trim($text)) . '</li>';
    }

    /**
     * @param list<string> $html
     * @param list<string> $blockquote
     */
    private function flushBlockquote(array &$html, array &$blockquote): void
    {
        if ($blockquote === []) {
            return;
        }

        $html[] = '<blockquote><p>' . $this->inline(implode(' ', $blockquote)) . '</p></blockquote>';
        $blockquote = [];
    }

    /**
     * @param list<string> $code
     */
    private function renderCodeBlock(array $code, string $language): string
    {
        $language = preg_replace('/[^A-Za-z0-9_-]/', '', $language) ?? '';
        $class = $language === '' ? '' : ' class="language-' . htmlspecialchars($language, ENT_QUOTES, 'UTF-8') . '"';

        return '<pre><code' . $class . '>' . htmlspecialchars(implode("\n", $code), ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</code></pre>';
    }
}
