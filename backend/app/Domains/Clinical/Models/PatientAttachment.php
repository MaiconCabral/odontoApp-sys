<?php

namespace App\Domains\Clinical\Models;

use App\Domains\Agenda\Models\Patient;
use App\Domains\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// A file of the patient, kept on the private disk of the clinic.
class PatientAttachment extends Model
{
    public const DISK = 'local';

    protected $fillable = [
        'patient_id',
        'uploader_id',
        'original_name',
        'path',
        'mime',
        'size_bytes',
    ];

    /** Patient the file belongs to. */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /** Who sent the file. */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }
}
