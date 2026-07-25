<?php

namespace Tests\Feature\Ujian;

use App\Enums\Section;
use App\Enums\Subject;
use App\Exceptions\InsufficientQuestionBankException;
use App\Models\Attempt;
use App\Models\Question;
use App\Models\User;
use App\Services\AttemptService;
use App\Services\QuestionSelector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\Concerns\SeedsQuestionBank;
use Tests\TestCase;

class QuestionSelectionTest extends TestCase
{
    use RefreshDatabase, SeedsQuestionBank;

    private function startAttemptForNewUser(): Attempt
    {
        return app(AttemptService::class)->startOrResume(User::factory()->pelajar()->create());
    }

    /**
     * @return Collection<int, Question>
     */
    private function questionsOf(Attempt $attempt)
    {
        return $attempt->attemptQuestions()
            ->with('question')
            ->orderBy('position')
            ->get()
            ->map(fn ($attemptQuestion): Question => $attemptQuestion->question);
    }

    public function test_a_new_attempt_contains_exactly_one_hundred_questions(): void
    {
        $this->seedQuestionBank();

        $attempt = $this->startAttemptForNewUser();

        $this->assertSame(100, $attempt->attemptQuestions()->count());
    }

    public function test_a_new_attempt_contains_exactly_thirty_section_a_questions(): void
    {
        $this->seedQuestionBank();

        $questions = $this->questionsOf($this->startAttemptForNewUser());

        $this->assertSame(30, $questions->where('section', Section::A)->count());
    }

    public function test_a_new_attempt_contains_exactly_ten_bahasa_inggeris_questions(): void
    {
        $this->seedQuestionBank();

        $questions = $this->questionsOf($this->startAttemptForNewUser());

        $this->assertSame(10, $questions->where('subject', Subject::BahasaInggeris)->count());
    }

    public function test_a_new_attempt_contains_exactly_sixty_non_english_section_b_questions(): void
    {
        $this->seedQuestionBank();

        $questions = $this->questionsOf($this->startAttemptForNewUser());

        $nonEnglishB = $questions
            ->where('section', Section::B)
            ->filter(fn (Question $question): bool => $question->subject !== Subject::BahasaInggeris);

        $this->assertSame(60, $nonEnglishB->count());
    }

    public function test_positions_are_exactly_one_to_one_hundred_and_unique(): void
    {
        $this->seedQuestionBank();

        $attempt = $this->startAttemptForNewUser();

        $positions = $attempt->attemptQuestions()->orderBy('position')->pluck('position')->all();

        $this->assertSame(range(1, 100), $positions);
    }

    public function test_positions_one_to_thirty_are_section_a_and_the_rest_are_section_b(): void
    {
        $this->seedQuestionBank();

        $questions = $this->questionsOf($this->startAttemptForNewUser())->values();

        foreach ($questions as $index => $question) {
            $expected = $index < QuestionSelector::SECTION_A_COUNT ? Section::A : Section::B;

            $this->assertSame($expected, $question->section, 'Position '.($index + 1).' has the wrong section.');
        }
    }

    public function test_no_question_is_selected_twice_within_an_attempt(): void
    {
        $this->seedQuestionBank();

        $attempt = $this->startAttemptForNewUser();

        $ids = $attempt->attemptQuestions()->pluck('question_id')->all();

        $this->assertCount(100, array_unique($ids));
    }

    public function test_inactive_questions_are_never_selected(): void
    {
        $this->seedQuestionBank(sectionA: 40, bahasaInggeris: 10, lain: 60);

        $inactive = collect(range(1, 5))
            ->map(fn (): Question => $this->makeSectionBQuestion(Subject::BahasaInggeris, isActive: false))
            ->pluck('id')
            ->all();

        $attempt = $this->startAttemptForNewUser();

        $selected = $attempt->attemptQuestions()->pluck('question_id')->all();

        $this->assertEmpty(array_intersect($inactive, $selected));
    }

    public function test_the_selected_order_persists_across_reloads(): void
    {
        $this->seedQuestionBank();

        $attempt = $this->startAttemptForNewUser();

        $snapshot = $attempt->attemptQuestions()->orderBy('position')->pluck('question_id')->all();

        $attempt->refresh();
        $reloaded = $attempt->attemptQuestions()->orderBy('position')->pluck('question_id')->all();

        $this->assertSame($snapshot, $reloaded);
    }

    public function test_two_separate_attempts_draw_different_question_sets(): void
    {
        $this->seedQuestionBank();

        $service = app(AttemptService::class);

        $first = $service->startOrResume(User::factory()->pelajar()->create());
        $second = $service->startOrResume(User::factory()->pelajar()->create());

        $this->assertNotSame(
            $first->attemptQuestions()->orderBy('position')->pluck('question_id')->all(),
            $second->attemptQuestions()->orderBy('position')->pluck('question_id')->all(),
        );
    }

    public function test_starting_with_an_active_attempt_resumes_it_instead_of_creating_a_new_one(): void
    {
        $this->seedQuestionBank();

        $user = User::factory()->pelajar()->create();
        $service = app(AttemptService::class);

        $first = $service->startOrResume($user);
        $second = $service->startOrResume($user);

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('attempts', 1);
    }

    public function test_starting_after_the_previous_attempt_expired_creates_a_new_one(): void
    {
        $this->seedQuestionBank();

        $user = User::factory()->pelajar()->create();
        $service = app(AttemptService::class);

        $first = $service->startOrResume($user);

        $this->travelTo($first->expires_at->addMinute());

        $second = $service->startOrResume($user);

        $this->assertNotSame($first->id, $second->id);
        $this->assertDatabaseCount('attempts', 2);
    }

    public function test_it_throws_when_the_bahasa_inggeris_pool_is_too_small(): void
    {
        $this->seedQuestionBank(sectionA: 40, bahasaInggeris: 9, lain: 75);

        $this->expectException(InsufficientQuestionBankException::class);

        $this->startAttemptForNewUser();
    }

    public function test_no_attempt_is_written_when_the_question_bank_is_too_small(): void
    {
        $this->seedQuestionBank(sectionA: 40, bahasaInggeris: 9, lain: 75);

        try {
            $this->startAttemptForNewUser();
        } catch (InsufficientQuestionBankException) {
            // expected
        }

        $this->assertDatabaseCount('attempts', 0);
        $this->assertDatabaseCount('attempt_questions', 0);
    }
}
