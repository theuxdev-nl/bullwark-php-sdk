<?php

namespace BullwarkSdk;

use BullwarkSdk\Exceptions\ApiRequestException;
use BullwarkSdk\Exceptions\JwkKidNotFoundException;
use BullwarkSdk\Support\CachedJwks;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;


class ApiClient
{
    private AuthConfig $authConfig;
    private AuthState $authState;

    private CachedJwks $cachedJwks;

    private ClientInterface $httpClient;
    private RequestFactoryInterface $requestFactory;
    private StreamFactoryInterface $streamFactory;

    public function __construct(AuthConfig $authConfig, AuthState $authState, ClientInterface $httpClient, RequestFactoryInterface $requestFactory, StreamFactoryInterface $streamFactory)
    {
        $this->authConfig = $authConfig;
        $this->authState = $authState;

        $this->httpClient = $httpClient;
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
    }

    private function withDefaultHeaders(\Psr\Http\Message\RequestInterface $request, array $extraHeaders = [])
    {
        $defaults = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-Customer-Uuid' => $this->authConfig->getCustomerUuid(),
            'X-Tenant-Uuid' => $this->authConfig->getTenantUuid(),
        ];

        foreach (array_merge($defaults, $extraHeaders) as $key => $value) {
            $request = $request->withHeader($key, $value);
        }

        return $request;
    }

    /**
     * @throws ClientExceptionInterface
     */
    public function login(string $email, string $password): array
    {
        $this->authState->isLoading = true;

        try {
            $body = $this->streamFactory->createStream(json_encode([
                'email' => $email,
                'password' => $password,
            ]));

            $request = $this->requestFactory->createRequest('POST', $this->authConfig->getApiUrl() . '/login?plainRefresh=true');
            $request = $this->withDefaultHeaders($request);
            $request = $request->withBody($body);

            $response = $this->httpClient->sendRequest($request);
            if ($response->getStatusCode() !== 200) {
                throw new ApiRequestException((string)$response->getBody(), $response->getStatusCode());
            }

            $data = json_decode((string)$response->getBody(), true);

            return [
                'jwt' => $data['token'],
                'refreshToken' => $data['refreshToken'],
            ];
        } finally {
            $this->authState->isLoading = false;
        }
    }


    /**
     * @throws ClientExceptionInterface
     */
    public function refresh(string $refreshToken): array
    {
        $this->authState->isLoading = true;

        try {
            $body = $this->streamFactory->createStream(json_encode([
                'refreshToken' => $refreshToken
            ]));

            $request = $this->requestFactory->createRequest('POST', $this->authConfig->getApiUrl() . '/refresh?plainRefresh=true');
            $request = $this->withDefaultHeaders($request);
            $request = $request->withBody($body);

            $response = $this->httpClient->sendRequest($request);
            if ($response->getStatusCode() !== 200) {
                throw new ApiRequestException((string)$response->getBody(), $response->getStatusCode());
            }

            $data = json_decode((string)$response->getBody(), true);

            return [
                'jwt' => $data['token'],
                'refreshToken' => $data['refreshToken'],
            ];
        } finally {
            $this->authState->isLoading = false;
        }

    }

    /**
     * @throws ClientExceptionInterface
     */
    public function fetchUserDetails(string $jwt): array
    {
        $this->authState->isLoading = true;

        try {

            $request = $this->requestFactory->createRequest('GET', $this->authConfig->getApiUrl() . '/me');
            $request = $this->withDefaultHeaders($request, [
                'Authorization' => 'Bearer ' . $jwt,
            ]);

            $response = $this->httpClient->sendRequest($request);
            if ($response->getStatusCode() !== 200) {
                throw new ApiRequestException((string)$response->getBody(), $response->getStatusCode());
            }


            return json_decode($response->getBody(), true);
        } finally {
            $this->authState->isLoading = false;
        }
    }


    /**
     * @throws JwkKidNotFoundException|ClientExceptionInterface
     */
    public function getPublicKey(string $kid): array
    {
        if (isset($this->cachedJwks) && !$this->cachedJwks->isExpired()) {
            $cachedJwk = $this->cachedJwks->getJWKByKid($kid) ?? null;
            if ($cachedJwk) return $cachedJwk;
        }

        $request = $this->requestFactory->createRequest('GET', $this->authConfig->getJwkUrl());
        $request = $this->withDefaultHeaders($request);

        $response = $this->httpClient->sendRequest($request);
        if ($response->getStatusCode() !== 200) {
            throw new ApiRequestException((string)$response->getBody(), $response->getStatusCode());
        }

        $data = json_decode($response->getBody(), true)['keys'];

        if (count($data) > 0) {
            $this->cachedJwks = new CachedJwks($data, (time() + $this->authConfig->getCacheTtl()));
            foreach ($data as $d) {
                if ($d['kid'] === $kid) {
                    return $d;
                }
            }
        }

        throw new JwkKidNotFoundException('JWK not found for kid ' . $kid);
    }

    public function logout(string $jwt): bool
    {
        $this->authState->isLoading = true;

        try {

            $request = $this->requestFactory->createRequest('POST', $this->authConfig->getApiUrl() . '/logout');
            $request = $this->withDefaultHeaders($request, [
                'Authorization' => 'Bearer ' . $jwt,
            ]);

            $response = $this->httpClient->sendRequest($request);
            if ($response->getStatusCode() !== 200) {
                throw new ApiRequestException((string)$response->getBody(), $response->getStatusCode());
            }

            return true;
        } finally {
            $this->authState->isLoading = false;
        }
    }
}