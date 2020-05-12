<?php

declare(strict_types=1);

namespace Ecomailcz\Webareal\Exception;

use Ecomailcz\Webareal\Response\IResponse;
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
