<?php

declare(strict_types=1);

namespace CommonPHP\Docs\Exceptions;

class DocumentNotFoundException extends DocsException
{
    public static function forPath(string $path): self
    {
        $path = $path === '' ? 'index' : $path;

        return new self('Documentation page not found: "' . $path . '".');
    }
}
