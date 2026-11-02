<?php

namespace App\Domains\Agenda\Models;

use Database\Factories\RoomFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    /** @use HasFactory<RoomFactory> */
    use HasFactory;

    protected $fillable = ['name'];

    /** Factory used by tests and seeds. */
    protected static function newFactory(): RoomFactory
    {
        return RoomFactory::new();
    }
}
