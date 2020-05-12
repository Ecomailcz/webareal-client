<?php

declare(strict_types=1);

namespace EcomailWebareal;

use EcomailWebareal\Exception\AccessDeniedException;
use EcomailWebareal\Exception\ConnectionException;
use EcomailWebareal\Exception\InvalidRequestException;
use EcomailWebareal\Exception\InvalidResponseException;
use EcomailWebareal\Exception\NotFoundException;
use EcomailWebareal\Exception\ResponseErrorException;
use EcomailWebareal\Exception\UnauthorizedException;
use EcomailWebareal\Exception\UnexpectedResponseException;
use EcomailWebareal\Response\JsonResponse;
use EcomailWebareal\Response\Response;
use EcomailWebareal\TokenCache\ITokenCache;
use EcomailWebareal\TokenCache\MemoryCache;
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
        return $this->tokenCache->load() ?? $this->login();
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

        $this->tokenCache->save($response->getField('token'));

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
        curl_setopt($curl, CURLOPT_HTTPHEADER, $requestHeaders);

        if ($apiToken !== null) {
            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BEARER);
            curl_setopt($curl, CURLOPT_XOAUTH2_BEARER, $apiToken);
        }

        $responseContent = curl_exec($curl);
        if ($error = curl_error($curl)) {
            $curl_errno = curl_errno($curl);
            curl_close($curl);
            throw new ConnectionException($error, $curl_errno);
        }

        $response = new Response($responseContent, $responseHeaders, curl_getinfo($curl, CURLINFO_HTTP_CODE));
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
}
