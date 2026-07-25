<?php

namespace App\Services;

use App\Enums\Section;
use App\Enums\Subject;
use App\Exceptions\InsufficientQuestionBankException;
use App\Models\Question;
use Illuminate\Contracts\Database\Query\Builder as BuilderContract;

final class QuestionSelector
{
    public const int SECTION_A_COUNT = 30;

    public const int SECTION_B_OTHER_COUNT = 60;

    public const int BAHASA_INGGERIS_COUNT = 10;

    public const int SECTION_B_COUNT = self::SECTION_B_OTHER_COUNT + self::BAHASA_INGGERIS_COUNT;

    public const int TOTAL = self::SECTION_A_COUNT + self::SECTION_B_COUNT;

    /**
     * Pick the fixed question set for one attempt, already in final display order.
     *
     * Positions 1-30 are Bahagian A. Positions 31-100 are Bahagian B, with the
     * 60 non-English and 10 Bahasa Inggeris questions shuffled together so the
     * subject cannot be inferred from the position.
     *
     * @return array<int, int> question ids; array index 0 => position 1
     *
     * @throws InsufficientQuestionBankException
     */
    public function select(): array
    {
        $bahagianA = $this->pick(Section::A, null, self::SECTION_A_COUNT);

        $bahagianB = [
            ...$this->pick(Section::B, Subject::selainBahasaInggeris(), self::SECTION_B_OTHER_COUNT),
            ...$this->pick(Section::B, [Subject::BahasaInggeris], self::BAHASA_INGGERIS_COUNT),
        ];

        shuffle($bahagianB);

        return [...$bahagianA, ...$bahagianB];
    }

    /**
     * @param  array<int, Subject>|null  $subjects  null applies no subject filter
     * @return array<int, int>
     *
     * @throws InsufficientQuestionBankException
     */
    private function pick(Section $section, ?array $subjects, int $count): array
    {
        /** @var array<int, mixed> $ids */
        $ids = Question::query()
            ->active()
            ->where('section', $section)
            ->when(
                $subjects !== null,
                fn (BuilderContract $query): BuilderContract => $query->whereIn(
                    'subject',
                    array_map(fn (Subject $subject): string => $subject->value, $subjects ?? []),
                ),
            )
            ->inRandomOrder()
            ->limit($count)
            ->pluck('id')
            ->all();

        if (count($ids) < $count) {
            throw InsufficientQuestionBankException::for(
                $section,
                $subjects === null ? null : ($subjects[0] ?? null),
                $count,
                count($ids),
            );
        }

        return array_map(fn (mixed $id): int => (int) $id, $ids);
    }
}
