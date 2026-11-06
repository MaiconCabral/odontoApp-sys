<?php

namespace App\Domains\Agenda\Actions;

use App\Domains\Agenda\Enums\WaitlistStatus;
use App\Domains\Agenda\Models\Appointment;
use App\Domains\Agenda\Models\WaitlistEntry;
use App\Domains\Agenda\Support\DaySchedule;
use App\Domains\Notifications\Enums\StaffMessage;
use App\Domains\Notifications\Notifications\WaitlistOffer;
use App\Domains\Notifications\PatientNotifier;
use App\Domains\Notifications\StaffNotifier;
use App\Domains\Tenancy\Support\ClinicUrl;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class OfferFreedSlot
{
    /** Patients told about the same free time; the first to book it gets it. */
    public const MAX_OFFERS = 3;

    /** Receives the staff notifier. */
    public function __construct(private readonly StaffNotifier $staff) {}

    /**
     * Tells the oldest matching patients of the waiting list that a time is free.
     * Runs after the appointment that held the time was cancelled, rejected or moved.
     *
     * @return Collection<int, WaitlistEntry> the entries that were told
     */
    public function handle(Appointment $freed, CarbonImmutable $start, int $minutes, int $professionalId): Collection
    {
        if (! tenant()->preferences()['waiting_list'] || $start <= now()) {
            return collect();
        }

        $schedule = DaySchedule::load($start);

        $offered = $this->candidates($start, $minutes, $professionalId, $freed->patient_id)
            ->filter(fn (WaitlistEntry $entry) => $entry->period->includes($start)
                && $entry->patient->email
                && $schedule->problems($start, $entry->procedure->minutes(), $professionalId, null) === [])
            ->take(self::MAX_OFFERS)
            ->each(fn (WaitlistEntry $entry) => $this->offer($entry, $start, $professionalId))
            ->values();

        if ($offered->isNotEmpty()) {
            $names = $offered->map(fn (WaitlistEntry $entry) => $entry->patient->name)->join(', ', ' e ');
            $this->staff->send($freed, StaffMessage::WaitlistOffered, $names, $start);
        }

        return $offered;
    }

    /**
     * Waiting entries for the day, the professional and a procedure that fits, oldest first.
     *
     * @return Collection<int, WaitlistEntry>
     */
    private function candidates(CarbonImmutable $start, int $minutes, int $professionalId, ?int $ignorePatientId): Collection
    {
        return WaitlistEntry::query()
            ->with(['patient', 'procedure'])
            ->where('status', WaitlistStatus::Waiting)
            // Plain ranges instead of DATE() so the columns stay indexable; the date cast stores midnight.
            ->where('from_date', '<', $start->startOfDay()->addDay())
            ->where('to_date', '>=', $start->startOfDay())
            ->where(fn ($query) => $query->whereNull('professional_id')->orWhere('professional_id', $professionalId))
            ->when($ignorePatientId, fn ($query) => $query->where('patient_id', '!=', $ignorePatientId))
            ->oldest('id')
            ->get()
            ->filter(fn (WaitlistEntry $entry) => $entry->procedure->minutes() <= $minutes);
    }

    /** E-mails the offer with the booking page already filled in, and marks the entry. */
    private function offer(WaitlistEntry $entry, CarbonImmutable $start, int $professionalId): void
    {
        $clinic = tenant();
        $link = ClinicUrl::to($clinic, '/agendar', [
            'date' => $start->toDateString(),
            'time' => $start->format('H:i'),
            'professional_id' => (string) $professionalId,
            'procedure_id' => (string) $entry->procedure_id,
        ]);

        $entry->patient->notify(new WaitlistOffer([
            'clinic' => $clinic->name,
            'phone' => $clinic->phone,
            'patient' => strtok($entry->patient->name, ' '),
            'procedure' => $entry->procedure->name,
            'when' => PatientNotifier::when($start),
            'link' => $link,
        ]));

        $entry->update(['status' => WaitlistStatus::Notified, 'notified_at' => now()]);
    }
}
