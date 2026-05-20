<?php

declare(strict_types=1);

namespace CommonPHP\Docs\Contracts;

use CommonPHP\Docs\DocumentPage;
use CommonPHP\Docs\Navigation;

interface DocumentationRendererInterface
{
    public function render(DocumentPage $page, ?Navigation $navigation = null, ?string $title = null): string;

    public function renderNotFound(string $path = '', ?string $title = null): string;
}
