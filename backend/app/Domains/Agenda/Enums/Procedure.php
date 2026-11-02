<?php

namespace App\Domains\Agenda\Enums;

enum Procedure: string
{
    case Evaluation = 'evaluation';
    case OrthodonticEvaluation = 'orthodontic_evaluation';
    case Cleaning = 'cleaning';
    case AestheticRestoration = 'aesthetic_restoration';
    case FollowUp = 'follow_up';

    /** Name of the procedure shown to users. */
    public function label(): string
    {
        return match ($this) {
            self::Evaluation => 'Avaliação',
            self::OrthodonticEvaluation => 'Avaliação ortodôntica',
            self::Cleaning => 'Limpeza e prevenção',
            self::AestheticRestoration => 'Restauração estética',
            self::FollowUp => 'Consulta de retorno',
        };
    }

    /** Length of the procedure, always a multiple of the agenda slot. */
    public function minutes(): int
    {
        return match ($this) {
            self::Evaluation, self::FollowUp => 30,
            self::OrthodonticEvaluation, self::Cleaning => 60,
            self::AestheticRestoration => 90,
        };
    }

    /**
     * Every procedure in the format the frontend uses.
     *
     * @return list<array{key: string, label: string, duration: int}>
     */
    public static function options(): array
    {
        return array_map(fn (self $procedure) => [
            'key' => $procedure->value,
            'label' => $procedure->label(),
            'duration' => $procedure->minutes(),
        ], self::cases());
    }
}
