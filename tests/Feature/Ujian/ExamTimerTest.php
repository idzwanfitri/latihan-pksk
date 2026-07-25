<?php

namespace Tests\Feature\Ujian;

use App\Enums\AttemptStatus;
use App\Exceptions\AttemptExpiredException;
use App\Exceptions\AttemptNotActiveException;
use App\Models\Attempt;
use App\Models\User;
use App\Services\AttemptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsQuestionBank;
use Tests\TestCase;

class ExamTimerTest extends TestCase
{
    use RefreshDatabase, SeedsQuestionBank;

    private function start(?User $user = null): Attempt
    {
        return app(AttemptService::class)->startOrResume($user ?? User::factory()->pelajar()->create());
    }

    /**
     * Answer the section-B question at $position correctly.
     */
    private function answerCorrectly(Attempt $attempt, int $position): void
    {
        $attemptQuestion = $attempt->attemptQuestions()->where('position', $position)->firstOrFail();

        $optionId = $attemptQuestion->question->options()->where('is_correct', true)->value('id');

        app(AttemptService::class)->saveAnswer($attempt, $position, (int) $optionId);
    }

    public function test_expires_at_is_exactly_ninety_minutes_after_started_at(): void
    {
        $this->seedQuestionBank();

        $attempt = $this->start();

        $this->assertSame(90 * 60, (int) $attempt->started_at->diffInSeconds($attempt->expires_at));
    }

    public function test_saving_an_answer_after_expiry_is_rejected_and_the_attempt_auto_scores_as_expired(): void
    {
        $this->seedQuestionBank();

        $attempt = $this->start();

        // Ten correct section-B answers before the deadline.
        foreach (range(31, 40) as $position) {
            $this->answerCorrectly($attempt, $position);
        }

        $this->travelTo($attempt->expires_at->addSecond());

        $target = $attempt->attemptQuestions()->where('position', 41)->firstOrFail();
        $optionId = (int) $target->question->options()->value('id');

        try {
            app(AttemptService::class)->saveAnswer($attempt, 41, $optionId);
            $this->fail('Expected the post-expiry answer to be rejected.');
        } catch (AttemptExpiredException) {
            // expected
        }

        $attempt->refresh();

        $this->assertSame(AttemptStatus::Expired, $attempt->status);
        $this->assertNull($attempt->attemptQuestions()->where('position', 41)->value('selected_option_id'));
        $this->assertSame(10.0, $attempt->score_b);
    }

    public function test_flagging_after_expiry_is_also_rejected(): void
    {
        $this->seedQuestionBank();

        $attempt = $this->start();

        $this->travelTo($attempt->expires_at->addSecond());

        $this->expectException(AttemptExpiredException::class);

        app(AttemptService::class)->toggleFlag($attempt, 1);
    }

    public function test_writing_to_an_already_finalised_attempt_is_rejected(): void
    {
        $this->seedQuestionBank();

        $attempt = $this->start();

        app(AttemptService::class)->submit($attempt);

        $this->expectException(AttemptNotActiveException::class);

        app(AttemptService::class)->toggleFlag($attempt->refresh(), 1);
    }

    public function test_submitted_at_is_set_to_expires_at_when_the_attempt_expires(): void
    {
        $this->seedQuestionBank();

        $attempt = $this->start();
        $expiresAt = $attempt->expires_at;

        $this->travelTo($expiresAt->addMinutes(30));

        app(AttemptService::class)->expire($attempt);

        $attempt->refresh();

        $this->assertSame(
            $expiresAt->toDateTimeString(),
            $attempt->submitted_at?->toDateTimeString(),
        );
    }

    public function test_submitting_a_lapsed_attempt_marks_it_expired_not_submitted(): void
    {
        $this->seedQuestionBank();

        $attempt = $this->start();

        $this->travelTo($attempt->expires_at->addSecond());

        app(AttemptService::class)->submit($attempt);

        $this->assertSame(AttemptStatus::Expired, $attempt->refresh()->status);
    }

    public function test_visiting_the_start_page_after_expiry_finalises_the_stale_attempt(): void
    {
        $this->seedQuestionBank();

        $user = User::factory()->pelajar()->create();
        $stale = $this->start($user);

        $this->travelTo($stale->expires_at->addMinute());

        $this->start($user);

        $this->assertSame(AttemptStatus::Expired, $stale->refresh()->status);
        $this->assertNotNull($stale->score_total);
    }

    public function test_submit_is_idempotent(): void
    {
        $this->seedQuestionBank();

        $attempt = $this->start();

        $service = app(AttemptService::class);

        $first = $service->submit($attempt);
        $submittedAt = $first->submitted_at?->toDateTimeString();

        $this->travelTo(now()->addMinutes(5));

        $second = $service->submit($attempt->refresh());

        $this->assertSame(AttemptStatus::Submitted, $second->status);
        $this->assertSame($submittedAt, $second->submitted_at?->toDateTimeString());
        $this->assertDatabaseCount('attempts', 1);
    }

    public function test_seconds_remaining_counts_down_and_never_goes_negative(): void
    {
        $this->seedQuestionBank();

        $attempt = $this->start();

        $this->assertSame(90 * 60, $attempt->secondsRemaining());

        $this->travelTo($attempt->started_at->addMinutes(30));
        $this->assertSame(60 * 60, $attempt->secondsRemaining());

        $this->travelTo($attempt->expires_at->addHour());
        $this->assertSame(0, $attempt->secondsRemaining());
    }

    public function test_an_active_attempt_is_not_returned_once_it_has_lapsed(): void
    {
        $this->seedQuestionBank();

        $user = User::factory()->pelajar()->create();
        $attempt = $this->start($user);

        $service = app(AttemptService::class);

        $this->assertNotNull($service->activeAttempt($user));

        $this->travelTo($attempt->expires_at->addSecond());

        $this->assertNull($service->activeAttempt($user));
    }
}
