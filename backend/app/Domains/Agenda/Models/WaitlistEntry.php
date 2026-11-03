<?php

namespace App\Domains\Agenda\Models;

use App\Domains\Agenda\Enums\WaitlistPeriod;
use App\Domains\Agenda\Enums\WaitlistStatus;
use App\Domains\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// A patient waiting for a free time within a range of days.
class WaitlistEntry extends Model
{
    protected $fillable = [
        'patient_id',
        'procedure_id',
        'professional_id',
        'from_date',
        'to_date',
        'period',
        'notes',
        'status',
        'notified_at',
    ];

    /**
     * Casts the dates, the period and the status.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'from_date' => 'immutable_date',
            'to_date' => 'immutable_date',
            'period' => WaitlistPeriod::class,
            'status' => WaitlistStatus::class,
            'notified_at' => 'datetime',
        ];
    }

    /** Patient who is waiting. */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /** Procedure the patient wants. */
    public function procedure(): BelongsTo
    {
        return $this->belongsTo(Procedure::class);
    }

    /** Preferred professional; null accepts anyone. */
    public function professional(): BelongsTo
    {
        return $this->belongsTo(User::class, 'professional_id');
    }
}
