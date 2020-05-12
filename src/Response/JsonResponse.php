<?php

declare(strict_types=1);

namespace EcomailWebareal\Response;

class JsonResponse implements IResponse
{
    /** @var array */
    private $content;
    /** @var array */
    private $headers;
    /** @var int */
    private $statusCode;

    public function __construct(array $content, array $headers, int $statusCode)
    {
        $this->content = $content;
        $this->headers = $headers;
        $this->statusCode = $statusCode;
    }

    public function getContent(): array
    {
        return $this->content;
    }

    public function hasField(string $key): bool
    {
        return isset($this->content[$key]);
    }

    /**
     * @param string $key
     * @return mixed|null
     */
    public function getField(string $key)
    {
        return $this->content[$key] ?? null;
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
