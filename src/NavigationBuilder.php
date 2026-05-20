<?php

declare(strict_types=1);

namespace CommonPHP\Docs;

use CommonPHP\Docs\Contracts\NavigationBuilderInterface;

final class NavigationBuilder implements NavigationBuilderInterface
{
    public function build(iterable $pages, string $basePath = '/docs', ?string $currentPath = null): Navigation
    {
        $basePath = Documentation::normalizeBasePath($basePath);
        $currentPath = $currentPath === null ? null : FilesystemDocumentLoader::normalizeDocumentPath($currentPath, true);
        $entries = [];

        foreach ($pages as $page) {
            if (!$page instanceof DocumentPage || $page->isHidden()) {
                continue;
            }

            $this->ensureParents($entries, $page->path(), $basePath, $currentPath);
            $entries[$page->path()] = [
                'item' => new NavigationItem(
                    $page->path(),
                    $page->navigationTitle(),
                    $page->href($basePath),
                    $this->isActive($page->path(), $currentPath),
                    $page->path() === ($currentPath ?? ''),
                ),
                'order' => $page->order(),
                'synthetic' => false,
            ];
        }

        uasort($entries, static function (array $left, array $right): int {
            return [$left['order'], $left['item']->title(), $left['item']->path()]
                <=> [$right['order'], $right['item']->title(), $right['item']->path()];
        });

        return new Navigation($this->childrenFor(null, $entries), $basePath, $currentPath);
    }

    /**
     * @param array<string, array{item: NavigationItem, order: int, synthetic: bool}> $entries
     */
    private function ensureParents(array &$entries, string $path, string $basePath, ?string $currentPath): void
    {
        if ($path === '' || !str_contains($path, '/')) {
            return;
        }

        $segments = explode('/', $path);
        array_pop($segments);
        $parent = '';

        foreach ($segments as $segment) {
            $parent = $parent === '' ? $segment : $parent . '/' . $segment;

            if (isset($entries[$parent])) {
                continue;
            }

            $entries[$parent] = [
                'item' => new NavigationItem(
                    $parent,
                    $this->humanize($segment),
                    $this->href($basePath, $parent),
                    $this->isActive($parent, $currentPath),
                    $parent === ($currentPath ?? ''),
                ),
                'order' => 0,
                'synthetic' => true,
            ];
        }
    }

    /**
     * @param array<string, array{item: NavigationItem, order: int, synthetic: bool}> $entries
     * @return list<NavigationItem>
     */
    private function childrenFor(?string $parentPath, array $entries): array
    {
        $children = [];

        foreach ($entries as $path => $entry) {
            if ($this->parentPath($path) !== $parentPath) {
                continue;
            }

            $children[] = $entry['item']->withChildren($this->childrenFor($path, $entries));
        }

        return $children;
    }

    private function parentPath(string $path): ?string
    {
        if ($path === '' || !str_contains($path, '/')) {
            return null;
        }

        return dirname($path);
    }

    private function isActive(string $path, ?string $currentPath): bool
    {
        if ($currentPath === null) {
            return false;
        }

        if ($path === $currentPath) {
            return true;
        }

        return $path !== '' && str_starts_with($currentPath, $path . '/');
    }

    private function href(string $basePath, string $path): string
    {
        return rtrim($basePath, '/') . '/' . implode('/', array_map('rawurlencode', explode('/', $path)));
    }

    private function humanize(string $segment): string
    {
        return ucwords(str_replace(['-', '_'], ' ', $segment));
    }
}
