<?php

declare(strict_types=1);

namespace CommonPHP\Docs\Tests\Unit;

use CommonPHP\Docs\Exceptions\DocsException;
use CommonPHP\Docs\Exceptions\DocumentNotFoundException;
use CommonPHP\Docs\Exceptions\DocumentParseException;
use CommonPHP\Docs\Exceptions\DocumentRenderException;
use CommonPHP\Docs\Exceptions\InvalidDocumentPathException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ExceptionsTest extends TestCase
{
    public function testExceptionFactoriesCreateHelpfulMessagesAndPreservePreviousExceptions(): void
    {
        $previous = new RuntimeException('root');
        $notFound = DocumentNotFoundException::forPath('');
        $parse = DocumentParseException::forPath('guide', 'bad front matter', $previous);
        $render = DocumentRenderException::forPath('guide', 'bad template', $previous);
        $invalid = InvalidDocumentPathException::forPath('../secret', 'path traversal is not allowed');

        self::assertInstanceOf(DocsException::class, $notFound);
        self::assertSame('Documentation page not found: "index".', $notFound->getMessage());
        self::assertSame('Unable to parse documentation page "guide": bad front matter', $parse->getMessage());
        self::assertSame($previous, $parse->getPrevious());
        self::assertSame('Unable to render documentation page "guide": bad template', $render->getMessage());
        self::assertSame($previous, $render->getPrevious());
        self::assertSame('Invalid documentation path "../secret": path traversal is not allowed', $invalid->getMessage());
    }
}
