<?php

namespace BullwarkSdk;

use BullwarkSdk\Exceptions\InvalidSignatureException;
use BullwarkSdk\Exceptions\JwtExpiredException;
use BullwarkSdk\Exceptions\TokenMalformedException;
use BullwarkSdk\jwt\JwtVerifier;
use GuzzleHttp\Exception\GuzzleException;

class BullwarkSdk
{
    private AuthConfig $authConfig;
    private AuthState $authState;
    private ApiClient $apiClient;
    private JwtVerifier $jwtVerifier;
    private AbilityChecker $abilityChecker;

    public function __construct(
        string $apiUrl,
        string $jwkUrl,
        string $tenantUuid,
        string $customerUuid,
        ?bool  $devMode = false,
        ?int   $cacheTtl = 900
    )
    {
        $this->authConfig = new AuthConfig(
            $apiUrl,
            $jwkUrl,
            $tenantUuid,
            $customerUuid,
            $devMode,
            $cacheTtl
        );

        $this->authState = new AuthState($this->authConfig);
        $this->apiClient = new ApiClient($this->authConfig, $this->authState);
        $this->abilityChecker = new AbilityChecker($this->authConfig, $this->authState);
        $this->jwtVerifier = new JwtVerifier($this->authConfig, $this->apiClient);

        if ($devMode) {
            echo "Bullwark SDK: Dev mode enabled. JWT signatures will NOT be verified.\n";
        }
    }

    public function setTenantUuid(string $tenantUuid): void
    {
        $this->authConfig->setTenantUuid($tenantUuid);
    }

    /**
     * @throws GuzzleException
     * @throws \Exception
     */
    public function login(string $email, string $password): bool
    {
        $this->invalidateSession();
        [$jwt, $refreshToken] = $this->apiClient->login($email, $password);
        $userData = $this->apiClient->fetchUserDetails($jwt);

        $this->authState->storedJwtToken = $jwt;
        $this->authState->storedRefreshToken = $refreshToken;
        $this->authState->isLoggedIn = true;

        $this->authState->setUser($userData);
        return true;
    }

    /**
     * @throws InvalidSignatureException
     * @throws GuzzleException
     * @throws JwtExpiredException
     * @throws TokenMalformedException
     * @throws \Exception
     */
    public function authenticate(string $jwt): bool
    {
        $this->invalidateSession();
        $this->jwtVerifier->checkIfTokenValid($jwt);
        $payload = $this->jwtVerifier->getJwtPayload($jwt);

        if (
            !isset($this->authState->user) ||
            $this->authState->user->uuid !== $payload['userUuid'] ||
            $this->authState->detailsHash !== $payload['detailsHash']) {

            $userData = $this->apiClient->fetchUserDetails($jwt);
            $this->authState->setUser($userData);
        }

        $this->authState->storedJwtToken = $jwt;
        $this->authState->isLoggedIn = true;
        $this->authState->detailsHash = $payload['detailsHash'];
        return true;
    }

    /**
     * @throws InvalidSignatureException
     * @throws JwtExpiredException
     * @throws GuzzleException
     * @throws TokenMalformedException
     * @throws \Exception
     */
    public function refresh(?string $refreshToken = null): bool
    {
        [$jwt, $refreshToken] = $this->apiClient->refresh($refreshToken);
        $this->jwtVerifier->checkIfTokenValid($jwt);
        $payload = $this->jwtVerifier->getJwtPayload($jwt);

        if (
            !isset($this->authState->user) ||
            $this->authState->user->uuid !== $payload['userUuid'] ||
            $this->authState->detailsHash !== $payload['detailsHash']) {

            $userData = $this->apiClient->fetchUserDetails($jwt);
            $this->authState->setUser($userData);
        }

        $this->authState->storedJwtToken = $jwt;
        $this->authState->isLoggedIn = true;
        $this->authState->detailsHash = $payload['detailsHash'];
        $this->authState->storedRefreshToken = $refreshToken;
        return true;

    }

    /**
     * @throws \Exception
     */
    public function logout(?string $token = null): bool
    {
        $this->apiClient->logout($token ?? $this->authState->storedJwtToken);
        $this->invalidateSession();

        return true;
    }

    public function userCan(string $uuid): bool
    {
        return $this->abilityChecker->userCan($uuid);
    }

    public function userCanKey(string $key): bool
    {
        return $this->abilityChecker->userCanKey($key);
    }

    private function invalidateSession(): void
    {
        $this->authState->isLoggedIn = false;
        $this->authState->user = null;
        $this->authState->detailsHash = null;
        $this->authState->storedJwtToken = null;
        $this->authState->storedRefreshToken = null;
    }


    // Getters
    public function getIsLoggedIn(): bool
    {
        return $this->authState->isLoggedIn;
    }

    public function getIsInitializing(): bool
    {
        return $this->authState->isInitialized;
    }

    public function getIsLoading(): bool
    {
        return $this->authState->isLoading;
    }

}