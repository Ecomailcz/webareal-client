<?php

declare(strict_types=1);

namespace EcomailWebareal\TokenCache;

use DateTimeInterface;

interface ITokenCache
{
    public const DEFAULT_TTL = '+1 hour';

    public function load(): ?string;

    public function save(string $token, ?DateTimeInterface $expire = null): void;

    public function clear(): void;
}
