<?php

namespace App\Domains\Agenda\DTOs;

readonly class PatientData
{
    /** Data of a patient record. */
    public function __construct(
        public string $name,
        public string $phone,
        public ?string $email = null,
        public ?string $cpf = null,
        public ?string $insurance = null,
        public ?string $birthDate = null,
        public ?string $notes = null,
    ) {}

    /**
     * Columns of the patients table.
     *
     * @return array<string, string|null>
     */
    public function toAttributes(): array
    {
        return [
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'cpf' => $this->cpf,
            'insurance' => $this->insurance,
            'birth_date' => $this->birthDate,
            'notes' => $this->notes,
        ];
    }
}
