<?php

declare(strict_types=1);

namespace Ecomailcz\Webareal\Response;

class Response implements IResponse
{
    /** @var string */
    private $content;
    /** @var array */
    private $headers;
    /** @var int */
    private $statusCode;

    public function __construct(string $content, array $headers, int $statusCode)
    {
        $this->content = $content;
        $this->headers = $headers;
        $this->statusCode = $statusCode;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getHeaderValue(string $name): ?string
    {
        return $this->headers[$name][0] ?? null;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
