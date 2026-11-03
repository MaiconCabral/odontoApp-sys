<?php

namespace App\Domains\Clinical\Enums;

enum ToothSurface: string
{
    case Mesial = 'M';
    case Distal = 'D';
    case Occlusal = 'O';
    case Vestibular = 'V';
    case Lingual = 'L';

    /** Name of the surface shown to the professional. */
    public function label(): string
    {
        return match ($this) {
            self::Mesial => 'mesial',
            self::Distal => 'distal',
            self::Occlusal => 'oclusal',
            self::Vestibular => 'vestibular',
            self::Lingual => 'lingual',
        };
    }
}
