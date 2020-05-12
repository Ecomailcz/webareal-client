<?php

declare(strict_types=1);

namespace EcomailWebareal\TokenCache;

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

    public function load(): ?string
    {
        try {
            return $this->loadFormFile();
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * @param string $token
     * @param DateTimeInterface|null $expire
     * @throws CacheException
     */
    public function save(string $token, ?DateTimeInterface $expire = null): void
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
            $this->getCacheFile(),
            $json
        );

        if ($result === false) {
            throw new CacheException(sprintf('Unable to create temp directory: "%s"', $this->tempDir));
        }
    }

    public function clear(): void
    {
        $file = $this->getCacheFile();
        if (is_file($file) && !unlink($file)) {
            throw new CacheException(sprintf('Unable to delete cache file: "%s"', $file));
        }
    }

    private function getCacheFile(): string
    {
        return $this->tempDir . '/tokenCache.json';
    }

    /**
     * @return string|null
     * @throws JsonException
     * @throws Exception
     */
    private function loadFormFile(): ?string
    {
        $file = $this->getCacheFile();

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
