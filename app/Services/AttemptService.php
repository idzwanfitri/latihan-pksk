<?php

namespace App\Services;

use App\Enums\AttemptStatus;
use App\Exceptions\AttemptExpiredException;
use App\Exceptions\AttemptNotActiveException;
use App\Exceptions\InsufficientQuestionBankException;
use App\Exceptions\InvalidOptionException;
use App\Models\Attempt;
use App\Models\AttemptQuestion;
use App\Models\Option;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class AttemptService
{
    public const int DURATION_MINUTES = 90;

    public function __construct(
        private readonly QuestionSelector $selector,
        private readonly ScoringService $scoring,
    ) {}

    /**
     * Resume the caller's live attempt, or draw a fresh one. An in-progress
     * attempt that has run past its deadline is finalised first, so the student
     * always lands on a coherent state.
     *
     * @throws InsufficientQuestionBankException
     */
    public function startOrResume(User $user): Attempt
    {
        return DB::transaction(function () use ($user): Attempt {
            $existing = $user->attempts()
                ->where('status', AttemptStatus::InProgress)
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($existing instanceof Attempt) {
                if (! $existing->isExpired()) {
                    return $existing;
                }

                // Lapsed while the student was away: finalise and score what was
                // saved, then fall through and draw them a fresh attempt.
                $this->expire($existing);
            }

            // Draw first: a short question bank must abort before any row is written.
            $questionIds = $this->selector->select();

            $now = now();

            $attempt = $user->attempts()->create([
                'started_at' => $now,
                'expires_at' => $now->addMinutes(self::DURATION_MINUTES),
                'status' => AttemptStatus::InProgress,
            ]);

            $rows = [];

            foreach ($questionIds as $index => $questionId) {
                $rows[] = [
                    'attempt_id' => $attempt->id,
                    'question_id' => $questionId,
                    'position' => $index + 1,
                    'is_flagged' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // insert() bypasses model events, so timestamps are supplied by hand.
            AttemptQuestion::query()->insert($rows);

            return $attempt;
        });
    }

    public function activeAttempt(User $user): ?Attempt
    {
        $attempt = $user->attempts()
            ->where('status', AttemptStatus::InProgress)
            ->latest('id')
            ->first();

        if (! $attempt instanceof Attempt) {
            return null;
        }

        return $attempt->isExpired() ? null : $attempt;
    }

    /**
     * The single choke point every write passes through. An expired attempt is
     * finalised (scoring whatever was already saved) and the write is rejected.
     *
     * @throws AttemptExpiredException|AttemptNotActiveException
     */
    public function assertActive(Attempt $attempt): void
    {
        if ($attempt->status !== AttemptStatus::InProgress) {
            throw new AttemptNotActiveException;
        }

        if ($attempt->isExpired()) {
            $this->expire($attempt);

            throw new AttemptExpiredException;
        }
    }

    /**
     * @throws AttemptExpiredException|AttemptNotActiveException|InvalidOptionException
     */
    public function saveAnswer(Attempt $attempt, int $position, int $optionId): void
    {
        $this->assertActive($attempt);

        $attemptQuestion = $attempt->attemptQuestions()
            ->where('position', $position)
            ->firstOrFail();

        // Without this the payload could be edited to submit an option belonging
        // to a different question, e.g. the highest-scoring Bahagian A option in
        // the whole bank for all thirty questions.
        $belongsToQuestion = Option::query()
            ->whereKey($optionId)
            ->where('question_id', $attemptQuestion->question_id)
            ->exists();

        if (! $belongsToQuestion) {
            throw new InvalidOptionException;
        }

        $attemptQuestion->forceFill(['selected_option_id' => $optionId])->save();
    }

    /**
     * @throws AttemptExpiredException|AttemptNotActiveException
     */
    public function toggleFlag(Attempt $attempt, int $position): bool
    {
        $this->assertActive($attempt);

        $attemptQuestion = $attempt->attemptQuestions()
            ->where('position', $position)
            ->firstOrFail();

        $flagged = ! $attemptQuestion->is_flagged;

        $attemptQuestion->forceFill(['is_flagged' => $flagged])->save();

        return $flagged;
    }

    /**
     * Idempotent: a second call (e.g. the auto-submit racing a manual click)
     * returns the already-finalised attempt untouched.
     */
    public function submit(Attempt $attempt): Attempt
    {
        return DB::transaction(function () use ($attempt): Attempt {
            $fresh = Attempt::query()->lockForUpdate()->findOrFail($attempt->id);

            if ($fresh->status !== AttemptStatus::InProgress) {
                return $fresh;
            }

            if ($fresh->isExpired()) {
                return $this->expire($fresh);
            }

            $fresh->update([
                ...$this->scoring->calculate($fresh),
                'status' => AttemptStatus::Submitted,
                'submitted_at' => now(),
            ]);

            return $fresh;
        });
    }

    /**
     * Finalise a lapsed attempt, scoring the answers saved before the deadline.
     */
    public function expire(Attempt $attempt): Attempt
    {
        return DB::transaction(function () use ($attempt): Attempt {
            $fresh = Attempt::query()->lockForUpdate()->findOrFail($attempt->id);

            if ($fresh->status !== AttemptStatus::InProgress) {
                return $fresh;
            }

            $fresh->update([
                ...$this->scoring->calculate($fresh),
                'status' => AttemptStatus::Expired,
                // The real cut-off, not whenever the lapse happened to be noticed.
                'submitted_at' => $fresh->expires_at,
            ]);

            return $fresh;
        });
    }
}
