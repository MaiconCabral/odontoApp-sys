<?php

namespace App\Domains\Clinical\Support;

// Tooth numbers in the FDI notation: quadrant then position.
class Teeth
{
    /**
     * Permanent teeth 11-18, 21-28, 31-38 and 41-48, plus deciduous 51-55 up to 81-85.
     *
     * @return list<int>
     */
    public static function all(): array
    {
        $teeth = [];

        foreach ([1, 2, 3, 4] as $quadrant) {
            foreach (range(1, 8) as $position) {
                $teeth[] = $quadrant * 10 + $position;
            }
        }

        foreach ([5, 6, 7, 8] as $quadrant) {
            foreach (range(1, 5) as $position) {
                $teeth[] = $quadrant * 10 + $position;
            }
        }

        return $teeth;
    }
}
