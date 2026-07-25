<?php

namespace App\Services;

use App\Enums\Section;
use App\Enums\Subject;
use App\Models\Attempt;
use App\Models\Question;

/**
 * Read-only health checks over the question bank.
 *
 * Shared by the `soalan:import` command and the admin dashboard so both report
 * the same numbers.
 */
final class QuestionBankReport
{
    /**
     * Active-pool counts against the quotas a single attempt has to satisfy.
     *
     * @return array<int, array{label: string, available: int, required: int, ok: bool}>
     */
    public function health(): array
    {
        return [
            [
                'label' => 'Bahagian A',
                'available' => $this->activeSectionA(),
                'required' => QuestionSelector::SECTION_A_COUNT,
                'ok' => $this->activeSectionA() >= QuestionSelector::SECTION_A_COUNT,
            ],
            [
                'label' => 'Bahagian B (bukan BI)',
                'available' => $this->activeOtherSubjects(),
                'required' => QuestionSelector::SECTION_B_OTHER_COUNT,
                'ok' => $this->activeOtherSubjects() >= QuestionSelector::SECTION_B_OTHER_COUNT,
            ],
            [
                'label' => 'Bahagian B (Bahasa Inggeris)',
                'available' => $this->activeBahasaInggeris(),
                'required' => QuestionSelector::BAHASA_INGGERIS_COUNT,
                'ok' => $this->activeBahasaInggeris() >= QuestionSelector::BAHASA_INGGERIS_COUNT,
            ],
        ];
    }

    public function isReady(): bool
    {
        foreach ($this->health() as $row) {
            if (! $row['ok']) {
                return false;
            }
        }

        return true;
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
    public function subjectSpread(): array
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

    /**
     * @return array{total: int, active: int, inactive: int, section_a: int, section_b: int, attempts: int}
     */
    public function counts(): array
    {
        $total = Question::query()->count();
        $active = Question::query()->active()->count();

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $total - $active,
            'section_a' => Question::query()->where('section', Section::A)->count(),
            'section_b' => Question::query()->where('section', Section::B)->count(),
            'attempts' => Attempt::query()->count(),
        ];
    }

    private function activeSectionA(): int
    {
        return Question::query()->active()->where('section', Section::A)->count();
    }

    private function activeBahasaInggeris(): int
    {
        return Question::query()->active()
            ->where('section', Section::B)
            ->where('subject', Subject::BahasaInggeris->value)
            ->count();
    }

    private function activeOtherSubjects(): int
    {
        return Question::query()->active()
            ->where('section', Section::B)
            ->where('subject', '!=', Subject::BahasaInggeris->value)
            ->count();
    }
}
