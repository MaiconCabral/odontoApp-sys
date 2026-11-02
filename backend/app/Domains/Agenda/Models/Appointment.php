<?php

namespace App\Domains\Agenda\Models;

use App\Domains\Identity\Models\User;
use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory;

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'patient_id',
        'professional_id',
        'room_id',
        'starts_at',
        'ends_at',
        'procedure',
        'status',
        'tone',
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

    /** Room or chair used. */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
