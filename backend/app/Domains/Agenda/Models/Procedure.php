<?php

namespace App\Domains\Agenda\Models;

use Database\Factories\ProcedureFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Procedure extends Model
{
    /** @use HasFactory<ProcedureFactory> */
    use HasFactory;

    /** How long the booking options stay cached; any change to a procedure clears them sooner. */
    private const OPTIONS_TTL = 3600;

    /** Clears the cached booking options whenever a procedure changes. */
    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::optionsCacheKey()));
        static::deleted(fn () => Cache::forget(self::optionsCacheKey()));
    }

    /** Cache key of the booking options, per clinic because the cache store is shared. */
    public static function optionsCacheKey(): string
    {
        return 'tenant:'.tenant()?->getTenantKey().':procedure-options';
    }

    protected $fillable = [
        'name',
        'duration_minutes',
        'price_cents',
        'active',
    ];

    /**
     * Casts the length, price and status.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'price_cents' => 'integer',
            'active' => 'boolean',
        ];
    }

    /** Factory used by tests and seeds. */
    protected static function newFactory(): ProcedureFactory
    {
        return ProcedureFactory::new();
    }

    /** Appointments booked with this procedure. */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /** Procedures offered for new bookings. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /** Name shown to users. */
    public function label(): string
    {
        return $this->name;
    }

    /** Length of the procedure, always a multiple of the agenda slot. */
    public function minutes(): int
    {
        return $this->duration_minutes;
    }

    /**
     * Active procedures in the format the booking forms use, cached per clinic.
     *
     * @return list<array{id: string, label: string, duration: int}>
     */
    public static function options(): array
    {
        return Cache::remember(self::optionsCacheKey(), self::OPTIONS_TTL, fn () => self::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'duration_minutes'])
            ->map(fn (self $procedure) => [
                'id' => (string) $procedure->id,
                'label' => $procedure->name,
                'duration' => $procedure->duration_minutes,
            ])
            ->all());
    }
}
