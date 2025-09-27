<?php

namespace BullwarkSdk;

class AbilityChecker
{
    private AuthConfig $authConfig;
    private AuthState $authState;
    public function __construct(AuthConfig $authConfig, AuthState $authState)
    {
        $this->authConfig = $authConfig;
        $this->authState = $authState;
    }

    public function userCan(string $uuid): bool
    {
        if(!$this->authState->getIsAuthenticated() || !$this->authState->getUser()) return false;
        $abilities = $this->authState->getUser()->getAbilities();
        foreach ($abilities as $ability) {
            if($ability->key === "*") return true;
            if($ability->uuid === $uuid) return true;
        }
        return false;
    }

    public function userCanKey(string $key): bool
    {
        if(!$this->authState->getIsAuthenticated() || !$this->authState->getUser()) return false;
        $abilities = $this->authState->getUser()->getAbilities();
        foreach ($abilities as $ability) {
            if($ability->key === "*") return true;
            if($ability->key === $key) return true;
        }
        return false;
    }
}