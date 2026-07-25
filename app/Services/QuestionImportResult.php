<?php

namespace App\Services;

use App\Enums\Section;
use App\Enums\Subject;
use App\Models\Question;

final class QuestionImportResult
{
    /** @var array<int, string> */
    public array $errors = [];

    /** @var array<int, string> */
    public array $skipped = [];

    public int $importedA = 0;

    public int $importedB = 0;

    public function addError(string $where, string $message): void
    {
        $this->errors[] = $where.': '.$message;
    }

    public function addSkipped(string $where, string $message): void
    {
        $this->skipped[] = $where.': '.$message;
    }

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    public function importedTotal(): int
    {
        return $this->importedA + $this->importedB;
    }

    /**
     * Active-pool counts against the quotas a single attempt has to satisfy.
     *
     * @return array<int, array{label: string, available: int, required: int, ok: bool}>
     */
    public static function bankHealth(): array
    {
        $sectionA = Question::query()->active()->where('section', Section::A)->count();

        $bahasaInggeris = Question::query()->active()
            ->where('section', Section::B)
            ->where('subject', Subject::BahasaInggeris->value)
            ->count();

        $lain = Question::query()->active()
            ->where('section', Section::B)
            ->where('subject', '!=', Subject::BahasaInggeris->value)
            ->count();

        return [
            [
                'label' => 'Bahagian A',
                'available' => $sectionA,
                'required' => QuestionSelector::SECTION_A_COUNT,
                'ok' => $sectionA >= QuestionSelector::SECTION_A_COUNT,
            ],
            [
                'label' => 'Bahagian B (bukan BI)',
                'available' => $lain,
                'required' => QuestionSelector::SECTION_B_OTHER_COUNT,
                'ok' => $lain >= QuestionSelector::SECTION_B_OTHER_COUNT,
            ],
            [
                'label' => 'Bahagian B (Bahasa Inggeris)',
                'available' => $bahasaInggeris,
                'required' => QuestionSelector::BAHASA_INGGERIS_COUNT,
                'ok' => $bahasaInggeris >= QuestionSelector::BAHASA_INGGERIS_COUNT,
            ],
        ];
    }

    /**
     * Active section-B questions per subject.
     *
     * The exam draws the 60 non-English questions from a single pooled query, so
     * a lopsided bank produces lopsided papers. Surfacing the split lets an
     * administrator notice that before students do.
     *
     * @return array<int, array{subject: string, count: int, percent: float}>
     */
    public static function subjectSpread(): array
    {
        $total = Question::query()->active()->where('section', Section::B)->count();

        $rows = [];

        foreach (Subject::cases() as $subject) {
            $count = Question::query()->active()
                ->where('section', Section::B)
                ->where('subject', $subject->value)
                ->count();

            $rows[] = [
                'subject' => $subject->label(),
                'count' => $count,
                'percent' => $total > 0 ? round(($count / $total) * 100, 1) : 0.0,
            ];
        }

        return $rows;
    }
}
