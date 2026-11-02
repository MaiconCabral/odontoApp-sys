<?php

namespace App\Http\Resources;

use App\Domains\Agenda\Models\Patient;
use App\Support\Initials;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Patient
 */
class PatientResource extends JsonResource
{
    public static $wrap = null;

    private const TONES = ['blue', 'amber', 'green', 'violet', 'pink'];

    /**
     * Patient record in the format of the patients page.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'initials' => Initials::of($this->name),
            'tone' => self::TONES[$this->id % count(self::TONES)],
            'cpf' => $this->cpf,
            'phone' => $this->phone,
            'email' => $this->email,
            'birthDate' => $this->birth_date?->toDateString(),
            'insurance' => $this->insurance,
            'isPrivate' => $this->insurance === null || $this->insurance === Patient::PRIVATE_INSURANCE,
            'notes' => $this->notes,
            'lastAppointment' => $this->lastAppointmentLabel(),
        ];
    }

    /** Date of the last visit, when the list query loaded it. */
    private function lastAppointmentLabel(): string
    {
        $last = $this->last_appointment_at ?? null;

        if (! $last) {
            return 'Sem consultas';
        }

        $date = Carbon::parse($last);

        return $date->isToday() ? 'Hoje, '.$date->format('H:i') : $date->format('d/m/Y');
    }
}
