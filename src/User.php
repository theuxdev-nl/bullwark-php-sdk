<?php
namespace BullwarkSdk;
class User{
    public ?string $uuid;
    public ?string $firstName;
    public ?string $lastName;
    public ?string $email;
    public ?array $abilities;
    public ?array $roles;
    public ?Role $primaryRole;

    public function __construct(
        string $uuid,
        ?string $firstName,
        ?string $lastName,
        string $email,
        array $abilities,
        array $roles,
        ?Role $primaryRole
    )
    {
        $this->uuid = $uuid;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
        $this->abilities = $abilities;
        $this->roles = $roles;
        $this->primaryRole = $primaryRole;
    }

    /**
     * @return array|null
     */
    public function getAbilities(): ?array
    {
        return $this->abilities;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @return string|null
     */
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    /**
     * @return string|null
     */
    public function getLastName(): ?string
    {
        return $this->lastName;
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