<?php

declare(strict_types=1);

namespace Ecomailcz\Webareal;

class Credentials
{
    public const DEFAULT_URL = 'https://api.webareal.cz';

    /**
     * Webareal login
     *
     * @var string
     */
    private $login;

    /**
     * Webareal password
     *
     * @var string
     */
    private $password;

    /**
     * Webareal API endpoint URL
     *
     * @var string
     */
    private $url;

    /**
     * Webareal access token
     *
     * @var string
     */
    private $apiKey;

    public function __construct(string $login, string $password, string $apiKey, string $url = self::DEFAULT_URL)
    {
        $this->password = $password;
        $this->login = $login;
        $this->apiKey = $apiKey;
        $this->url = $this->getAbsoluteUrl($url);
    }

    /**
     * Make absolute Endpoint URL, with back compatibility
     *
     * @param string $url
     * @return string
     */
    private function getAbsoluteUrl(string $url): string
    {
        if (preg_match('~^https?://~', $url) === 1) {
            return $url;
        }

        trigger_error('Use absolute URL with https://', E_USER_DEPRECATED);
        return 'https://' . $url;
    }

    public function getLogin(): string
    {
        return $this->login;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getIdentityHash(): string
    {
        return md5(serialize([$this->login, $this->password, $this->apiKey, $this->url]));
    }
}
