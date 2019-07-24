<?php declare(strict_types=1);


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
        $this->url = $url;
        $this->apiKey = $apiKey;
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