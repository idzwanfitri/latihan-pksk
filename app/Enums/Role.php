<?php

namespace App\Enums;

enum Role: string
{
    case Pelajar = 'pelajar';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Pelajar => 'Pelajar',
            self::Admin => 'Pentadbir',
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
