<?php

namespace App\Domains\Clinical\Models;

use App\Domains\Agenda\Models\Patient;
use App\Domains\Clinical\Enums\ToothCondition;
use App\Domains\Clinical\Enums\ToothSurface;
use App\Domains\Identity\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// One change in the odontogram. Rows are history; the latest per tooth and surface is the current state.
class ToothRecord extends Model
{
    protected $fillable = [
        'patient_id',
        'author_id',
        'tooth',
        'surface',
        'condition',
        'notes',
    ];

    /**
     * Casts the surface and the condition.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tooth' => 'integer',
            'surface' => ToothSurface::class,
            'condition' => ToothCondition::class,
        ];
    }

    /** Patient the record belongs to. */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /** Who recorded the change. */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** Only the latest record of each tooth and surface. */
    public function scopeCurrent(Builder $query): Builder
    {
        return $query->whereIn('id', fn ($latest) => $latest
            ->from('tooth_records as latest')
            ->selectRaw('MAX(latest.id)')
            ->whereColumn('latest.patient_id', 'tooth_records.patient_id')
            ->groupBy('latest.tooth', 'latest.surface'));
    }
}
