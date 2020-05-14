<?php

declare(strict_types=1);

namespace Ecomailcz\Webareal\TokenCache;

use DateTimeInterface;

interface ITokenCache
{
    public const DEFAULT_TTL = '+1 hour';

    public function load(string $cacheKey): ?string;

    public function save(string $cacheKey, string $token, ?DateTimeInterface $expire = null): void;

    public function clear(string $cacheKey): void;
}
