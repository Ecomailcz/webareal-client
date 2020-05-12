<?php

declare(strict_types=1);

namespace Ecomailcz\Webareal\Response;

interface IResponse
{
    /** @return string|array */
    public function getContent();

    public function getHeaders(): array;

    public function getStatusCode(): int;
}
