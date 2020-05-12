<?php

declare(strict_types=1);

namespace EcomailWebareal\Exception;

use EcomailWebareal\Response\Response;
use Throwable;

class ResponseErrorException extends NetworkException
{
    /** @var Response */
    private $response;

    public function __construct(string $message, int $code, Response $response, ?Throwable $previous = null)
    {
        $this->response = $response;
        parent::__construct($message, $code, $previous);
    }

    public function getResponse(): Response
    {
        return $this->response;
    }

}
