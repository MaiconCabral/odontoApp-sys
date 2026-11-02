<?php

namespace App\Http\Controllers\Api;

use App\Domains\Identity\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PasswordController extends Controller
{
    /**
     * Sends the reset link when the e-mail has an account.
     * The outcome is ignored on purpose: the response never reveals whether the e-mail has an account.
     */
    public function forgot(ForgotPasswordRequest $request): Response
    {
        Password::sendResetLink(['email' => $request->email()]);

        return response()->noContent();
    }

    /** Saves the new password from a valid link. */
    public function reset(ResetPasswordRequest $request): Response
    {
        $status = Password::reset(
            [...$request->validated(), 'email' => $request->email()],
            function (User $user, string $password) {
                $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['token' => 'Este link é inválido ou expirou.']);
        }

        return response()->noContent();
    }
}
