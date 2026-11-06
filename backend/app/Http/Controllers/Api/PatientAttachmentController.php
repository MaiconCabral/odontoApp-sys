<?php

namespace App\Http\Controllers\Api;

use App\Domains\Agenda\Models\Patient;
use App\Domains\Clinical\Actions\StoreAttachment;
use App\Domains\Clinical\Models\PatientAttachment;
use App\Http\Controllers\Controller;
use App\Http\Requests\PatientAttachmentRequest;
use App\Http\Resources\PatientAttachmentResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PatientAttachmentController extends Controller
{
    /** Files per page of the patient record. */
    private const PER_PAGE = 30;

    /** Lists the files of the patient, newest first, in pages. */
    public function index(Patient $patient): AnonymousResourceCollection
    {
        Gate::authorize('viewRecord', $patient);

        $attachments = PatientAttachment::query()
            ->with('uploader:id,name')
            ->where('patient_id', $patient->id)
            ->latest('id')
            ->paginate(self::PER_PAGE);

        // Every row belongs to this patient, so it is set once instead of loaded per row.
        $attachments->getCollection()->each->setRelation('patient', $patient);

        return PatientAttachmentResource::collection($attachments);
    }

    /** Receives a file of the patient. */
    public function store(PatientAttachmentRequest $request, Patient $patient, StoreAttachment $store): JsonResponse
    {
        $attachment = $store->handle($patient, $request->upload(), $request->user());

        return (new PatientAttachmentResource($attachment->load(['uploader', 'patient'])))->response()->setStatusCode(201);
    }

    /** Streams the file from the private disk; there is no public link to it. */
    public function download(PatientAttachment $attachment): StreamedResponse
    {
        Gate::authorize('viewRecord', $attachment->patient);

        return Storage::disk(PatientAttachment::DISK)->download($attachment->path, $attachment->original_name, [
            'Content-Type' => $attachment->mime,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /** Removes the file and its row. */
    public function destroy(PatientAttachment $attachment): Response
    {
        Gate::authorize('deleteAttachment', $attachment->patient);

        Storage::disk(PatientAttachment::DISK)->delete($attachment->path);
        $attachment->delete();

        return response()->noContent();
    }
}
