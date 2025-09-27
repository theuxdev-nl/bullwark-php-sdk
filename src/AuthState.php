<?php
namespace BullwarkSdk;
class AuthState {
    private AuthConfig $authConfig;
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

    public function setUser(?array $userData): ?User
    {
        if($userData === null) {
            $this->user = null;
            return null;
        }

        $this->user = new User(
            $userData['uuid'],
            $userData['firstName'],
            $userData['lastName'],
            $userData['email'],
            array_map(function($ability){
                return new Ability(
                    $ability['uuid'],
                    $ability['key'],
                    $ability['label']
                );
            }, $userData['abilities']),
            array_map(function($role){
                return new Role(
                    $role['uuid'],
                    $role['key'],
                    $role['label']
                );
            }, $userData['roles']),
            isset($userData['primaryRole']) ?
                new Role(
                    $userData['primaryRole']['uuid'],
                    $userData['primaryRole']['key'],
                    $userData['primaryRole']['label']
                ) : null,
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

    /**
     * @param string|null $detailsHash
     */
    public function setDetailsHash(?string $detailsHash): void
    {
        $this->detailsHash = $detailsHash;
        $this->detailsHashSetAt = $detailsHash != null ? time() : null;
    }

    public function isCacheExpired(): ?bool
    {
        if($this->detailsHashSetAt === null) return null;
        return time() < ($this->detailsHashSetAt + $this->authConfig->getCacheTtl());
    }

    public function shouldFetchUserDetails(string $detailsHash): bool
    {
        return $this->getUser() === null ||
            $this->isCacheExpired() ||
            ($this->detailsHash !== null && $this->detailsHash !== $detailsHash);
    }
}