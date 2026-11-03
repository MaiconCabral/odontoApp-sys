<?php

namespace App\Domains\Platform\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

// Someone who manages clinics and plans. Signs in on the central domain with the "platform" guard.
class PlatformAdmin extends Authenticatable
{
    use CentralConnection;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Hashes the password on save.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }
}
