<?php
namespace BullwarkSdk;

use BullwarkSdk\Exceptions\InvalidSignatureException;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\GuzzleException;

class ApiClient {
    private AuthConfig $authConfig;
    private AuthState $authState;

    private Client $apiClient;
    private Client $jwkClient;
    
    public function __construct(AuthConfig $authConfig, AuthState $authState)
    {
        $this->authConfig = $authConfig;
        $this->authState = $authState;

        $this->apiClient = new Client([
            'base_uri' => $this->authConfig->getApiUrl(),
            'http_errors' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'X-Tenant-Uuid' => $this->authConfig->getTenantUuid(),
            ]
        ]);

        $this->jwkClient = new Client([
            'base_uri' => $this->authConfig->getJwkUrl(),
            'http_errors' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'X-Tenant-Uuid' => $this->authConfig->getTenantUuid(),
            ]
        ]);
    }

    /**
     * @throws GuzzleException
     * @throws \Exception
     */
    public function login(string $email, string $password): array
    {
        $this->authState->isLoading = true;
        $response = $this->apiClient->request('POST', 'login?plainRefresh=true', [
            'body' => json_encode([
                'email' => $email,
                'password' => $password
            ])
        ]);

        if($response->getStatusCode() !== 200) {
            throw new \Exception($response->getBody());
        }

        $data = json_decode($response->getBody(), true);
        $this->authState->isLoading = false;
        return [
            'jwt' => $data['token'],
            'refreshToken' => $data['refreshToken'],
        ];
    }


    /**
     * @throws \Exception
     * @throws GuzzleException
     */
    public function refresh(?string $refreshToken = null): array
    {
        $this->authState->isLoading = true;
        $response = $this->apiClient->request('POST', '/refresh?plainRefresh=true', [
            'headers' => [
                'X-Refresh-Token' => $refreshToken ?? $this->authState->storedRefreshToken,
            ]
        ]);
        if($response->getStatusCode() !== 200) {
            throw new \Exception($response->getBody());
        }

        $data = json_decode($response->getBody(), true);
        $this->authState->isLoading = false;
        return [
            'jwt' => $data['token'],
            'refreshToken' => $data['refreshToken'],
        ];

    }

    /**
     * @throws GuzzleException
     */
    public function fetchUserDetails(?string $token = null): array
    {
        $response = $this->apiClient->request('GET', $this->authConfig->getApiUrl() . '/me', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token ?? $this->authState->storedJwtToken,
            ]
        ]);

        if($response->getStatusCode() !== 200) {
            throw new \Exception($response->getBody());
        }

        return json_decode($response->getBody(), true);
    }


    /**
     * @throws GuzzleException
     * @throws \Exception
     */
    public function getPublicKey(string $kid): array
    {
        $response = $this->jwkClient->get('http://localhost:8000/.well-known/jwks', [
            'headers' => [
                'X-Tenant-Uuid' => $this->authConfig->getTenantUuid(),
            ]
        ]);
        if($response->getStatusCode() !== 200) {
            $errorMessage = $response->getBody()->getContents();
            throw new \Exception($errorMessage);
        }

        $data = json_decode($response->getBody()->getContents(), true)['keys'];
        foreach($data as $d) {
            if($d['kid'] === $kid){
                return $d;
            }
        }

        throw new \Exception('Could not get public key for JWT');

    }

    public function logout(string $token)
    {
        $response = $this->apiClient->request('POST', 'logout', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
            ]
        ]);

        if($response->getStatusCode() !== 200) {
            throw new \Exception($response->getBody());
        }
    }
}