<?php

namespace App\Http\Controllers\Api;

use App\Domains\Agenda\Actions\AcceptAppointmentRequest;
use App\Domains\Agenda\Actions\ChangeAppointmentStatus;
use App\Domains\Agenda\Actions\RescheduleAppointment;
use App\Domains\Agenda\Actions\ScheduleAppointment;
use App\Domains\Agenda\Enums\AppointmentStatus;
use App\Domains\Agenda\Models\Appointment;
use App\Http\Controllers\Controller;
use App\Http\Requests\AcceptAppointmentRequestRequest;
use App\Http\Requests\AppointmentRequest;
use App\Http\Requests\ChangeAppointmentStatusRequest;
use App\Http\Resources\AppointmentResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class AppointmentController extends Controller
{
    private const DETAILS = ['patient', 'professional', 'room', 'statusChanges.author'];

    /** Lists the patient requests waiting for an answer, oldest first. */
    public function requests(): AnonymousResourceCollection
    {
        Gate::authorize('manage', Appointment::class);

        $requests = Appointment::query()
            ->with(['patient', 'professional', 'room'])
            ->where('status', AppointmentStatus::Requested)
            ->orderBy('created_at')
            ->get();

        return AppointmentResource::collection($requests);
    }

    /** Returns an appointment with its history. */
    public function show(Appointment $appointment): AppointmentResource
    {
        Gate::authorize('view', $appointment);

        return new AppointmentResource($appointment->load(self::DETAILS));
    }

    /** Books an appointment from the panel. */
    public function store(AppointmentRequest $request, ScheduleAppointment $schedule): AppointmentResource
    {
        $appointment = $schedule->handle($request->toData(), $request->user());

        return new AppointmentResource($appointment->load(self::DETAILS));
    }

    /** Moves or edits an appointment. */
    public function update(AppointmentRequest $request, Appointment $appointment, RescheduleAppointment $reschedule): AppointmentResource
    {
        $appointment = $reschedule->handle($appointment, $request->toData(), $request->user());

        return new AppointmentResource($appointment->load(self::DETAILS));
    }

    /** Changes the status of an appointment. */
    public function status(ChangeAppointmentStatusRequest $request, Appointment $appointment, ChangeAppointmentStatus $change): AppointmentResource
    {
        $appointment = $change->handle($appointment, $request->status(), $request->input('reason'), $request->user());

        return new AppointmentResource($appointment->load(self::DETAILS));
    }

    /** Accepts a patient request in a free room. */
    public function accept(AcceptAppointmentRequestRequest $request, Appointment $appointment, AcceptAppointmentRequest $accept): AppointmentResource
    {
        $appointment = $accept->handle($appointment, $request->integer('room_id'), $request->user());

        return new AppointmentResource($appointment->load(self::DETAILS));
    }
}
