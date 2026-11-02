<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AgendaRequest extends FormRequest
{
    /**
     * Validates the optional professional filter.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'professional_id' => ['sometimes', 'integer'],
        ];
    }

    /** Professional asked by the client, if any. */
    public function professionalId(): ?int
    {
        return $this->filled('professional_id') ? $this->integer('professional_id') : null;
    }
}
