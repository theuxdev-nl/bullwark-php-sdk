<?php

namespace BullwarkSdk\jwt;

use BullwarkSdk\ApiClient;
use BullwarkSdk\AuthConfig;
use BullwarkSdk\Exceptions\InvalidSignatureException;
use BullwarkSdk\Exceptions\JwkKidNotFoundException;
use BullwarkSdk\Exceptions\JwtExpiredException;
use BullwarkSdk\Exceptions\TokenMalformedException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use GuzzleHttp\Exception\GuzzleException;

class JwtVerifier
{
    private AuthConfig $authConfig;
    private ApiClient $apiClient;
    private ?array $JWKs;

    public function __construct(AuthConfig $authConfig, ApiClient $apiClient)
    {
        $this->authConfig = $authConfig;
        $this->apiClient = $apiClient;
    }

    /**
     * @throws InvalidSignatureException
     * @throws JwtExpiredException
     * @throws TokenMalformedException|JwkKidNotFoundException
     */
    public function checkIfTokenValid(string $jwt): bool
    {
        $header = $this->getJwtHeader($jwt);
        $payload = $this->getJwtPayload($jwt);
        if($payload['exp'] < time()) throw new JwtExpiredException('Token expired!');

        if($this->authConfig->isDevMode()){
            return true;
        }

        $pubKey = $this->apiClient->getPublicKey($header['kid']);
        try {
            $keyObject = JWK::parseKey($pubKey);
            JWT::decode($jwt, $keyObject);
            return true;
        } catch (ExpiredException $e) {
            throw new JwtExpiredException("JWT Token Expired!");
        } catch (\Exception $e) {
            throw new InvalidSignatureException("JWT verification failed: " . $e->getMessage());
        }

    }

    public function getJwtHeader(string $jwt): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new TokenMalformedException('Invalid JWT format');
        }
        return json_decode($this->decode($parts[0]), true);
    }

    public function getJwtPayload(string $jwt): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new TokenMalformedException('Invalid JWT format');
        }
        return json_decode($this->decode($parts[1]), true);
    }

    /**
     * @param string $jwtToken
     * @return array
     * @throws InvalidSignatureException
     * @throws JwtExpiredException
     * @throws TokenMalformedException|JwkKidNotFoundException
     */
    public function getVerifiedTokenData(string $jwtToken): array
    {
        if($this->authConfig->isDevMode()){
            return $this->getParsedJwt($jwtToken);
        }

        $headerData = $this->getParsedJwt($jwtToken);
        $pubKey = $this->apiClient->getPublicKey($headerData['header']['kid']);

        try {
            // JWK::parseKey already returns a Key object - use it directly
            $keyObject = JWK::parseKey($pubKey);
            $decoded = JWT::decode($jwtToken, $keyObject);

            return [
                'header' => $headerData['header'],
                'payload' => (array) $decoded,
                'signature' => $headerData['signature']
            ];
        } catch (ExpiredException $e) {
            throw new JwtExpiredException("JWT Token Expired!");
        } catch (\Exception $e) {
            throw new InvalidSignatureException("JWT verification failed: " . $e->getMessage());
        }
    }

    /**
     * @throws TokenMalformedException
     */
    private function getParsedJwt(string $jwtToken): array
    {
        $parts = explode('.', $jwtToken);

        if (count($parts) !== 3) {
            throw new TokenMalformedException('Invalid JWT format');
        }

        list($header, $payload, $signature) = $parts;
        $decodedHeader = $this->decode($header);
        $decodedPayload = $this->decode($payload);

        return [
            'header' => json_decode($decodedHeader, true),
            'payload' => json_decode($decodedPayload, true),
            'signature' => $signature
        ];
    }

    private function decode($data): string
    {
        $padded = $data . str_repeat('=', (4 - strlen($data) % 4) % 4);
        $base64 = strtr($padded, '-_', '+/');
        return base64_decode($base64);
    }
}