<?php

namespace App\Domains\Identity\DTOs;

use App\Domains\Identity\Enums\UserRole;

readonly class UserData
{
    /** Data to create or update a team member. */
    public function __construct(
        public string $name,
        public string $email,
        public UserRole $role,
        public ?string $specialty,
        public bool $attendsPatients,
        public bool $active = true,
    ) {}

    /**
     * The role decides who attends patients, except for admins, who may do both.
     */
    public function attends(): bool
    {
        return match ($this->role) {
            UserRole::Professional => true,
            UserRole::Reception => false,
            UserRole::Admin => $this->attendsPatients,
        };
    }
}
