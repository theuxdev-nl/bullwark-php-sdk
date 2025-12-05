<?php

namespace BullwarkSdk\Support;

use BullwarkSdk\Exceptions\JwkKidNotFoundException;
use Firebase\JWT\JWK;

class CachedJwks
{
    /* @var array<\Firebase\JWT\JWK> $jwks */
    private array $jwks;
    private int $expiresAt;

    public function __construct(
        array $jwks,
        int $expiresAt
    ){
        $this->jwks = $jwks;
        $this->expiresAt = $expiresAt;
    }

    public function getJWKByKid(string $kid): array
    {
        $jwk = array_filter($this->jwks, function ($jwk) use ($kid) {
            return $jwk['kid'] === $kid;
        });

        if (empty($jwk)) {
            throw new JwkKidNotFoundException('JWK not found!');
        }

        return $jwk[0];
    }

    public function isExpired(): bool
    {
        return $this->expiresAt < time();
    }

}