<?php
namespace BullwarkSdk;
class AuthState {
    public bool $isLoggedIn;
    public bool $isLoading;
    public ?User $user;
    public ?string $detailsHash;
    public ?int $detailsHashSetAt;
    public ?string $storedJwtToken;
    public ?string $storedRefreshToken;
    public bool $isInitialized;

    public function __construct(AuthConfig $authConfig)
    {
        $this->authConfig = $authConfig;
        $this->isLoggedIn = false;
        $this->user = null;
        $this->detailsHash = null;
        $this->detailsHashSetAt = null;
        $this->storedJwtToken = null;
        $this->storedRefreshToken = null;
    }

    public function getUser(): ?User {
        return $this->user;
    }

    public function setUser(array $payload): ?User
    {

        $this->user = new User(
            $payload['sub'],
            $payload['https://bullwark.io/claims/abilities'],
            $payload['https://bullwark.io/claims/roles'],
            $payload['https://bullwark.io/claims/is_admin']
        );
        return $this->user;
    }

    /**
     * @param string|null $storedJwtToken
     */
    public function setStoredJwtToken(?string $storedJwtToken): void
    {
        $this->storedJwtToken = $storedJwtToken;
    }

    /**
     * @param bool $isLoggedIn
     */
    public function setIsAuthenticated(bool $isLoggedIn): void
    {
        $this->isAuthenticated = $isLoggedIn;
    }

    /**
     * @param string|null $storedRefreshToken
     */
    public function setStoredRefreshToken(?string $storedRefreshToken): void
    {
        $this->storedRefreshToken = $storedRefreshToken;
    }
}