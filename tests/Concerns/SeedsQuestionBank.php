<?php

namespace Tests\Concerns;

use App\Enums\Subject;
use App\Models\Option;
use App\Models\Question;

trait SeedsQuestionBank
{
    /**
     * Build a question bank large enough for at least one full attempt.
     *
     * Bahagian A questions get four options scored 3/2/1/0; Bahagian B questions
     * get four options with the first marked correct.
     */
    protected function seedQuestionBank(int $sectionA = 40, int $bahasaInggeris = 15, int $lain = 75): void
    {
        for ($i = 0; $i < $sectionA; $i++) {
            $this->makeSectionAQuestion();
        }

        $subjects = Subject::selainBahasaInggeris();

        for ($i = 0; $i < $lain; $i++) {
            $this->makeSectionBQuestion($subjects[$i % count($subjects)]);
        }

        for ($i = 0; $i < $bahasaInggeris; $i++) {
            $this->makeSectionBQuestion(Subject::BahasaInggeris);
        }
    }

    /**
     * @param  array<int, int>  $scores
     */
    protected function makeSectionAQuestion(array $scores = [3, 2, 1, 0], bool $isActive = true): Question
    {
        $question = Question::factory()->sectionA()->create(['is_active' => $isActive]);

        foreach ($scores as $score) {
            Option::factory()->for($question)->withScore($score)->create();
        }

        return $question->load('options');
    }

    protected function makeSectionBQuestion(Subject $subject, bool $isActive = true, int $optionCount = 4): Question
    {
        $question = Question::factory()->sectionB($subject)->create(['is_active' => $isActive]);

        for ($i = 0; $i < $optionCount; $i++) {
            Option::factory()->for($question)->create(['is_correct' => $i === 0]);
        }

        return $question->load('options');
    }
}
