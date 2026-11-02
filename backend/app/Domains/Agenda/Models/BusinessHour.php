<?php

namespace App\Domains\Agenda\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessHour extends Model
{
    public const WEEKDAY_LABELS = [1 => 'Segunda', 2 => 'Terça', 3 => 'Quarta', 4 => 'Quinta', 5 => 'Sexta', 6 => 'Sábado', 7 => 'Domingo'];

    protected $fillable = [
        'weekday',
        'closed',
        'opens_at',
        'closes_at',
        'break_starts_at',
        'break_ends_at',
    ];

    /**
     * Casts the closed flag.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'weekday' => 'integer',
            'closed' => 'boolean',
        ];
    }

    /** Time as HH:MM, whatever the database returns. */
    public static function hhmm(?string $time): ?string
    {
        return $time === null ? null : substr($time, 0, 5);
    }
}
