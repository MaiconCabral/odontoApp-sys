<?php

namespace App\Domains\Clinical\Models;

use App\Domains\Agenda\Models\Appointment;
use App\Domains\Agenda\Models\Patient;
use App\Domains\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// A note of the medical record. Notes are never edited or deleted; a correction amends one.
class ClinicalNote extends Model
{
    protected $fillable = [
        'patient_id',
        'appointment_id',
        'author_id',
        'amends_id',
        'body',
    ];

    /** Patient the note is about. */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /** Appointment the note was written for, if any. */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /** Who wrote the note. */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** Earlier note this one corrects. */
    public function amends(): BelongsTo
    {
        return $this->belongsTo(self::class, 'amends_id');
    }
}
