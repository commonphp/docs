<?php

declare(strict_types=1);

namespace CommonPHP\Docs\Contracts;

use CommonPHP\Docs\DocumentPage;

interface DocumentLoaderInterface
{
    public function load(string $path = ''): DocumentPage;

    public function exists(string $path = ''): bool;

    /**
     * @return list<DocumentPage>
     */
    public function all(): array;

    /**
     * @return list<string>
     */
    public function paths(): array;
}
