<?php
namespace BullwarkSdk;
class User{
    public ?string $uuid;
    public ?array $abilities;
    public ?array $roles;
    public ?bool $isAdmin;

    public function __construct(
        string $uuid,
        array $abilities,
        array $roles,
        bool $isAdmin
    )
    {
        $this->uuid = $uuid;
        $this->abilities = $abilities;
        $this->roles = $roles;
        $this->isAdmin = $isAdmin;
    }

    /**
     * @return array|null
     */
    public function getAbilities(): ?array
    {
        return $this->abilities;
    }

    /**
     * @return array|null
     */
    public function getRoles(): ?array
    {
        return $this->roles;
    }

    /**
     * @return string|null
     */
    public function getUuid(): ?string
    {
        return $this->uuid;
    }
}