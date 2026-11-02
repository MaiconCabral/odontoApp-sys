<?php

namespace App\Http\Requests;

use App\Domains\Identity\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends StoreUserRequest
{
    /** Only admins may edit team members. */
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('user'));
    }

    /** E-mail must not belong to another user of this clinic. */
    protected function uniqueEmail(): mixed
    {
        return Rule::unique(User::class, 'email')->ignore($this->route('user'));
    }
}
