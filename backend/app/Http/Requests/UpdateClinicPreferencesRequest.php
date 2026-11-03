<?php

namespace App\Http\Requests;

use App\Domains\Tenancy\DTOs\ClinicPreferencesData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateClinicPreferencesRequest extends FormRequest
{
    /** Only admins may change the preferences. */
    public function authorize(): bool
    {
        return Gate::allows('update', tenant());
    }

    /**
     * Validates the preferences.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'auto_confirmation' => ['required', 'boolean'],
            'waiting_list' => ['required', 'boolean'],
            'reminder_24h' => ['required', 'boolean'],
        ];
    }

    /** Converts the request into the preferences DTO. */
    public function toData(): ClinicPreferencesData
    {
        return new ClinicPreferencesData(
            autoConfirmation: $this->boolean('auto_confirmation'),
            waitingList: $this->boolean('waiting_list'),
            reminder24h: $this->boolean('reminder_24h'),
        );
    }
}
