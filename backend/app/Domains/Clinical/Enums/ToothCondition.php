<?php

namespace App\Domains\Clinical\Enums;

enum ToothCondition: string
{
    case Healthy = 'healthy';
    case Caries = 'caries';
    case Restoration = 'restoration';
    case RootCanal = 'root_canal';
    case Crown = 'crown';
    case Implant = 'implant';
    case ExtractionNeeded = 'extraction_needed';
    case Missing = 'missing';

    /** Name of the condition shown to the professional. */
    public function label(): string
    {
        return match ($this) {
            self::Healthy => 'Saudável',
            self::Caries => 'Cárie',
            self::Restoration => 'Restauração',
            self::RootCanal => 'Endodontia',
            self::Crown => 'Coroa',
            self::Implant => 'Implante',
            self::ExtractionNeeded => 'Extração indicada',
            self::Missing => 'Ausente',
        };
    }

    /** Conditions of the whole tooth, recorded without a surface. */
    public function wholeTooth(): bool
    {
        return in_array($this, [self::RootCanal, self::Crown, self::Implant, self::ExtractionNeeded, self::Missing], true);
    }
}
