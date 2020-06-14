<?php

declare(strict_types=1);

namespace Ecomailcz\Webareal\TokenCache;

use DateTimeImmutable;
use DateTimeInterface;

class MemoryCache implements ITokenCache
{
    /** @var string[] */
    private $token = [];
    /** @var DateTimeInterface[] */
    private $expire = [];

    public function load(string $cacheKey): ?string
    {
        if (isset($this->token[$cacheKey]) && $this->expire[$cacheKey] > new DateTimeImmutable()) {
            return $this->token[$cacheKey];
        }

        return null;
    }

    public function save(string $cacheKey, string $token, ?DateTimeInterface $expire = null): void
    {
        $this->expire[$cacheKey] = $expire ?? new DateTimeImmutable(self::DEFAULT_TTL);
        $this->token[$cacheKey] = $token;
    }

    public function clear(string $cacheKey): void
    {
        if (isset($this->token[$cacheKey])) {
            unset($this->token[$cacheKey], $this->expire[$cacheKey]);
        }
    }
}
