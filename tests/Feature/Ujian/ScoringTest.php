<?php

namespace Tests\Feature\Ujian;

use App\Enums\Subject;
use App\Models\Attempt;
use App\Models\AttemptQuestion;
use App\Models\Option;
use App\Models\Question;
use App\Models\User;
use App\Services\ScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsQuestionBank;
use Tests\TestCase;

class ScoringTest extends TestCase
{
    use RefreshDatabase, SeedsQuestionBank;

    private int $position = 0;

    private function attempt(): Attempt
    {
        $this->position = 0;

        return Attempt::factory()->for(User::factory()->pelajar())->create();
    }

    /**
     * Attach a question to the attempt at the next position, optionally selecting
     * the option at $chooseIndex (null leaves it unanswered).
     */
    private function attach(Attempt $attempt, Question $question, ?int $chooseIndex = null): AttemptQuestion
    {
        $options = $question->options()->orderBy('id')->get();

        return $attempt->attemptQuestions()->create([
            'question_id' => $question->id,
            'position' => ++$this->position,
            'selected_option_id' => $chooseIndex === null ? null : $options[$chooseIndex]->id,
        ]);
    }

    public function test_score_a_is_proportional_to_the_selected_option_scores(): void
    {
        $attempt = $this->attempt();

        // 30 questions, options scored 3/2/1/0 => max 3 each => maxA = 90.
        // Choose the 3-scorer 15 times and the 1-scorer 15 times => earned 60.
        for ($i = 0; $i < 30; $i++) {
            $this->attach($attempt, $this->makeSectionAQuestion(), $i < 15 ? 0 : 2);
        }

        $scores = app(ScoringService::class)->calculate($attempt);

        // 60 / 90 * 20 = 13.333...
        $this->assertEqualsWithDelta(13.33, $scores['score_a'], 0.001);
    }

    public function test_score_b_equals_the_number_of_correct_answers(): void
    {
        $attempt = $this->attempt();

        // The bank helper marks option index 0 as the correct one.
        for ($i = 0; $i < 70; $i++) {
            $this->attach($attempt, $this->makeSectionBQuestion(Subject::Matematik), $i < 42 ? 0 : 1);
        }

        $scores = app(ScoringService::class)->calculate($attempt);

        // 42 / 70 * 70 = 42
        $this->assertEqualsWithDelta(42.00, $scores['score_b'], 0.001);
    }

    public function test_score_total_is_score_a_plus_score_b(): void
    {
        $attempt = $this->attempt();

        for ($i = 0; $i < 30; $i++) {
            $this->attach($attempt, $this->makeSectionAQuestion(), $i < 15 ? 0 : 2);
        }

        for ($i = 0; $i < 70; $i++) {
            $this->attach($attempt, $this->makeSectionBQuestion(Subject::Sains), $i < 42 ? 0 : 1);
        }

        $scores = app(ScoringService::class)->calculate($attempt);

        $this->assertEqualsWithDelta(13.33, $scores['score_a'], 0.001);
        $this->assertEqualsWithDelta(42.00, $scores['score_b'], 0.001);
        $this->assertEqualsWithDelta(55.33, $scores['score_total'], 0.001);
        $this->assertEqualsWithDelta($scores['score_a'] + $scores['score_b'], $scores['score_total'], 0.001);
    }

    public function test_a_perfect_attempt_scores_twenty_seventy_and_ninety(): void
    {
        $attempt = $this->attempt();

        for ($i = 0; $i < 30; $i++) {
            $this->attach($attempt, $this->makeSectionAQuestion(), 0);
        }

        for ($i = 0; $i < 70; $i++) {
            $this->attach($attempt, $this->makeSectionBQuestion(Subject::Iq), 0);
        }

        $scores = app(ScoringService::class)->calculate($attempt);

        $this->assertSame(20.0, $scores['score_a']);
        $this->assertSame(70.0, $scores['score_b']);
        $this->assertSame(90.0, $scores['score_total']);
    }

    public function test_scores_never_exceed_their_section_ceilings(): void
    {
        $attempt = $this->attempt();

        for ($i = 0; $i < 30; $i++) {
            $this->attach($attempt, $this->makeSectionAQuestion(), 0);
        }

        for ($i = 0; $i < 70; $i++) {
            $this->attach($attempt, $this->makeSectionBQuestion(Subject::Iq), 0);
        }

        $scores = app(ScoringService::class)->calculate($attempt);

        $this->assertLessThanOrEqual(20.0, $scores['score_a']);
        $this->assertLessThanOrEqual(70.0, $scores['score_b']);
        $this->assertLessThanOrEqual(90.0, $scores['score_total']);
    }

    public function test_unanswered_questions_score_zero_but_still_count_in_the_denominator(): void
    {
        $attempt = $this->attempt();

        // 10 answered with the 3-scorer, 20 unanswered. maxA is still 30 * 3 = 90.
        for ($i = 0; $i < 30; $i++) {
            $this->attach($attempt, $this->makeSectionAQuestion(), $i < 10 ? 0 : null);
        }

        // 35 correct out of 70, the rest unanswered.
        for ($i = 0; $i < 70; $i++) {
            $this->attach($attempt, $this->makeSectionBQuestion(Subject::Sains), $i < 35 ? 0 : null);
        }

        $scores = app(ScoringService::class)->calculate($attempt);

        // 30 / 90 * 20 = 6.67
        $this->assertEqualsWithDelta(6.67, $scores['score_a'], 0.001);
        $this->assertEqualsWithDelta(35.00, $scores['score_b'], 0.001);
    }

    public function test_a_fully_unanswered_attempt_scores_zero_without_dividing_by_zero(): void
    {
        $attempt = $this->attempt();

        for ($i = 0; $i < 30; $i++) {
            $this->attach($attempt, $this->makeSectionAQuestion());
        }

        for ($i = 0; $i < 70; $i++) {
            $this->attach($attempt, $this->makeSectionBQuestion(Subject::Iq));
        }

        $scores = app(ScoringService::class)->calculate($attempt);

        $this->assertSame(0.0, $scores['score_a']);
        $this->assertSame(0.0, $scores['score_b']);
        $this->assertSame(0.0, $scores['score_total']);
    }

    public function test_an_empty_attempt_scores_zero_without_dividing_by_zero(): void
    {
        $scores = app(ScoringService::class)->calculate($this->attempt());

        $this->assertSame(0.0, $scores['score_a']);
        $this->assertSame(0.0, $scores['score_b']);
        $this->assertSame(0.0, $scores['score_total']);
    }

    public function test_section_a_questions_whose_options_all_score_zero_do_not_divide_by_zero(): void
    {
        $attempt = $this->attempt();

        for ($i = 0; $i < 30; $i++) {
            $this->attach($attempt, $this->makeSectionAQuestion([0, 0, 0, 0]), 0);
        }

        $scores = app(ScoringService::class)->calculate($attempt);

        $this->assertSame(0.0, $scores['score_a']);
    }

    public function test_scores_are_rounded_to_two_decimals(): void
    {
        $attempt = $this->attempt();

        // One A question scored 1 out of a max of 3 => 1/3 * 20 = 6.666...
        $this->attach($attempt, $this->makeSectionAQuestion(), 2);

        // One B question answered wrongly => 0/1 * 70 = 0
        $this->attach($attempt, $this->makeSectionBQuestion(Subject::Iq), 1);

        $scores = app(ScoringService::class)->calculate($attempt);

        $this->assertSame(6.67, $scores['score_a']);
        $this->assertSame(round($scores['score_a'], 2), $scores['score_a']);
    }

    public function test_the_section_b_breakdown_counts_correct_and_total_per_subject(): void
    {
        $attempt = $this->attempt();

        // Matematik: 3 of 5 correct. Sains: 1 of 2 correct.
        for ($i = 0; $i < 5; $i++) {
            $this->attach($attempt, $this->makeSectionBQuestion(Subject::Matematik), $i < 3 ? 0 : 1);
        }

        for ($i = 0; $i < 2; $i++) {
            $this->attach($attempt, $this->makeSectionBQuestion(Subject::Sains), $i < 1 ? 0 : 1);
        }

        $breakdown = app(ScoringService::class)->sectionBBreakdown($attempt);

        $this->assertSame(3, $breakdown['matematik']['correct']);
        $this->assertSame(5, $breakdown['matematik']['total']);
        $this->assertSame(60.0, $breakdown['matematik']['percent']);
        $this->assertSame(1, $breakdown['sains']['correct']);
        $this->assertSame(2, $breakdown['sains']['total']);
    }

    public function test_the_section_b_breakdown_omits_subjects_and_section_a_entirely(): void
    {
        $attempt = $this->attempt();

        $this->attach($attempt, $this->makeSectionAQuestion(), 0);
        $this->attach($attempt, $this->makeSectionBQuestion(Subject::Iq), 0);

        $breakdown = app(ScoringService::class)->sectionBBreakdown($attempt);

        $this->assertSame(['iq'], array_keys($breakdown));
        $this->assertSame(1, $breakdown['iq']['total']);
    }

    public function test_an_option_with_a_null_score_contributes_nothing_to_section_a(): void
    {
        $attempt = $this->attempt();

        $question = Question::factory()->sectionA()->create();
        Option::factory()->for($question)->withScore(4)->create();
        Option::factory()->for($question)->create(['score' => null]);

        $this->attach($attempt, $question, 1);

        $scores = app(ScoringService::class)->calculate($attempt);

        $this->assertSame(0.0, $scores['score_a']);
    }
}
