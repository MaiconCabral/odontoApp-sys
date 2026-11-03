<?php

namespace App\Domains\Clinical\Actions;

use App\Domains\Agenda\Models\Patient;
use App\Domains\Clinical\Models\PatientAttachment;
use App\Domains\Identity\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StoreAttachment
{
    /**
     * Saves the file on the private disk, under the folder of the clinic, and records it.
     * The folder carries the clinic id explicitly: the filesystem bootstrapper of the
     * tenancy package would also move sessions and asset() of every clinic request.
     */
    public function handle(Patient $patient, UploadedFile $file, User $uploader): PatientAttachment
    {
        $folder = self::folderFor($patient);
        $path = $file->store($folder, PatientAttachment::DISK);

        try {
            return DB::transaction(fn () => PatientAttachment::create([
                'patient_id' => $patient->id,
                'uploader_id' => $uploader->id,
                'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
                'path' => $path,
                'mime' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
            ]));
        } catch (\Throwable $exception) {
            // A file without its row would never be listed nor deleted.
            Storage::disk(PatientAttachment::DISK)->delete($path);

            throw $exception;
        }
    }

    /** Private folder of a patient inside the folder of the current clinic. */
    public static function folderFor(Patient $patient): string
    {
        return 'clinics/'.tenant()->getTenantKey()."/patients/{$patient->id}";
    }
}
