<?php

declare(strict_types=1);

namespace CommonPHP\Docs\Exceptions;

use Throwable;

class DocumentParseException extends DocsException
{
    public static function forPath(string $path, string $reason, ?Throwable $previous = null): self
    {
        $path = $path === '' ? 'index' : $path;

        return new self('Unable to parse documentation page "' . $path . '": ' . $reason, 0, $previous);
    }
}
