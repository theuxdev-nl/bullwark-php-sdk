<?php
namespace BullwarkSdk;

class AuthConfig
{
    private string $apiUrl;
    private string $jwkUrl;
    private string $customerUuid;
    private string $tenantUuid;
    private bool $devMode;
    private int $cacheTtl;

    public function __construct(
        string $apiUrl,
        string $jwkUrl,
        string $tenantUuid,
        string $customerUuid,
        bool $devMode = false,
        int $cacheTtl = 900
    ){
        $this->apiUrl = $apiUrl;
        $this->jwkUrl = $jwkUrl;
        $this->tenantUuid = $tenantUuid;
        $this->customerUuid = $customerUuid;
        $this->devMode = $devMode;
        $this->cacheTtl = $cacheTtl;
    }

    public function getTenantUuid(): ?string {
        return $this->tenantUuid ?? null;
    }

    public function setTenantUuid(string $tenantUuid): void
    {
        $this->tenantUuid = $tenantUuid;
    }

    public function getCustomerUuid(): ?string
    {
        return $this->customerUuid ?? null;
    }

    public function getApiUrl(): string
    {
        return $this->apiUrl;
    }

    public function getJwkUrl(): string
    {
        return $this->jwkUrl;
    }

    /**
     * @return bool
     */
    public function isDevMode(): bool
    {
        return $this->devMode;
    }

    /**
     * @return int
     */
    public function getCacheTtl(): int
    {
        return $this->cacheTtl;
    }
}