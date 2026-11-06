<?php

namespace App\Domains\Agenda\Models;

use App\Domains\Agenda\Enums\AppointmentStatus;
use App\Domains\Finance\Models\Payment;
use App\Domains\Identity\Models\User;
use App\Domains\Notifications\Models\NotificationLog;
use Carbon\CarbonImmutable;
use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory;

    public const SOURCE_INTERNAL = 'internal';

    public const SOURCE_PUBLIC = 'public';

    /** Longest an appointment may last (procedures go up to 4 hours), with room to spare. */
    public const MAX_MINUTES = 24 * 60;

    protected $fillable = [
        'patient_id',
        'professional_id',
        'room_id',
        'procedure_id',
        'price_cents',
        'starts_at',
        'ends_at',
        'status',
        'tone',
        'source',
        'notes',
        'cancel_reason',
    ];

    /**
     * Casts dates and status.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'status' => AppointmentStatus::class,
            'price_cents' => 'integer',
            'reminder_sent_at' => 'datetime',
        ];
    }

    /** Factory used by tests and seeds. */
    protected static function newFactory(): AppointmentFactory
    {
        return AppointmentFactory::new();
    }

    /** Patient of the appointment. */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /** User who attends the appointment. */
    public function professional(): BelongsTo
    {
        return $this->belongsTo(User::class, 'professional_id');
    }

    /** Procedure booked, with the length and price it has today. */
    public function procedure(): BelongsTo
    {
        return $this->belongsTo(Procedure::class);
    }

    /** Length booked, which stays the same if the procedure changes later. */
    public function minutes(): int
    {
        return (int) $this->starts_at->diffInMinutes($this->ends_at);
    }

    /** Room or chair used. */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /** Status history, oldest first. */
    public function statusChanges(): HasMany
    {
        return $this->hasMany(AppointmentStatusChange::class)->orderBy('id');
    }

    /** Messages sent to the patient about this appointment, newest first. */
    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class)->latest('id');
    }

    /** Payments of this appointment, voided ones included, newest first. */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('id');
    }

    /**
     * "none" for a free appointment, else "pending", "partial" or "paid", from the valid payments.
     * Uses the paid_cents sum when the query loaded it (withSum), so lists do not query per row.
     */
    public function paymentStatus(): string
    {
        $paid = $this->paidCents();

        return match (true) {
            $this->price_cents === 0 => 'none',
            $paid <= 0 => 'pending',
            $paid < $this->price_cents => 'partial',
            default => 'paid',
        };
    }

    /** Sum of the valid payments, from withSum when loaded. */
    public function paidCents(): int
    {
        if (array_key_exists('paid_cents', $this->attributes)) {
            return (int) $this->attributes['paid_cents'];
        }

        return (int) $this->payments()->valid()->sum('amount_cents');
    }

    /** Loads the sum of the valid payments as paid_cents. */
    public function scopeWithPaidCents(Builder $query): Builder
    {
        return $query->withSum(['payments as paid_cents' => fn ($payments) => $payments->whereNull('voided_at')], 'amount_cents');
    }

    /** Loads the sum of the valid payments as paid_cents on this model. */
    public function loadPaidCents(): static
    {
        return $this->loadSum(['payments as paid_cents' => fn ($payments) => $payments->whereNull('voided_at')], 'amount_cents');
    }

    /** Appointments that hold their time in the agenda. */
    public function scopeOccupying(Builder $query): Builder
    {
        return $query->whereIn('status', AppointmentStatus::occupying());
    }

    /** Appointments that cross the given interval, bounded below so the starts_at index skips the history. */
    public function scopeOverlapping(Builder $query, mixed $start, mixed $end): Builder
    {
        $earliest = CarbonImmutable::parse($start)->subMinutes(self::MAX_MINUTES);

        return $query->where('starts_at', '>', $earliest)->where('starts_at', '<', $end)->where('ends_at', '>', $start);
    }

    /** Moves to a status and writes it in the history. */
    public function moveTo(AppointmentStatus $status, ?User $author, ?string $reason = null): void
    {
        $previous = $this->exists ? $this->getOriginal('status') : null;

        $this->status = $status;
        $this->save();

        $this->statusChanges()->create([
            'from_status' => $previous,
            'to_status' => $status,
            'reason' => $reason,
            'changed_by' => $author?->id,
        ]);
    }
}
