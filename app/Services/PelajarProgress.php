<?php

namespace App\Services;

use App\Enums\AttemptStatus;
use App\Models\Attempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Read-only summary of a student's exam history, for the dashboard.
 */
final class PelajarProgress
{
    public function __construct(private readonly AttemptService $attempts) {}

    public function activeAttempt(User $user): ?Attempt
    {
        return $this->attempts->activeAttempt($user);
    }

    public function latestResult(User $user): ?Attempt
    {
        return $this->finalisedQuery($user)->latest('id')->first();
    }

    /**
     * @return Collection<int, Attempt>
     */
    public function recentResults(User $user, int $limit = 5): Collection
    {
        return $this->finalisedQuery($user)->latest('id')->limit($limit)->get();
    }

    public function attemptCount(User $user): int
    {
        return $this->finalisedQuery($user)->count();
    }

    public function bestTotal(User $user): ?float
    {
        $best = $this->finalisedQuery($user)->max('score_total');

        return $best === null ? null : (float) $best;
    }

    /**
     * @return Builder<Attempt>
     */
    private function finalisedQuery(User $user)
    {
        return $user->attempts()
            ->whereIn('status', [AttemptStatus::Submitted, AttemptStatus::Expired])
            ->getQuery();
    }
}
