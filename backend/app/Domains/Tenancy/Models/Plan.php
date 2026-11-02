<?php

namespace App\Domains\Tenancy\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class Plan extends Model
{
    use CentralConnection;

    public const TRIAL_CODE = 'pro';

    protected $fillable = [
        'code',
        'name',
        'price_cents',
        'max_professionals',
    ];
}
