<?php

namespace App\Domains\Agenda\Models;

use App\Domains\Identity\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleBlock extends Model
{
    protected $fillable = [
        'professional_id',
        'starts_at',
        'ends_at',
        'reason',
    ];

    /**
     * Casts start and end to dates.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /** Professional on leave, or null when the whole clinic is closed. */
    public function professional(): BelongsTo
    {
        return $this->belongsTo(User::class, 'professional_id');
    }

    /** Blocks that cross the given interval. */
    public function scopeOverlapping(Builder $query, mixed $start, mixed $end): Builder
    {
        return $query->where('starts_at', '<', $end)->where('ends_at', '>', $start);
    }
}
