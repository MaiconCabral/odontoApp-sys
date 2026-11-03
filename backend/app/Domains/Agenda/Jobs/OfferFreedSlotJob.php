<?php

namespace App\Domains\Agenda\Jobs;

use App\Domains\Agenda\Actions\OfferFreedSlot;
use App\Domains\Agenda\Models\Appointment;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

// Offers a freed time to the waiting list in the queue, so cancelling never waits for e-mails.
// The queue bootstrapper reopens the clinic of the dispatch in the worker.
class OfferFreedSlotJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /** Keeps the appointment that held the time and the time it freed. */
    public function __construct(
        public readonly int $appointmentId,
        public readonly string $start,
        public readonly int $minutes,
        public readonly int $professionalId,
    ) {}

    /** Queues the offer once the change that freed the time is committed. */
    public static function afterCommit(Appointment $freed, \DateTimeInterface $start, int $minutes, ?int $professionalId): void
    {
        if (! $professionalId) {
            return;
        }

        DB::afterCommit(fn () => self::dispatch($freed->id, CarbonImmutable::instance($start)->toIso8601String(), $minutes, $professionalId));
    }

    /** Runs the offer. A deleted appointment ends it quietly. */
    public function handle(OfferFreedSlot $offer): void
    {
        $freed = Appointment::query()->find($this->appointmentId);

        if ($freed) {
            $offer->handle($freed, CarbonImmutable::parse($this->start), $this->minutes, $this->professionalId);
        }
    }
}
