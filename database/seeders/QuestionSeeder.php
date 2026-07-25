<?php

namespace Database\Seeders;

use App\Enums\Section;
use App\Enums\Subject;
use App\Models\Question;
use Database\Seeders\Data\SoalanA;
use Database\Seeders\Data\SoalanB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuestionSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedBahagianA();
            $this->seedBahagianB();
        });
    }

    private function seedBahagianA(): void
    {
        foreach (SoalanA::all() as $item) {
            $question = Question::query()->create([
                'section' => Section::A,
                'subject' => null,
                'question_text' => $item['text'],
                'is_active' => true,
            ]);

            $options = $item['options'];
            shuffle($options);

            foreach ($options as $option) {
                $question->options()->create([
                    'option_text' => $option['text'],
                    'score' => $option['score'],
                    'is_correct' => false,
                ]);
            }
        }
    }

    private function seedBahagianB(): void
    {
        foreach (SoalanB::all() as $item) {
            $question = Question::query()->create([
                'section' => Section::B,
                'subject' => Subject::from($item['subject']),
                'question_text' => $item['text'],
                'is_active' => true,
            ]);

            // Shuffle so the answer key is not inferable from option position,
            // tracking the correct choice by its text rather than its index.
            $correctText = $item['options'][$item['correct']];

            $options = $item['options'];
            shuffle($options);

            foreach ($options as $optionText) {
                $question->options()->create([
                    'option_text' => $optionText,
                    'score' => null,
                    'is_correct' => $optionText === $correctText,
                ]);
            }
        }
    }
}
