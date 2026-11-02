<?php

namespace App\Domains\Identity\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Professional = 'professional';
    case Reception = 'reception';

    /** Name of the role shown to users. */
    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Professional => 'Profissional',
            self::Reception => 'Recepção',
        };
    }
}
