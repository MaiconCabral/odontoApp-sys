<?php

namespace App\Domains\Agenda\Queries;

use App\Domains\Agenda\Enums\AppointmentStatus;
use App\Domains\Agenda\Models\Appointment;
use App\Domains\Agenda\Models\Patient;
use App\Domains\Agenda\Support\DaySchedule;
use App\Domains\Identity\Models\User;
use App\Support\Initials;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

// Numbers of the panel home page. The number of queries does not grow with the number of appointments.
class OverviewQuery
{
    // Appointments that happen or happened: not requests, cancellations, rejections or no-shows.
    private const HELD = [AppointmentStatus::Scheduled, AppointmentStatus::Confirmed, AppointmentStatus::Completed];

    private const WEEKDAYS = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'];

    private const UPCOMING_LIMIT = 10;

    /**
     * Everything the overview page shows.
     *
     * @return array<string, mixed>
     */
    public function for(User $viewer): array
    {
        $now = CarbonImmutable::now();
        $today = $now->startOfDay();

        return [
            'greeting' => $this->greeting($viewer, $now),
            'metrics' => $this->metrics($today),
            'weeklyAppointments' => $this->weekly($today),
            'occupancyPercentage' => $this->occupancy($today),
            'agendaDate' => $today->locale('pt_BR')->isoFormat('D [de] MMMM [de] YYYY'),
            'upcomingAppointments' => $this->upcoming($today, $now),
        ];
    }

    /** "Bom dia, Carlos", by the hour and the first name without the title. */
    private function greeting(User $viewer, CarbonImmutable $now): string
    {
        $period = match (true) {
            $now->hour < 12 => 'Bom dia',
            $now->hour < 18 => 'Boa tarde',
            default => 'Boa noite',
        };
        $name = strtok(preg_replace('/^dra?\.?\s+/iu', '', $viewer->name), ' ');

        return "{$period}, {$name}";
    }

    /**
     * The four cards: appointments and revenue of today, attendance and new patients of the month.
     *
     * @return list<array<string, string>>
     */
    private function metrics(CarbonImmutable $today): array
    {
        $yesterday = $today->subDay();
        $days = Appointment::query()
            ->whereIn('status', self::HELD)
            ->whereBetween('starts_at', [$yesterday, $today->endOfDay()])
            ->selectRaw('DATE(starts_at) as day, COUNT(*) as total, SUM(price_cents) as revenue')
            ->groupBy('day')
            ->get()
            ->keyBy('day');
        $todayRow = $days->get($today->toDateString());
        $yesterdayRow = $days->get($yesterday->toDateString());

        [$attendance, $previousAttendance] = $this->attendance($today);
        $newPatients = $this->newPatients($today);

        return [
            $this->metric('appointments', 'Consultas hoje', (string) (int) ($todayRow->total ?? 0), (int) ($todayRow->total ?? 0), (int) ($yesterdayRow->total ?? 0), 'vs. ontem', 'calendar', 'blue'),
            $this->metric('revenue', 'Faturamento estimado', self::money((int) ($todayRow->revenue ?? 0)), (int) ($todayRow->revenue ?? 0), (int) ($yesterdayRow->revenue ?? 0), 'vs. ontem', 'billing', 'green'),
            $this->attendanceMetric($attendance, $previousAttendance),
            $this->metric('new-patients', 'Novos pacientes', (string) $newPatients[0], $newPatients[0], $newPatients[1], 'este mês', 'patients', 'violet'),
        ];
    }

    /**
     * Attendance rate of this month and of the previous one, or null without finished appointments.
     *
     * @return array{0: float|null, 1: float|null}
     */
    private function attendance(CarbonImmutable $today): array
    {
        $rows = Appointment::query()
            ->whereIn('status', [AppointmentStatus::Completed, AppointmentStatus::NoShow])
            ->whereBetween('starts_at', [$today->subMonthNoOverflow()->startOfMonth(), $today->endOfMonth()])
            ->selectRaw("CASE WHEN starts_at >= ? THEN 'current' ELSE 'previous' END as month, status, COUNT(*) as total", [$today->startOfMonth()])
            ->groupBy('month', 'status')
            ->get();

        $rate = function (string $month) use ($rows): ?float {
            $completed = (int) $rows->where('month', $month)->where('status', AppointmentStatus::Completed->value)->sum('total');
            $missed = (int) $rows->where('month', $month)->where('status', AppointmentStatus::NoShow->value)->sum('total');

            return $completed + $missed > 0 ? $completed / ($completed + $missed) * 100 : null;
        };

        return [$rate('current'), $rate('previous')];
    }

    /**
     * Patients registered this month and in the previous one.
     *
     * @return array{0: int, 1: int}
     */
    private function newPatients(CarbonImmutable $today): array
    {
        $row = Patient::query()
            ->whereBetween('created_at', [$today->subMonthNoOverflow()->startOfMonth(), $today->endOfMonth()])
            ->selectRaw('SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as current, SUM(CASE WHEN created_at < ? THEN 1 ELSE 0 END) as previous', [$today->startOfMonth(), $today->startOfMonth()])
            ->first();

        return [(int) ($row->current ?? 0), (int) ($row->previous ?? 0)];
    }

    /**
     * Attendance card, compared in percentage points.
     *
     * @return array<string, string>
     */
    private function attendanceMetric(?float $rate, ?float $previous): array
    {
        $difference = $rate !== null && $previous !== null ? $rate - $previous : null;

        return [
            'id' => 'attendance',
            'label' => 'Taxa de comparecimento',
            'value' => $rate === null ? '—' : self::percent($rate),
            'change' => $difference === null ? '—' : self::number(abs($difference)).' p.p.',
            'trend' => self::trend($difference ?? 0.0),
            'period' => 'este mês',
            'icon' => 'attendance',
            'tone' => 'amber',
        ];
    }

    /**
     * One card, comparing the value with the previous period.
     *
     * @return array<string, string>
     */
    private function metric(string $id, string $label, string $value, int $current, int $previous, string $period, string $icon, string $tone): array
    {
        $change = $previous > 0 ? ($current - $previous) / $previous * 100 : null;

        return [
            'id' => $id,
            'label' => $label,
            'value' => $value,
            'change' => $change === null ? '—' : self::percent(abs($change)),
            'trend' => self::trend($change ?? 0.0),
            'period' => $period,
            'icon' => $icon,
            'tone' => $tone,
        ];
    }

    /**
     * Appointments per day of this week, compared with the previous week.
     *
     * @return array<string, mixed>
     */
    private function weekly(CarbonImmutable $today): array
    {
        // pt_BR weeks start on Sunday; the chart starts on Monday.
        $monday = $today->startOfWeek(CarbonInterface::MONDAY);
        $counts = Appointment::query()
            ->whereIn('status', self::HELD)
            ->whereBetween('starts_at', [$monday->subWeek(), $monday->addWeek()->subSecond()])
            ->selectRaw('DATE(starts_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $bars = collect(range(0, 6))->map(fn (int $offset) => [
            'day' => self::WEEKDAYS[$offset],
            'value' => (int) ($counts[$monday->addDays($offset)->toDateString()] ?? 0),
            'highlighted' => $monday->addDays($offset)->isSameDay($today),
        ]);
        $total = $bars->sum('value');
        $previous = (int) collect(range(0, 6))->sum(fn (int $offset) => $counts[$monday->subWeek()->addDays($offset)->toDateString()] ?? 0);
        $change = $previous > 0 ? ($total - $previous) / $previous * 100 : null;

        // Four even steps that round up the busiest day, like the original chart.
        $step = max(5, (int) ceil($bars->max('value') / 4 / 5) * 5);

        return [
            'total' => $total,
            'change' => $change === null ? '—' : self::percent(abs($change)),
            'trend' => self::trend($change ?? 0.0),
            'comparison' => 'vs. semana anterior',
            'range' => 'Esta semana',
            'scaleMax' => (int) round($step * 4 * 1.375),
            'axis' => [$step * 4, $step * 3, $step * 2, $step, 0],
            'bars' => $bars->all(),
        ];
    }

    /** Booked minutes of today over the minutes the active professionals work. */
    private function occupancy(CarbonImmutable $today): int
    {
        $professionalIds = User::query()->where('attends_patients', true)->where('active', true)->pluck('id');
        $schedule = DaySchedule::load($today);
        $capacity = 0;

        foreach ($schedule->slotStarts() as $start) {
            foreach ($professionalIds as $professionalId) {
                $capacity += $schedule->serves($start, $professionalId) ? DaySchedule::SLOT_MINUTES : 0;
            }
        }

        if ($capacity === 0) {
            return 0;
        }

        $booked = Appointment::query()
            ->whereIn('status', [...self::HELD, AppointmentStatus::NoShow])
            ->whereIn('professional_id', $professionalIds)
            ->whereBetween('starts_at', [$today, $today->endOfDay()])
            ->get(['starts_at', 'ends_at'])
            ->sum(fn (Appointment $appointment) => $appointment->minutes());

        return (int) min(100, round($booked / $capacity * 100));
    }

    /** Today's appointments, ongoing and upcoming first. */
    private function upcoming(CarbonImmutable $today, CarbonImmutable $now): Collection
    {
        return Appointment::query()
            ->with(['patient:id,name', 'procedure:id,name', 'professional:id,name'])
            ->whereIn('status', [...self::HELD, AppointmentStatus::NoShow])
            ->whereBetween('starts_at', [$today, $today->endOfDay()])
            ->orderBy('starts_at')
            ->get()
            // Finished ones go last: by the clock, or because the outcome is already recorded.
            ->sortBy(fn (Appointment $appointment) => $this->isFinished($appointment, $now) ? 1 : 0)
            ->take(self::UPCOMING_LIMIT)
            ->values()
            ->map(fn (Appointment $appointment) => [
                'id' => (string) $appointment->id,
                'time' => $appointment->starts_at->format('H:i'),
                'duration' => $appointment->minutes().' min',
                'patientInitials' => Initials::of($appointment->patient->name),
                'patientName' => $appointment->patient->name,
                'patientTone' => $appointment->patient->tone(),
                'procedure' => $appointment->procedure->name,
                'professional' => $appointment->professional?->name ?? 'A definir',
                ...$this->visualStatus($appointment, $now),
            ]);
    }

    /**
     * Status of the row: by the clock, except for what was already recorded.
     *
     * @return array{status: string, statusLabel: string}
     */
    private function visualStatus(Appointment $appointment, CarbonImmutable $now): array
    {
        return match (true) {
            $appointment->status === AppointmentStatus::Completed => ['status' => 'completed', 'statusLabel' => 'Concluído'],
            $appointment->status === AppointmentStatus::NoShow => ['status' => 'completed', 'statusLabel' => 'Faltou'],
            $appointment->starts_at > $now => ['status' => 'waiting', 'statusLabel' => $appointment->status === AppointmentStatus::Confirmed ? 'Confirmado' : 'Aguardando'],
            $appointment->ends_at > $now => ['status' => 'in_progress', 'statusLabel' => 'Em atendimento'],
            default => ['status' => 'completed', 'statusLabel' => 'Encerrada'],
        };
    }

    /** Tells whether the appointment is over, by its recorded outcome or by the clock. */
    private function isFinished(Appointment $appointment, CarbonImmutable $now): bool
    {
        return in_array($appointment->status, [AppointmentStatus::Completed, AppointmentStatus::NoShow], true) || $appointment->ends_at <= $now;
    }

    /** Direction of a change. */
    private static function trend(float $change): string
    {
        return match (true) {
            $change > 0.05 => 'up',
            $change < -0.05 => 'down',
            default => 'flat',
        };
    }

    /** "12,5%". */
    private static function percent(float $value): string
    {
        return self::number($value).'%';
    }

    /** One decimal, Brazilian style. */
    private static function number(float $value): string
    {
        return number_format($value, 1, ',', '.');
    }

    /** "R$ 4.280", rounded to reais. */
    private static function money(int $cents): string
    {
        return 'R$ '.number_format($cents / 100, 0, ',', '.');
    }
}
