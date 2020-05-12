<?php

declare(strict_types=1);

namespace Ecomailcz\Webareal\TokenCache;

use DateTimeImmutable;
use DateTimeInterface;

class MemoryCache implements ITokenCache
{
    /** @var string|null */
    private $token;
    /** @var DateTimeInterface|null */
    private $expire;

    public function load(): ?string
    {
        if ($this->token !== null && $this->expire > new DateTimeImmutable()) {
            return $this->token;
        }

        return null;
    }

    public function save(string $token, ?DateTimeInterface $expire = null): void
    {
        $this->expire = $expire ?? new DateTimeImmutable(self::DEFAULT_TTL);
        $this->token = $token;
    }

    public function clear(): void
    {
        $this->token = null;
        $this->expire = null;
    }
}
