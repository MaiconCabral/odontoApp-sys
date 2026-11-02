<?php

namespace App\Domains\Agenda\Models;

use Database\Factories\PatientFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Patient extends Model
{
    /** @use HasFactory<PatientFactory> */
    use HasFactory;

    public const PRIVATE_INSURANCE = 'Particular';

    protected $fillable = [
        'name',
        'phone',
        'email',
        'cpf',
        'insurance',
        'birth_date',
        'notes',
    ];

    /**
     * Casts the birth date.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
        ];
    }

    /** Keeps the phone digits in sync, so search and matching ignore the mask. */
    protected static function booted(): void
    {
        static::saving(function (Patient $patient) {
            $patient->phone_digits = self::digits($patient->phone) ?: null;
        });
    }

    /** Factory used by tests and seeds. */
    protected static function newFactory(): PatientFactory
    {
        return PatientFactory::new();
    }

    /** Appointments of the patient. */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /** Stores the e-mail trimmed and in lowercase. */
    protected function email(): Attribute
    {
        return Attribute::set(fn (?string $value) => $value === null || trim($value) === '' ? null : Str::lower(trim($value)));
    }

    /** Stores the CPF always as 000.000.000-00. */
    protected function cpf(): Attribute
    {
        return Attribute::set(function (?string $value) {
            $digits = self::digits($value);

            return strlen($digits) === 11
                ? preg_replace('/^(\d{3})(\d{3})(\d{3})(\d{2})$/', '$1.$2.$3-$4', $digits)
                : null;
        });
    }

    /** Patients whose name, CPF or phone match the search. */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        $digits = self::digits($term);

        return $query->where(function (Builder $query) use ($term, $digits) {
            $query->where('name', 'like', "%{$term}%");

            if ($digits !== '') {
                $query->orWhere('phone_digits', 'like', "%{$digits}%")
                    ->orWhereRaw("replace(replace(cpf, '.', ''), '-', '') like ?", ["%{$digits}%"]);
            }
        });
    }

    /** Only the digits of a value. */
    public static function digits(?string $value): string
    {
        return preg_replace('/\D/', '', (string) $value);
    }
}
