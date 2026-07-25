<?php

namespace App\Concerns;

use App\Enums\Section;
use App\Enums\Subject;
use App\Models\Option;
use App\Models\Question;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Shared state and persistence for the two question-bank screens.
 *
 * The Livewire components themselves live in `resources/`, which PHPStan does
 * not analyse, so everything with actual logic in it is kept here instead.
 *
 * @phpstan-require-extends Component
 */
trait ManagesQuestions
{
    use WithPagination;

    public string $search = '';

    public ?int $editingId = null;

    public ?int $deletingId = null;

    public string $questionText = '';

    public bool $isActive = true;

    /** @var array<int, array{key: string, id: int|null, option_text: string, score: int}> */
    public array $options = [];

    public function updatedSearch(): void
    {
        // Without this, searching from page 3 lands on an empty result set.
        $this->resetPage();
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function questionTextRules(): array
    {
        return ['required', 'string', 'min:10', 'max:1000'];
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function baseRules(): array
    {
        return [
            'questionText' => $this->questionTextRules(),
            'isActive' => ['boolean'],
            'options' => ['required', 'array', 'min:'.Question::MIN_OPTIONS, 'max:'.Question::MAX_OPTIONS],
            'options.*.option_text' => ['required', 'string', 'max:500'],
        ];
    }

    /**
     * @param  Builder<Question>  $query
     * @return Builder<Question>
     */
    protected function applySearch(Builder $query): Builder
    {
        $search = trim($this->search);

        if ($search === '') {
            return $query;
        }

        return $query->where('question_text', 'like', '%'.$search.'%');
    }

    public function addOption(): void
    {
        if (count($this->options) >= Question::MAX_OPTIONS) {
            $this->addError('options', __('Maksimum :bil pilihan sahaja.', ['bil' => Question::MAX_OPTIONS]));

            return;
        }

        $this->options[] = $this->blankOption();
    }

    public function removeOption(int $index): void
    {
        if (count($this->options) <= Question::MIN_OPTIONS) {
            $this->addError('options', __('Sekurang-kurangnya :bil pilihan diperlukan.', ['bil' => Question::MIN_OPTIONS]));

            return;
        }

        if (! array_key_exists($index, $this->options)) {
            return;
        }

        unset($this->options[$index]);

        // Re-index or `wire:model="options.3.option_text"` binds to a hole and
        // Livewire silently drops every edit made to the rows after it.
        $this->options = array_values($this->options);

        $this->handleOptionRemoved($index);
    }

    /**
     * Hook for section-specific bookkeeping after a row disappears.
     */
    protected function handleOptionRemoved(int $index): void
    {
        //
    }

    /**
     * @return array{key: string, id: int|null, option_text: string, score: int}
     */
    protected function blankOption(): array
    {
        return [
            // Stable per-row id. Keying on the loop index instead means deleting
            // a middle row remaps every later key and morphdom carries stale DOM
            // into the wrong row.
            'key' => Str::uuid()->toString(),
            'id' => null,
            'option_text' => '',
            'score' => 0,
        ];
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->questionText = '';
        $this->isActive = true;
        $this->options = [];

        for ($i = 0; $i < Question::MIN_OPTIONS; $i++) {
            $this->options[] = $this->blankOption();
        }

        $this->resetErrorBag();
    }

    protected function fillFormFrom(Question $question): void
    {
        $this->editingId = $question->id;
        $this->questionText = $question->question_text;
        $this->isActive = $question->is_active;

        $this->options = $question->options()
            ->orderBy('id')
            ->get()
            ->map(fn (Option $option): array => [
                'key' => Str::uuid()->toString(),
                'id' => $option->id,
                'option_text' => $option->option_text,
                'score' => (int) $option->score,
            ])
            ->all();

        $this->resetErrorBag();
    }

    /**
     * Create or update a question together with its options.
     *
     * `$correctIndex` is only meaningful for section B; section A stores a score
     * per option instead and never marks one correct.
     */
    protected function persistQuestion(Section $section, ?Subject $subject, ?int $correctIndex = null): Question
    {
        return DB::transaction(function () use ($section, $subject, $correctIndex): Question {
            $question = $this->editingId !== null
                ? Question::query()->findOrFail($this->editingId)
                : new Question;

            $question->fill([
                'section' => $section,
                'subject' => $subject,
                'question_text' => trim($this->questionText),
                'is_active' => $this->isActive,
            ])->save();

            /** @var array<int, int> $keptIds */
            $keptIds = [];

            foreach ($this->options as $index => $row) {
                $payload = [
                    'option_text' => trim($row['option_text']),
                    'score' => $section === Section::A ? (int) $row['score'] : null,
                    'is_correct' => $correctIndex !== null && $index === $correctIndex,
                ];

                $existing = $row['id'] === null
                    ? null
                    : $question->options()->whereKey($row['id'])->first();

                if ($existing instanceof Option) {
                    $existing->update($payload);
                    $keptIds[] = $existing->id;

                    continue;
                }

                $keptIds[] = $question->options()->create($payload)->id;
            }

            $question->options()->whereNotIn('id', $keptIds)->delete();

            return $question;
        });
    }

    /**
     * Remove a question, or deactivate it when it is already part of an attempt.
     *
     * The foreign key is restrictOnDelete precisely so historical attempts keep
     * their hundred questions and stay reproducible; deactivating instead takes
     * the question out of every future draw without rewriting the past.
     *
     * @return bool true when the row was deleted, false when it was deactivated
     */
    protected function removeOrDeactivate(Question $question): bool
    {
        if ($question->attemptQuestions()->exists()) {
            $question->update(['is_active' => false]);

            return false;
        }

        $question->delete();

        return true;
    }
}
