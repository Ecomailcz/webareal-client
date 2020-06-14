<?php

declare(strict_types=1);

namespace Ecomailcz\Webareal;

use Ecomailcz\Webareal\Exception\AccessDeniedException;
use Ecomailcz\Webareal\Exception\ConnectionException;
use Ecomailcz\Webareal\Exception\InvalidRequestException;
use Ecomailcz\Webareal\Exception\InvalidResponseException;
use Ecomailcz\Webareal\Exception\NotFoundException;
use Ecomailcz\Webareal\Exception\ResponseErrorException;
use Ecomailcz\Webareal\Exception\UnauthorizedException;
use Ecomailcz\Webareal\Exception\UnexpectedResponseException;
use Ecomailcz\Webareal\Response\JsonResponse;
use Ecomailcz\Webareal\Response\Response;
use Ecomailcz\Webareal\TokenCache\ITokenCache;
use Ecomailcz\Webareal\TokenCache\MemoryCache;
use JsonException;

class Client
{
    private const USER_AGENT = 'Ecomail.cz Webareal client (https://github.com/Ecomailcz/webareal-client)';

    /**
     * Webareal API credentials
     *
     * @var Credentials
     */
    private $credentials;


    /**
     * Token cache
     *
     * @var ITokenCache
     */
    private $tokenCache;

    public function __construct(Credentials $credentials, ?ITokenCache $tokenCache = null)
    {
        $this->credentials = $credentials;
        $this->tokenCache = $tokenCache ?? new MemoryCache();
    }

    public function requestGet(string $urlPath, array $query = []): array
    {
        return $this->request('GET', $urlPath, [], $query)->getContent();
    }

    public function requestPost(string $urlPath, array $post = [], array $query = []): array
    {
        return $this->request('POST', $urlPath, $post, $query)->getContent();
    }

    public function requestPut(string $urlPath, array $post = [], array $query = []): array
    {
        return $this->request('PUT', $urlPath, $post, $query)->getContent();
    }

    public function requestDelete(string $urlPath, array $post = [], array $query = []): array
    {
        return $this->request('DELETE', $urlPath, $post, $query)->getContent();
    }

    public function request(string $method, string $urlPath, array $post = [], array $query = []): JsonResponse
    {
        $response = $this->processRequest($method, $this->getApiToken(), $urlPath, $post, $query);

        return $response;
    }

    private function getApiToken(): string
    {
        return $this->tokenCache->load($this->credentials->getIdentityHash()) ?? $this->login();
    }

    private function login(): string
    {
        $response = $this->processRequest(
            'POST',
            null,
            'login',
            [
                'username' => $this->credentials->getLogin(),
                'password' => $this->credentials->getPassword()
            ]
        );

        if (is_string($response->getField('token')) === false) {
            throw new InvalidResponseException(
                "Expected 'token' field in response is missing or invalid type", 0, $response
            );
        }

        $this->tokenCache->save($this->credentials->getIdentityHash(), $response->getField('token'));

        return $response->getField('token');
    }

    private function processRequest(
        string $method,
        ?string $apiToken,
        string $urlPath,
        array $post = [],
        array $query = []
    ): JsonResponse {
        $requestHeaders = [];
        $responseHeaders = [];

        if (count($query)) {
            $urlPath .= (strpos($urlPath, '?') === false ? '?' : '&') . http_build_query($query);
        }

        $curl = curl_init();
        curl_setopt_array(
            $curl,
            [
                CURLOPT_URL => $this->credentials->getUrl() . '/' . $urlPath,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FAILONERROR => false,
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_USERAGENT => self::USER_AGENT,
                CURLOPT_HEADERFUNCTION => static function ($curl, $header) use (&$responseHeaders) {
                    $parts = explode(':', $header, 2);
                    if (count($parts) === 2) {
                        $responseHeaders[strtolower(trim($parts[0]))][] = trim($parts[1]);
                    }
                    return strlen($header);
                },
            ]
        );

        if (count($post) && in_array(strtoupper($method), ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            $requestHeaders[] = 'Content-Type: application/json';
            curl_setopt($curl, CURLOPT_POSTFIELDS, $this->jsonEncode($post));
        }

        $requestHeaders[] = 'X-Wa-api-token: ' . $this->credentials->getApiKey();

        if ($apiToken !== null) {
            $requestHeaders[] = 'Authorization: Bearer ' . $apiToken;
        }
        curl_setopt($curl, CURLOPT_HTTPHEADER, $requestHeaders);

        $this->connectCaBundle($curl);

        $responseContent = curl_exec($curl);
        if ($error = curl_error($curl)) {
            $curl_errno = curl_errno($curl);
            curl_close($curl);
            throw new ConnectionException($error, $curl_errno);
        }

        $response = new Response((string)$responseContent, $responseHeaders, curl_getinfo($curl, CURLINFO_HTTP_CODE));
        curl_close($curl);

        if ($response->getStatusCode() >= 400) {
            throw $this->createResponseErrorException($response);
        }

        if (empty($response->getContent())) {
            return new JsonResponse([], $response->getHeaders(), $response->getStatusCode());
        }

        if ($response->getHeaderValue('content-type') !== 'application/json') {
            $type = $response->getHeaderValue('content-type') ?? 'undefined';
            throw new UnexpectedResponseException(
                "Expected JSON response, '{$type}' type response instead",
                0,
                $response
            );
        }

        try {
            $data = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidResponseException($e->getMessage(), $e->getCode(), $response, $e);
        }

        return new JsonResponse($data, $response->getHeaders(), $response->getStatusCode());
    }

    private function jsonEncode(array $data): string
    {
        try {
            return json_encode(
                $data,
                JSON_THROW_ON_ERROR
                | JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
                | JSON_PRESERVE_ZERO_FRACTION
            );
        } catch (JsonException $e) {
            throw new InvalidRequestException('JSON: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    private function createResponseErrorException(Response $response): ResponseErrorException
    {
        $map = [
            401 => UnauthorizedException::class,
            403 => AccessDeniedException::class,
            404 => NotFoundException::class,
        ];
        $exceptionClass = $map[$response->getStatusCode()] ?? ResponseErrorException::class;

        return new $exceptionClass(
            "Server returns error status code: {$response->getStatusCode()}",
            $response->getStatusCode(),
            $response
        );
    }

    /**
     * @param resource $curl Curl resource
     */
    private function connectCaBundle($curl): void
    {
        if (class_exists(\Composer\CaBundle\CaBundle::class, true) === false) {
            return;
        }

        $caPathOrFile = \Composer\CaBundle\CaBundle::getSystemCaRootBundlePath();
        if (is_dir($caPathOrFile)) {
            curl_setopt($curl, CURLOPT_CAPATH, $caPathOrFile);
        } else {
            curl_setopt($curl, CURLOPT_CAINFO, $caPathOrFile);
        }
    }
}
