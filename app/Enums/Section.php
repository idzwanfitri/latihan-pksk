<?php

namespace App\Enums;

enum Section: string
{
    case A = 'A';
    case B = 'B';

    public function label(): string
    {
        return match ($this) {
            self::A => 'Bahagian A',
            self::B => 'Bahagian B',
        };
    }

    public function fullLabel(): string
    {
        return match ($this) {
            self::A => 'Bahagian A — Kecerdasan Insaniah',
            self::B => 'Bahagian B — Kecerdasan Intelek',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
