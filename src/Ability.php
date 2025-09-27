<?php
namespace BullwarkSdk;
class Ability
{
    public string $uuid;
    public string $key;
    public string $label;

    public function __construct(
        string $uuid,
        string $key,
        string $label
    )
    {
        $this->uuid = $uuid;
        $this->key = $key;
        $this->label = $label;
    }
}