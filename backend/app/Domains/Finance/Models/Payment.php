<?php

namespace App\Domains\Finance\Models;

use App\Domains\Agenda\Models\Appointment;
use App\Domains\Agenda\Models\Patient;
use App\Domains\Finance\Enums\PaymentMethod;
use App\Domains\Identity\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Money received for an appointment. Undoing one is a void with a reason, so the history stays.
class Payment extends Model
{
    protected $fillable = [
        'appointment_id',
        'patient_id',
        'amount_cents',
        'method',
        'paid_at',
        'notes',
        'recorded_by',
        'voided_at',
        'voided_by',
        'void_reason',
    ];

    /**
     * Casts the method and the dates.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'method' => PaymentMethod::class,
            'paid_at' => 'immutable_date',
            'voided_at' => 'datetime',
        ];
    }

    /** Appointment that was paid. */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /** Patient who paid. */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /** Who recorded the payment. */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /** Who voided the payment. */
    public function voider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    /** Payments that count: the ones not voided. */
    public function scopeValid(Builder $query): Builder
    {
        return $query->whereNull('voided_at');
    }

    /** Payments dated between two days, inclusive, as a plain range so the (paid_at, voided_at) index is used. */
    public function scopePaidBetween(Builder $query, CarbonInterface $first, CarbonInterface $last): Builder
    {
        $column = $query->qualifyColumn('paid_at');

        return $query
            ->where($column, '>=', CarbonImmutable::instance($first)->startOfDay())
            ->where($column, '<', CarbonImmutable::instance($last)->startOfDay()->addDay());
    }
}
