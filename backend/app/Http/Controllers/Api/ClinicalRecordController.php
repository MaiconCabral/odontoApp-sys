<?php

namespace App\Http\Controllers\Api;

use App\Domains\Agenda\Models\Patient;
use App\Domains\Clinical\Actions\AddClinicalNote;
use App\Domains\Clinical\Actions\RecordToothCondition;
use App\Domains\Clinical\Models\ClinicalNote;
use App\Domains\Clinical\Models\ToothRecord;
use App\Http\Controllers\Controller;
use App\Http\Requests\ClinicalNoteRequest;
use App\Http\Requests\ToothRecordRequest;
use App\Http\Resources\ClinicalNoteResource;
use App\Http\Resources\ToothRecordResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class ClinicalRecordController extends Controller
{
    /** Latest odontogram changes listed below the chart. */
    private const HISTORY_LIMIT = 100;

    /** Lists the notes of the medical record, newest first. */
    public function notes(Patient $patient): AnonymousResourceCollection
    {
        Gate::authorize('viewRecord', $patient);

        $notes = ClinicalNote::query()
            ->with(['author', 'appointment.procedure'])
            ->where('patient_id', $patient->id)
            ->latest('id')
            ->get();

        return ClinicalNoteResource::collection($notes);
    }

    /** Appends a note to the medical record. */
    public function storeNote(ClinicalNoteRequest $request, Patient $patient, AddClinicalNote $addNote): JsonResponse
    {
        $note = $addNote->handle($patient, $request->toData(), $request->user());

        return (new ClinicalNoteResource($note->load(['author', 'appointment.procedure'])))->response()->setStatusCode(201);
    }

    /** Current state of each tooth and the latest changes. */
    public function odontogram(Patient $patient): JsonResponse
    {
        Gate::authorize('viewRecord', $patient);

        $records = ToothRecord::query()->with('author')->where('patient_id', $patient->id);

        return response()->json([
            'current' => ToothRecordResource::collection((clone $records)->current()->orderBy('tooth')->get()),
            'history' => ToothRecordResource::collection($records->latest('id')->limit(self::HISTORY_LIMIT)->get()),
        ]);
    }

    /** Records one change in the odontogram. */
    public function storeTooth(ToothRecordRequest $request, Patient $patient, RecordToothCondition $record): JsonResponse
    {
        $tooth = $record->handle($patient, $request->toData(), $request->user());

        return (new ToothRecordResource($tooth->load('author')))->response()->setStatusCode(201);
    }
}
