<?php

use App\Domains\Agenda\Models\Appointment;
use App\Domains\Agenda\Models\ScheduleBlock;
use App\Domains\Agenda\Queries\AvailableSlots;
use App\Domains\Agenda\Support\DaySchedule;
use App\Domains\Identity\Models\User;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-09 07:00'));
    tenancy()->initialize($this->createClinic('sorriso'));

    $this->ana = User::factory()->create();
    $this->tuesday = CarbonImmutable::parse('2026-11-10');
});

it('builds the slots between opening and closing', function () {
    $slots = DaySchedule::load($this->tuesday)->slotStarts();

    expect($slots)->toHaveCount(20)
        ->and($slots[0]->format('H:i'))->toBe('08:00')
        ->and(end($slots)->format('H:i'))->toBe('17:30');
});

it('has no slots on a closed day', function () {
    expect(DaySchedule::load(CarbonImmutable::parse('2026-11-15'))->slotStarts())->toBe([]);
});

it('treats touching appointments as free', function () {
    Appointment::factory()->at('2026-11-10 09:00')->create(['professional_id' => $this->ana->id]);

    $schedule = DaySchedule::load($this->tuesday);

    expect($schedule->problems($this->tuesday->setTime(10, 0), 30, $this->ana->id, null))->toBe([])
        ->and($schedule->problems($this->tuesday->setTime(8, 30), 30, $this->ana->id, null))->toBe([])
        ->and($schedule->problems($this->tuesday->setTime(8, 30), 60, $this->ana->id, null))->toHaveKey('professional_id');
});

it('applies a block only to its professional', function () {
    $felipe = User::factory()->create();
    ScheduleBlock::create(['professional_id' => $this->ana->id, 'starts_at' => '2026-11-10 13:00', 'ends_at' => '2026-11-10 18:00', 'reason' => 'Curso']);

    $schedule = DaySchedule::load($this->tuesday);

    expect($schedule->problems($this->tuesday->setTime(14, 0), 30, $this->ana->id, null))->toBe(['professional_id' => 'O profissional não atende neste horário: Curso.'])
        ->and($schedule->problems($this->tuesday->setTime(14, 0), 30, $felipe->id, null))->toBe([])
        ->and($schedule->problems($this->tuesday->setTime(9, 0), 30, $this->ana->id, null))->toBe([]);
});

it('offers only times where the whole procedure fits', function () {
    $slots = collect((new AvailableSlots)->for($this->tuesday, 90, $this->ana->id))->pluck('time');

    expect($slots)->toContain('08:00', '10:30', '13:00', '16:30')
        ->not->toContain('11:00', '11:30', '17:00', '17:30');
});

it('counts the free slots of the professionals, without lunch', function () {
    Appointment::factory()->at('2026-11-10 09:00')->create(['professional_id' => $this->ana->id]);

    expect((new AvailableSlots)->countFree($this->tuesday, [$this->ana->id]))->toBe(16);
});
