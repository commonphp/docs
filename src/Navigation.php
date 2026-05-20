<?php

declare(strict_types=1);

namespace CommonPHP\Docs;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, NavigationItem>
 */
final class Navigation implements Countable, IteratorAggregate
{
    /**
     * @param list<NavigationItem> $items
     */
    public function __construct(
        private readonly array $items = [],
        private readonly string $basePath = '/docs',
        private readonly ?string $currentPath = null,
    ) {
    }

    /**
     * @return list<NavigationItem>
     */
    public function items(): array
    {
        return $this->items;
    }

    /**
     * @return list<NavigationItem>
     */
    public function all(): array
    {
        return $this->items();
    }

    public function basePath(): string
    {
        return $this->basePath;
    }

    public function currentPath(): ?string
    {
        return $this->currentPath;
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    public function find(string $path): ?NavigationItem
    {
        $path = FilesystemDocumentLoader::normalizeDocumentPath($path, true);

        foreach ($this->flatten() as $item) {
            if ($item->path() === $path) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @return list<NavigationItem>
     */
    public function flatten(): array
    {
        $items = [];

        foreach ($this->items as $item) {
            $this->flattenItem($item, $items);
        }

        return $items;
    }

    public function toHtml(): string
    {
        if ($this->items === []) {
            return '';
        }

        return '<nav class="docs-nav" aria-label="Documentation navigation">' . $this->renderItems($this->items) . '</nav>';
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function toArray(): array
    {
        return array_map(static fn (NavigationItem $item): array => $item->toArray(), $this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    /**
     * @param list<NavigationItem> $items
     */
    private function renderItems(array $items): string
    {
        $html = '<ul>';

        foreach ($items as $item) {
            $classes = array_filter([
                $item->isActive() ? 'is-active' : null,
                $item->isCurrent() ? 'is-current' : null,
            ]);
            $class = $classes === [] ? '' : ' class="' . implode(' ', $classes) . '"';
            $current = $item->isCurrent() ? ' aria-current="page"' : '';
            $html .= '<li' . $class . '><a href="' . $this->escape($item->href()) . '"' . $current . '>'
                . $this->escape($item->title()) . '</a>';

            if ($item->children() !== []) {
                $html .= $this->renderItems($item->children());
            }

            $html .= '</li>';
        }

        return $html . '</ul>';
    }

    /**
     * @param list<NavigationItem> $items
     */
    private function flattenItem(NavigationItem $item, array &$items): void
    {
        $items[] = $item;

        foreach ($item->children() as $child) {
            $this->flattenItem($child, $items);
        }
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
