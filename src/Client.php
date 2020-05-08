<?php

declare(strict_types=1);

namespace EcomailWebareal;

use EcomailShoptet\Exception\EcomailShoptetRequestError;
use EcomailWebareal\Exception\EcomailWebarealInvalidAuthorization;
use EcomailWebareal\Exception\EcomailWebarealNotFound;

class Client
{
    /**
     * @var Config
     */
    private $config;

    /**
     * Webareal api token
     *
     * @var string|null
     */
    private $apiToken;

    public function __construct(string $login, string $pass, string $url, string $apiKey)
    {
        $this->config = new Config($login, $pass, $url, $apiKey);
    }

    public function makeRequest(
        string $httpMethod,
        string $url,
        array $postFields = [],
        array $queryParameters = []
    ): array {
        if ($this->apiToken === null) {
            throw new EcomailWebarealInvalidAuthorization();
        }

        /** @var resource $ch */
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_HTTPAUTH, true);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            [
                'Authorization:  Bearer ' . $this->apiToken,
                'X-Wa-api-token: ' . $this->config->getApiKey()
            ]
        );

        if (count($queryParameters) !== 0) {
            $url .= '?' . http_build_query($queryParameters);
        }

        curl_setopt($ch, CURLOPT_URL, 'https://' . $this->config->getUrl() . '/' . $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $httpMethod);
        curl_setopt(
            $ch,
            CURLOPT_USERAGENT,
            'Ecomail.cz Webareal client (https://github.com/Ecomailcz/webareal-client)'
        );

        if (count($postFields) !== 0) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postFields));
        }

        $output = curl_exec($ch);

        if ($output === false) {
            throw new Exception(curl_error($ch), curl_errno($ch));
        }

        $result = json_decode($output, true);

        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) !== 200 && curl_getinfo($ch, CURLINFO_HTTP_CODE) !== 201) {
            if (curl_getinfo($ch, CURLINFO_HTTP_CODE) === 404) {
                throw new EcomailWebarealNotFound();
            } // Check authorization
            elseif (curl_getinfo($ch, CURLINFO_HTTP_CODE) === 401) {
                throw new EcomailWebarealInvalidAuthorization($this->apiToken);
            } elseif (curl_getinfo($ch, CURLINFO_HTTP_CODE) === 400) {
                if (isset($result['errors']) && sizeof($result['errors']) > 0) {
                    foreach ($result['errors'] as $error) {
                        throw new EcomailShoptetRequestError($error['message']);
                    }
                }
            }
        }

        if (!$result) {
            return [];
        }

        if (array_key_exists('success', $result) && !$result['success']) {
            throw new EcomailWebrealAnotherError($result);
        }

        return $result;
    }

    public function getApiToken()
    {
        $curl = curl_init();
        $apiServer = $this->config->getUrl();
        $username = $this->config->getLogin();
        $password = $this->config->getPassword();
        $apiKey = $this->config->getApiKey();

        curl_setopt_array(
            $curl,
            [
                CURLOPT_URL => "https://$apiServer/login",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => "{\n  \"username\": \"$username\",\n  \"password\": \"$password\"\n}",
                CURLOPT_HTTPHEADER => [
                    "X-Wa-api-token: $apiKey",
                ],
            ]
        );

        $output = curl_exec($curl);
        $err = curl_error($curl);


        if ($output === false) {
            throw new Exception($err, curl_errno($curl));
        }

        $result = json_decode($output);

        return $result;
    }
}
