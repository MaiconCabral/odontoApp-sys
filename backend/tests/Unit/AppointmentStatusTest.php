<?php

use App\Domains\Agenda\Enums\AppointmentStatus as Status;

it('allows only the moves of the flow', function (Status $from, Status $to, bool $allowed) {
    expect($from->canTransitionTo($to))->toBe($allowed);
})->with([
    'request accepted' => [Status::Requested, Status::Scheduled, true],
    'request rejected' => [Status::Requested, Status::Rejected, true],
    'request cannot be confirmed' => [Status::Requested, Status::Confirmed, false],
    'scheduled confirmed' => [Status::Scheduled, Status::Confirmed, true],
    'scheduled completed' => [Status::Scheduled, Status::Completed, true],
    'confirmed missed' => [Status::Confirmed, Status::NoShow, true],
    'confirmed cannot be rejected' => [Status::Confirmed, Status::Rejected, false],
    'completed is final' => [Status::Completed, Status::Cancelled, false],
    'cancelled is final' => [Status::Cancelled, Status::Scheduled, false],
]);

it('holds the time only while the appointment is open', function () {
    expect(array_filter(Status::cases(), fn (Status $status) => $status->occupiesSlot()))
        ->toBe([Status::Requested, Status::Scheduled, Status::Confirmed]);
});

it('asks a reason only to cancel or reject', function () {
    expect(Status::Cancelled->requiresReason())->toBeTrue()
        ->and(Status::Rejected->requiresReason())->toBeTrue()
        ->and(Status::Confirmed->requiresReason())->toBeFalse();
});
