<?php

namespace Database\Factories;

use App\Enums\AttemptStatus;
use App\Models\Attempt;
use App\Models\User;
use App\Services\AttemptService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attempt>
 */
class AttemptFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startedAt = now();

        return [
            'user_id' => User::factory(),
            'started_at' => $startedAt,
            'expires_at' => $startedAt->addMinutes(AttemptService::DURATION_MINUTES),
            'submitted_at' => null,
            'status' => AttemptStatus::InProgress,
            'score_a' => null,
            'score_b' => null,
            'score_total' => null,
        ];
    }

    public function expired(): static
    {
        return $this->state(function (array $attributes): array {
            $startedAt = now()->subMinutes(AttemptService::DURATION_MINUTES + 10);

            return [
                'started_at' => $startedAt,
                'expires_at' => $startedAt->addMinutes(AttemptService::DURATION_MINUTES),
            ];
        });
    }
}
