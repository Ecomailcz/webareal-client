<?php

declare(strict_types=1);

namespace EcomailWebareal\Response;

interface IResponse
{
    /** @return string|array */
    public function getContent();

    public function getHeaders(): array;

    public function getStatusCode(): int;
}
