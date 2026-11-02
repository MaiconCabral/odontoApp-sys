<?php

namespace App\Domains\Agenda\Models;

use Database\Factories\PatientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    /** @use HasFactory<PatientFactory> */
    use HasFactory;

    protected $fillable = ['name', 'phone'];

    /** Factory used by tests and seeds. */
    protected static function newFactory(): PatientFactory
    {
        return PatientFactory::new();
    }
}
