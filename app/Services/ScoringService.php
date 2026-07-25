<?php

namespace App\Services;

use App\Enums\Section;
use App\Enums\Subject;
use App\Models\Attempt;
use App\Models\Option;

final class ScoringService
{
    public const float SECTION_A_WEIGHT = 20.0;

    public const float SECTION_B_WEIGHT = 70.0;

    /**
     * Bahagian A is scored proportionally against the maximum obtainable score of
     * the questions actually drawn; Bahagian B against the number of B questions
     * in the attempt. Unanswered questions score nothing but still count towards
     * the denominator.
     *
     * @return array{score_a: float, score_b: float, score_total: float}
     */
    public function calculate(Attempt $attempt): array
    {
        $attempt->loadMissing(['attemptQuestions.question.options', 'attemptQuestions.selectedOption']);

        $earnedA = 0;
        $maxA = 0;
        $correctB = 0;
        $totalB = 0;

        foreach ($attempt->attemptQuestions as $attemptQuestion) {
            $selectedOption = $attemptQuestion->selectedOption;

            if ($attemptQuestion->question->section === Section::A) {
                $maxA += (int) $attemptQuestion->question->options->max('score');
                $earnedA += $selectedOption instanceof Option ? (int) $selectedOption->score : 0;

                continue;
            }

            $totalB++;

            if ($selectedOption instanceof Option && $selectedOption->is_correct) {
                $correctB++;
            }
        }

        $scoreA = $maxA > 0 ? round(($earnedA / $maxA) * self::SECTION_A_WEIGHT, 2) : 0.0;
        $scoreB = $totalB > 0 ? round(($correctB / $totalB) * self::SECTION_B_WEIGHT, 2) : 0.0;

        // Total is the sum of the already-rounded parts so the displayed total
        // always equals the displayed A plus the displayed B.
        return [
            'score_a' => $scoreA,
            'score_b' => $scoreB,
            'score_total' => round($scoreA + $scoreB, 2),
        ];
    }

    /**
     * Per-subject tally for Bahagian B only. Subjects absent from the attempt are omitted.
     *
     * @return array<string, array{subject: Subject, correct: int, total: int, percent: float}>
     */
    public function sectionBBreakdown(Attempt $attempt): array
    {
        $attempt->loadMissing(['attemptQuestions.question', 'attemptQuestions.selectedOption']);

        /** @var array<string, int> $correct */
        $correct = [];
        /** @var array<string, int> $total */
        $total = [];

        foreach ($attempt->attemptQuestions as $attemptQuestion) {
            $question = $attemptQuestion->question;

            if ($question->section !== Section::B || ! $question->subject instanceof Subject) {
                continue;
            }

            $key = $question->subject->value;
            $total[$key] = ($total[$key] ?? 0) + 1;

            $selectedOption = $attemptQuestion->selectedOption;

            if ($selectedOption instanceof Option && $selectedOption->is_correct) {
                $correct[$key] = ($correct[$key] ?? 0) + 1;
            }
        }

        $breakdown = [];

        foreach (Subject::cases() as $subject) {
            $subjectTotal = $total[$subject->value] ?? 0;

            if ($subjectTotal === 0) {
                continue;
            }

            $subjectCorrect = $correct[$subject->value] ?? 0;

            $breakdown[$subject->value] = [
                'subject' => $subject,
                'correct' => $subjectCorrect,
                'total' => $subjectTotal,
                'percent' => round(($subjectCorrect / $subjectTotal) * 100, 1),
            ];
        }

        return $breakdown;
    }
}
