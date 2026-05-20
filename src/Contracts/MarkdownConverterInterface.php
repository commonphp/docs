<?php

declare(strict_types=1);

namespace CommonPHP\Docs\Contracts;

interface MarkdownConverterInterface
{
    public function convert(string $markdown): string;

    public function toHtml(string $markdown): string;
}
