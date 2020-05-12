<?php

declare(strict_types=1);

namespace EcomailWebareal\Exception;

use EcomailWebareal\Response\IResponse;
use Throwable;

class ResponseException extends RuntimeException
{
    /** @var IResponse */
    private $response;

    public function __construct(string $message, int $code, IResponse $response, ?Throwable $previous = null)
    {
        $this->response = $response;
        parent::__construct($message, $code, $previous);
    }

    public function getResponse(): IResponse
    {
        return $this->response;
    }
}
