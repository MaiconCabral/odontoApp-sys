<?php

namespace App\Domains\Clinical\DTOs;

readonly class ClinicalNoteData
{
    /** Text of a note, the appointment it belongs to and the note it corrects. */
    public function __construct(
        public string $body,
        public ?int $appointmentId,
        public ?int $amendsId,
    ) {}
}
