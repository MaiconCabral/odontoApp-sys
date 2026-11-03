<?php

namespace App\Domains\Notifications\Models;

use App\Domains\Agenda\Models\Appointment;
use App\Domains\Notifications\Enums\PatientMessage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    public const STATUS_QUEUED = 'queued';

    public const STATUS_SENT = 'sent';

    public const STATUS_SKIPPED = 'skipped';

    public const STATUS_FAILED = 'failed';

    private const STATUS_LABELS = [
        self::STATUS_QUEUED => 'Na fila de envio',
        self::STATUS_SENT => 'Enviado',
        self::STATUS_SKIPPED => 'Não enviado: paciente sem e-mail',
        self::STATUS_FAILED => 'Falhou',
    ];

    protected $fillable = [
        'appointment_id',
        'type',
        'channel',
        'status',
        'error',
    ];

    /**
     * Casts the message type.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PatientMessage::class,
        ];
    }

    /** Appointment the message was about. */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /** Status shown to the reception. */
    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }
}
