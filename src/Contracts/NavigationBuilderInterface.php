<?php

declare(strict_types=1);

namespace CommonPHP\Docs\Contracts;

use CommonPHP\Docs\DocumentPage;
use CommonPHP\Docs\Navigation;

interface NavigationBuilderInterface
{
    /**
     * @param iterable<DocumentPage> $pages
     */
    public function build(iterable $pages, string $basePath = '/docs', ?string $currentPath = null): Navigation;
}
