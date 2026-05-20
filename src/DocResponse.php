<?php

declare(strict_types=1);

namespace CommonPHP\Docs;

use CommonPHP\Docs\Contracts\DocumentationRendererInterface;
use CommonPHP\Docs\Exceptions\DocumentRenderException;
use CommonPHP\HTTP\Enums\ResponseStatus;
use CommonPHP\HTTP\HeaderBag;
use CommonPHP\HTTP\Response;
use Throwable;

final class DocResponse extends Response
{
    /**
     * @param array<string, mixed>|HeaderBag $headers
     */
    public function __construct(
        private readonly ?DocumentPage $page = null,
        string $body = '',
        ResponseStatus|int $status = ResponseStatus::OK,
        array|HeaderBag $headers = [],
    ) {
        parent::__construct($body, $status, $headers);
    }

    public static function fromPage(
        DocumentPage $page,
        DocumentationRendererInterface $renderer,
        ?Navigation $navigation = null,
        ?string $title = null,
        bool $includeBody = true,
    ): self {
        try {
            $body = $renderer->render($page, $navigation, $title);
        } catch (Throwable $exception) {
            throw DocumentRenderException::forPath($page->path(), $exception->getMessage(), $exception);
        }

        $headers = [
            'Content-Type' => 'text/html; charset=utf-8',
            'Content-Length' => (string) strlen($body),
        ];

        if ($page->modifiedAt() !== null) {
            $headers['Last-Modified'] = gmdate('D, d M Y H:i:s', $page->modifiedAt()) . ' GMT';
        }

        return new self($page, $includeBody ? $body : '', ResponseStatus::OK, $headers);
    }

    public static function notFound(
        string $path,
        DocumentationRendererInterface $renderer,
        ?string $title = null,
        bool $includeBody = true,
    ): self {
        $body = $renderer->renderNotFound($path, $title);

        return new self(null, $includeBody ? $body : '', ResponseStatus::NOT_FOUND, [
            'Content-Type' => 'text/html; charset=utf-8',
            'Content-Length' => (string) strlen($body),
        ]);
    }

    public function page(): ?DocumentPage
    {
        return $this->page;
    }
}
