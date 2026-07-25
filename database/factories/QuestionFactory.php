<?php

namespace Database\Factories;

use App\Enums\Section;
use App\Enums\Subject;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'section' => Section::A,
            'subject' => null,
            'question_text' => fake()->sentence(12),
            'is_active' => true,
        ];
    }

    public function sectionA(): static
    {
        return $this->state(fn (array $attributes): array => [
            'section' => Section::A,
            'subject' => null,
        ]);
    }

    public function sectionB(?Subject $subject = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'section' => Section::B,
            'subject' => $subject ?? fake()->randomElement(Subject::cases()),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
