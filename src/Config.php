<?php

declare(strict_types=1);

namespace EcomailWebareal;

final class Config
{
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
    private $pass;

    /**
     * Webareal api subdomain
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

    public function __construct(string $login, string $pass, string $url, string $apiKey)
    {
        $this->pass = $pass;
        $this->login = $login;
        $this->url = $this->getAbsoluteUrl($url);
        $this->apiKey = $apiKey;
    }

    /**
     * Make absolute Endpoint URL, with back compatibility
     *
     * @param string $url
     * @return string
     */
    private function getAbsoluteUrl(string $url): string
    {
        if(preg_match('~^https?://~', $url) === 1) {
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
        return $this->pass;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }
}
