<?php

declare(strict_types=1);

namespace CommonPHP\Docs;

use Stringable;

final class NavigationItem implements Stringable
{
    /**
     * @param list<self> $children
     */
    public function __construct(
        private readonly string $path,
        private readonly string $title,
        private readonly string $href,
        private readonly bool $active = false,
        private readonly bool $current = false,
        private readonly array $children = [],
    ) {
    }

    public function path(): string
    {
        return $this->path;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function href(): string
    {
        return $this->href;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function isCurrent(): bool
    {
        return $this->current;
    }

    /**
     * @return list<self>
     */
    public function children(): array
    {
        return $this->children;
    }

    /**
     * @param list<self> $children
     */
    public function withChildren(array $children): self
    {
        return new self($this->path, $this->title, $this->href, $this->active, $this->current, $children);
    }

    /**
     * @return array{
     *     path: string,
     *     title: string,
     *     href: string,
     *     active: bool,
     *     current: bool,
     *     children: list<array<string, mixed>>
     * }
     */
    public function toArray(): array
    {
        return [
            'path' => $this->path,
            'title' => $this->title,
            'href' => $this->href,
            'active' => $this->active,
            'current' => $this->current,
            'children' => array_map(static fn (self $child): array => $child->toArray(), $this->children),
        ];
    }

    public function __toString(): string
    {
        return $this->title;
    }
}
