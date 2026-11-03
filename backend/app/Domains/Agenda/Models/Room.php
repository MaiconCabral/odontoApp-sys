<?php

namespace App\Domains\Agenda\Models;

use Database\Factories\RoomFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    /** @use HasFactory<RoomFactory> */
    use HasFactory;

    protected $fillable = ['name', 'active'];

    protected $attributes = [
        'active' => true,
    ];

    /**
     * Casts the status.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    /** Factory used by tests and seeds. */
    protected static function newFactory(): RoomFactory
    {
        return RoomFactory::new();
    }

    /** Appointments held in this room. */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /** Rooms offered for new bookings. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }
}
