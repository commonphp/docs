<?php

declare(strict_types=1);

namespace CommonPHP\Docs\Exceptions;

class InvalidDocumentPathException extends DocsException
{
    public static function forPath(string $path, string $reason): self
    {
        return new self('Invalid documentation path "' . $path . '": ' . $reason);
    }
}
