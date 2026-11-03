<?php

namespace App\Domains\Tenancy\Support;

use App\Domains\Identity\Models\User;
use Illuminate\Validation\ValidationException;

// Applies the limits of the clinic's plan. Call it inside a transaction, so the lock holds.
class PlanLimits
{
    /** Refuses one more active professional when the plan is full. A null limit means no limit. */
    public function ensureCanAddProfessional(?User $current = null): void
    {
        $max = tenant()->plan?->max_professionals;

        if ($max === null) {
            return;
        }

        if ($this->professionalsUsed($current, lock: true) >= $max) {
            throw ValidationException::withMessages([
                'role' => "Seu plano permite até {$max} ".($max === 1 ? 'profissional' : 'profissionais').'. Fale com a gente para mudar de plano.',
            ]);
        }
    }

    /** Active users who attend patients, optionally leaving one out and locking the rows. */
    public function professionalsUsed(?User $except = null, bool $lock = false): int
    {
        $query = User::query()
            ->where('active', true)
            ->where('attends_patients', true)
            ->when($except?->exists, fn ($query) => $query->whereKeyNot($except->id));

        // Two admins can add professionals at the same moment; the lock serializes them here.
        return $lock ? $query->lockForUpdate()->count() : $query->count();
    }
}
