<?php

namespace App\Services;

use App\Enums\Section;
use App\Enums\Subject;
use App\Models\Question;
use Illuminate\Support\Facades\DB;

/**
 * Bulk-load questions from a JSON file.
 *
 * The admin screens enforce a set of invariants that the exam silently depends
 * on — at least four options, exactly one correct answer in section B, at least
 * one positive score in section A. A bulk path that skipped those checks would
 * let a malformed question sit in the bank until it turned up in a real paper,
 * so every item is validated here to the same standard and a file with any
 * error imports nothing at all.
 */
final class QuestionImporter
{
    /**
     * @param  array<mixed>  $payload
     */
    public function import(array $payload, bool $activate = false, bool $dryRun = false): QuestionImportResult
    {
        $result = new QuestionImportResult;

        $sectionA = $this->sectionPayload($payload, 'section_a', $result);
        $sectionB = $this->sectionPayload($payload, 'section_b', $result);

        if ($result->hasErrors()) {
            return $result;
        }

        /** @var array<int, array{text: string, options: array<int, array{text: string, score: int}>}> $validA */
        $validA = [];
        /** @var array<int, array{subject: Subject, text: string, options: array<int, string>, correct: int}> $validB */
        $validB = [];

        $seen = [];

        foreach ($sectionA as $index => $item) {
            $parsed = $this->validateSectionA($item, 'section_a['.$index.']', $result);

            if ($parsed === null) {
                continue;
            }

            if ($this->isDuplicate($parsed['text'], $seen, $result, 'section_a['.$index.']')) {
                continue;
            }

            $validA[] = $parsed;
        }

        foreach ($sectionB as $index => $item) {
            $parsed = $this->validateSectionB($item, 'section_b['.$index.']', $result);

            if ($parsed === null) {
                continue;
            }

            if ($this->isDuplicate($parsed['text'], $seen, $result, 'section_b['.$index.']')) {
                continue;
            }

            $validB[] = $parsed;
        }

        // All-or-nothing: a half-imported file is harder to reason about than a
        // rejected one, and the administrator can fix and re-run. Read the array
        // directly — the validation loops above mutate it, so a cached
        // hasErrors() result from before them would be stale.
        if ($result->errors !== [] || $dryRun) {
            $result->importedA = count($validA);
            $result->importedB = count($validB);

            return $result;
        }

        DB::transaction(function () use ($validA, $validB, $activate, $result): void {
            foreach ($validA as $item) {
                $question = Question::query()->create([
                    'section' => Section::A,
                    'subject' => null,
                    'question_text' => $item['text'],
                    'is_active' => $activate,
                ]);

                foreach ($item['options'] as $option) {
                    $question->options()->create([
                        'option_text' => $option['text'],
                        'score' => $option['score'],
                        'is_correct' => false,
                    ]);
                }

                $result->importedA++;
            }

            foreach ($validB as $item) {
                $question = Question::query()->create([
                    'section' => Section::B,
                    'subject' => $item['subject'],
                    'question_text' => $item['text'],
                    'is_active' => $activate,
                ]);

                foreach ($item['options'] as $optionIndex => $optionText) {
                    $question->options()->create([
                        'option_text' => $optionText,
                        'score' => null,
                        'is_correct' => $optionIndex === $item['correct'],
                    ]);
                }

                $result->importedB++;
            }
        });

        return $result;
    }

    /**
     * @param  array<mixed>  $payload
     * @return array<int, mixed>
     */
    private function sectionPayload(array $payload, string $key, QuestionImportResult $result): array
    {
        if (! array_key_exists($key, $payload)) {
            return [];
        }

        if (! is_array($payload[$key]) || ! array_is_list($payload[$key])) {
            $result->addError($key, 'mesti berupa senarai (array).');

            return [];
        }

        return $payload[$key];
    }

    /**
     * @param  array<string, bool>  $seen
     */
    private function isDuplicate(string $text, array &$seen, QuestionImportResult $result, string $where): bool
    {
        $fingerprint = mb_strtolower(trim($text));

        if (isset($seen[$fingerprint])) {
            $result->addSkipped($where, 'pendua dalam fail yang sama.');

            return true;
        }

        $seen[$fingerprint] = true;

        if (Question::query()->where('question_text', trim($text))->exists()) {
            $result->addSkipped($where, 'sudah wujud dalam bank soalan.');

            return true;
        }

        return false;
    }

    /**
     * @return array{text: string, options: array<int, array{text: string, score: int}>}|null
     */
    private function validateSectionA(mixed $item, string $where, QuestionImportResult $result): ?array
    {
        if (! is_array($item)) {
            $result->addError($where, 'mesti berupa objek.');

            return null;
        }

        $text = $this->questionText($item, $where, $result);
        $rawOptions = $this->optionList($item, $where, $result);

        if ($text === null || $rawOptions === null) {
            return null;
        }

        $options = [];
        $highest = 0;

        foreach ($rawOptions as $index => $option) {
            if (! is_array($option) || ! isset($option['text'])) {
                $result->addError($where, 'pilihan '.$index.' mesti objek dengan kunci "text" dan "score".');

                return null;
            }

            $optionText = trim((string) $option['text']);

            if ($optionText === '') {
                $result->addError($where, 'teks pilihan '.$index.' kosong.');

                return null;
            }

            if (! isset($option['score']) || ! is_numeric($option['score'])) {
                $result->addError($where, 'pilihan '.$index.' perlukan "score" berupa nombor.');

                return null;
            }

            $score = (int) $option['score'];

            if ($score < 0 || $score > 10) {
                $result->addError($where, 'skor pilihan '.$index.' mesti antara 0 dan 10.');

                return null;
            }

            $highest = max($highest, $score);
            $options[] = ['text' => $optionText, 'score' => $score];
        }

        // A question whose options all score zero contributes nothing to the
        // maximum obtainable score, so it would quietly stop counting.
        if ($highest <= 0) {
            $result->addError($where, 'sekurang-kurangnya satu pilihan mesti berskor melebihi 0.');

            return null;
        }

        return ['text' => $text, 'options' => $options];
    }

    /**
     * @return array{subject: Subject, text: string, options: array<int, string>, correct: int}|null
     */
    private function validateSectionB(mixed $item, string $where, QuestionImportResult $result): ?array
    {
        if (! is_array($item)) {
            $result->addError($where, 'mesti berupa objek.');

            return null;
        }

        $text = $this->questionText($item, $where, $result);
        $rawOptions = $this->optionList($item, $where, $result);

        $subject = isset($item['subject']) && is_string($item['subject'])
            ? Subject::tryFrom($item['subject'])
            : null;

        if (! $subject instanceof Subject) {
            $result->addError($where, 'subjek tidak sah. Guna salah satu: '.implode(', ', Subject::values()).'.');

            return null;
        }

        if ($text === null || $rawOptions === null) {
            return null;
        }

        $options = [];

        foreach ($rawOptions as $index => $option) {
            if (! is_string($option) && ! is_numeric($option)) {
                $result->addError($where, 'pilihan '.$index.' mesti berupa teks.');

                return null;
            }

            $optionText = trim((string) $option);

            if ($optionText === '') {
                $result->addError($where, 'teks pilihan '.$index.' kosong.');

                return null;
            }

            $options[] = $optionText;
        }

        if (count(array_unique($options)) !== count($options)) {
            $result->addError($where, 'terdapat pilihan yang berulang.');

            return null;
        }

        if (! isset($item['correct']) || ! is_numeric($item['correct'])) {
            $result->addError($where, 'perlukan "correct" berupa indeks nombor bermula 0.');

            return null;
        }

        $correct = (int) $item['correct'];

        if ($correct < 0 || $correct >= count($options)) {
            $result->addError($where, 'indeks "correct" ('.$correct.') di luar julat 0-'.(count($options) - 1).'.');

            return null;
        }

        return ['subject' => $subject, 'text' => $text, 'options' => $options, 'correct' => $correct];
    }

    /**
     * @param  array<mixed>  $item
     */
    private function questionText(array $item, string $where, QuestionImportResult $result): ?string
    {
        if (! isset($item['text']) || ! is_string($item['text'])) {
            $result->addError($where, 'perlukan "text" berupa teks soalan.');

            return null;
        }

        $text = trim($item['text']);

        if (mb_strlen($text) < 10) {
            $result->addError($where, 'teks soalan terlalu pendek (minimum 10 aksara).');

            return null;
        }

        if (mb_strlen($text) > 1000) {
            $result->addError($where, 'teks soalan melebihi 1000 aksara.');

            return null;
        }

        return $text;
    }

    /**
     * @param  array<mixed>  $item
     * @return array<int, mixed>|null
     */
    private function optionList(array $item, string $where, QuestionImportResult $result): ?array
    {
        if (! isset($item['options']) || ! is_array($item['options']) || ! array_is_list($item['options'])) {
            $result->addError($where, 'perlukan "options" berupa senarai.');

            return null;
        }

        $count = count($item['options']);

        if ($count < Question::MIN_OPTIONS || $count > Question::MAX_OPTIONS) {
            $result->addError($where, 'bilangan pilihan mesti antara '.Question::MIN_OPTIONS.' dan '.Question::MAX_OPTIONS.' (dapat '.$count.').');

            return null;
        }

        return $item['options'];
    }
}
