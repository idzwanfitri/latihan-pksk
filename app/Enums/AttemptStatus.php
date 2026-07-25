<?php

namespace App\Enums;

enum AttemptStatus: string
{
    case InProgress = 'in_progress';
    case Submitted = 'submitted';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::InProgress => 'Sedang Berlangsung',
            self::Submitted => 'Dihantar',
            self::Expired => 'Tamat Masa',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::InProgress => 'blue',
            self::Submitted => 'green',
            self::Expired => 'amber',
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
