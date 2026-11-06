<?php

namespace App\Domains\Finance\Queries;

use App\Domains\Agenda\Enums\AppointmentStatus;
use App\Domains\Agenda\Models\Appointment;
use App\Domains\Agenda\Models\Procedure;
use App\Domains\Finance\Models\Payment;
use App\Domains\Identity\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

// Report of a period grouped by day, professional or procedure. Three queries, whatever the size.
class ReportQuery
{
    public const GROUPS = ['day', 'professional', 'procedure'];

    // Appointments that count: the ones that happened or are still to happen, plus no-shows.
    private const COUNTED = [AppointmentStatus::Scheduled, AppointmentStatus::Confirmed, AppointmentStatus::Completed, AppointmentStatus::NoShow];

    // Appointments whose price is expected: the no-shows are left out.
    private const EXPECTED = [AppointmentStatus::Scheduled, AppointmentStatus::Confirmed, AppointmentStatus::Completed];

    /**
     * Rows of the report, the totals and the money received by method.
     *
     * Expected revenue follows the date of the appointment. Received money follows the date of the
     * payment (cash basis); grouped by professional or procedure, it goes to the paid appointment.
     *
     * @return array<string, mixed>
     */
    public function for(CarbonImmutable $from, CarbonImmutable $to, string $group): array
    {
        $start = $from->startOfDay();
        $end = $to->endOfDay();

        $appointments = $this->appointments($start, $end, $group);
        $received = $this->received($start, $end, $group);
        $labels = $this->labels($group, $appointments->keys()->merge($received->keys())->unique());

        $rows = $labels->map(function (string $label, string $key) use ($appointments, $received) {
            $row = $appointments->get($key);
            $completed = (int) ($row->completed ?? 0);
            $missed = (int) ($row->missed ?? 0);

            return [
                'key' => $key,
                'label' => $label,
                'appointments' => (int) ($row->total ?? 0),
                'completed' => $completed,
                'noShow' => $missed,
                'absenceRate' => $completed + $missed > 0 ? round($missed / ($completed + $missed) * 100, 1) : null,
                'expectedCents' => (int) ($row->expected ?? 0),
                'receivedCents' => (int) ($received->get($key) ?? 0),
            ];
        })->values();

        $completed = $rows->sum('completed');
        $missed = $rows->sum('noShow');

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'group' => $group,
            'rows' => $group === 'day' ? $rows->sortBy('key')->values()->all() : $rows->sortByDesc('expectedCents')->values()->all(),
            'totals' => [
                'appointments' => $rows->sum('appointments'),
                'completed' => $completed,
                'noShow' => $missed,
                'absenceRate' => $completed + $missed > 0 ? round($missed / ($completed + $missed) * 100, 1) : null,
                'expectedCents' => $rows->sum('expectedCents'),
                'receivedCents' => $rows->sum('receivedCents'),
            ],
            'byMethod' => $this->byMethod($start, $end),
        ];
    }

    /**
     * Counts and expected revenue of the appointments, keyed by the group.
     *
     * @return Collection<string, object>
     */
    private function appointments(CarbonImmutable $start, CarbonImmutable $end, string $group): Collection
    {
        $key = match ($group) {
            'day' => 'DATE(starts_at)',
            'professional' => 'professional_id',
            'procedure' => 'procedure_id',
        };

        return Appointment::query()
            ->whereIn('status', self::COUNTED)
            ->whereBetween('starts_at', [$start, $end])
            ->selectRaw("{$key} as group_key, COUNT(*) as total")
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed', [AppointmentStatus::Completed->value])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as missed', [AppointmentStatus::NoShow->value])
            ->selectRaw('SUM(CASE WHEN status IN (?, ?, ?) THEN price_cents ELSE 0 END) as expected', array_map(fn ($status) => $status->value, self::EXPECTED))
            ->groupBy('group_key')
            ->get()
            ->keyBy(fn ($row) => (string) $row->group_key);
    }

    /**
     * Valid payments of the period, keyed by the group.
     *
     * @return Collection<string, int>
     */
    private function received(CarbonImmutable $start, CarbonImmutable $end, string $group): Collection
    {
        $key = match ($group) {
            'day' => 'DATE(payments.paid_at)',
            'professional' => 'appointments.professional_id',
            'procedure' => 'appointments.procedure_id',
        };

        return Payment::query()
            ->valid()
            ->join('appointments', 'appointments.id', '=', 'payments.appointment_id')
            ->paidBetween($start, $end)
            ->selectRaw("{$key} as group_key, SUM(payments.amount_cents) as received")
            ->groupBy('group_key')
            ->get()
            ->mapWithKeys(fn ($row) => [self::normalizeKey($row->group_key) => (int) $row->received]);
    }

    /**
     * Name of each key: the date, the professional or the procedure.
     *
     * @param  Collection<int, string>  $keys
     * @return Collection<string, string>
     */
    private function labels(string $group, Collection $keys): Collection
    {
        $keys = $keys->map(fn ($key) => self::normalizeKey($key))->unique()->values();

        $names = match ($group) {
            'professional' => User::query()->whereIn('id', $keys->filter())->pluck('name', 'id'),
            'procedure' => Procedure::query()->whereIn('id', $keys)->pluck('name', 'id'),
            default => collect(),
        };

        return $keys->mapWithKeys(fn (string $key) => [$key => match ($group) {
            'day' => CarbonImmutable::parse($key)->format('d/m/Y'),
            'professional' => $names[$key] ?? 'Sem profissional',
            'procedure' => $names[$key] ?? 'Procedimento removido',
        }]);
    }

    /**
     * Money received by payment method in the period.
     *
     * @return list<array<string, mixed>>
     */
    private function byMethod(CarbonImmutable $start, CarbonImmutable $end): array
    {
        return Payment::query()
            ->valid()
            ->paidBetween($start, $end)
            ->selectRaw('method, SUM(amount_cents) as received')
            ->groupBy('method')
            ->orderByDesc('received')
            ->get()
            ->map(fn ($row) => [
                'method' => $row->method->value,
                'methodLabel' => $row->method->label(),
                'receivedCents' => (int) $row->received,
            ])
            ->all();
    }

    /** Dates may come back with a time from some drivers; ids as strings. */
    private static function normalizeKey(mixed $key): string
    {
        return $key === null ? '' : substr((string) $key, 0, 10);
    }
}
