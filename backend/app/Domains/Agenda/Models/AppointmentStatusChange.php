<?php

namespace App\Domains\Agenda\Models;

use App\Domains\Agenda\Enums\AppointmentStatus;
use App\Domains\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentStatusChange extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'appointment_id',
        'from_status',
        'to_status',
        'reason',
        'changed_by',
    ];

    /**
     * Casts the statuses to the enum.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'from_status' => AppointmentStatus::class,
            'to_status' => AppointmentStatus::class,
            'created_at' => 'datetime',
        ];
    }

    /** User who made the change, null for a public request. */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
