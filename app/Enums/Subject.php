<?php

namespace App\Enums;

enum Subject: string
{
    case Iq = 'iq';
    case PengetahuanAm = 'pengetahuan_am';
    case Matematik = 'matematik';
    case Sains = 'sains';
    case BahasaInggeris = 'bahasa_inggeris';

    public function label(): string
    {
        return match ($this) {
            self::Iq => 'IQ',
            self::PengetahuanAm => 'Pengetahuan Am',
            self::Matematik => 'Matematik',
            self::Sains => 'Sains',
            self::BahasaInggeris => 'Bahasa Inggeris',
        };
    }

    /**
     * Every subject except Bahasa Inggeris, which is drawn under its own quota.
     *
     * @return array<int, self>
     */
    public static function selainBahasaInggeris(): array
    {
        $subjects = [];

        foreach (self::cases() as $subject) {
            if ($subject !== self::BahasaInggeris) {
                $subjects[] = $subject;
            }
        }

        return $subjects;
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
