<?php

declare(strict_types=1);

namespace Ecomailcz\Webareal\TokenCache;

use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use JsonException;

class FileCache implements ITokenCache
{
    private const EXPIRE = 'expire';
    private const TOKEN = 'token';

    /** @var string */
    private $tempDir;

    public function __construct(string $tempDir)
    {
        $this->tempDir = $tempDir;
    }

    public function load(string $cacheKey): ?string
    {
        try {
            return $this->loadFormFile($cacheKey);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * @param string $cacheKey
     * @param string $token
     * @param DateTimeInterface|null $expire
     * @throws CacheException
     */
    public function save(string $cacheKey, string $token, ?DateTimeInterface $expire = null): void
    {
        if ($expire === null) {
            $expire = new DateTimeImmutable(self::DEFAULT_TTL);
        }

        if (!is_dir($this->tempDir) && !mkdir($this->tempDir, 0777, true) && !is_dir($this->tempDir)) {
            throw new CacheException(sprintf('Unable to create temp directory: "%s"', $this->tempDir));
        }

        try {
            $json = json_encode([self::EXPIRE => $expire->format('c'), self::TOKEN => $token], JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new CacheException("JSON: " . $e->getMessage(), $e->getCode(), $e);
        }

        $result = file_put_contents(
            $this->getCacheFile($cacheKey),
            $json
        );

        if ($result === false) {
            throw new CacheException(sprintf('Unable to create temp directory: "%s"', $this->tempDir));
        }
    }

    public function clear(string $cacheKey): void
    {
        $file = $this->getCacheFile($cacheKey);
        if (is_file($file) && !unlink($file)) {
            throw new CacheException(sprintf('Unable to delete cache file: "%s"', $file));
        }
    }

    private function getCacheFile(string $cacheKey): string
    {
        return $this->tempDir . "/tokenCache_{$cacheKey}.json";
    }

    /**
     * @param string $cacheKey
     * @return string|null
     * @throws JsonException
     * @throws Exception
     */
    private function loadFormFile(string $cacheKey): ?string
    {
        $file = $this->getCacheFile($cacheKey);

        $json = @file_get_contents($file); // intentionally @ - file nay not exists
        if ($json === false) {
            return null;
        }

        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        if (isset($data[self::EXPIRE], $data[self::TOKEN]) === false) {
            return null;
        }

        $expire = new DateTimeImmutable($data[self::EXPIRE]);
        if ($expire <= new DateTimeImmutable()) {
            return null;
        }

        return (string)$data[self::TOKEN];
    }
}
