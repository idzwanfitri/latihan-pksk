<?php

namespace App\Livewire\Admin;

use App\Concerns\ManagesQuestions;
use App\Enums\Section;
use App\Enums\Subject;
use App\Models\Question;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Validation\ValidationRule;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Shared behaviour for the two question-bank screens.
 *
 * The Blade single-file components extend this class so that every branch worth
 * checking lives in `app/`, which PHPStan analyses, rather than in `resources/`,
 * which it does not.
 */
abstract class QuestionBankPage extends Component
{
    use ManagesQuestions;

    abstract protected function section(): Section;

    /**
     * Section A questions carry no subject; section B requires one.
     */
    abstract protected function subject(): ?Subject;

    /**
     * Zero-based index of the correct option, or null for section A where no
     * option is correct.
     */
    abstract protected function correctOptionIndex(): ?int;

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    abstract protected function validationRules(): array;

    public function mount(): void
    {
        $this->resetForm();
    }

    /**
     * @return LengthAwarePaginator<int, Question>
     */
    #[Computed]
    public function questions(): LengthAwarePaginator
    {
        return $this->applySearch(
            Question::query()->where('section', $this->section())
        )
            ->withCount('options')
            ->latest('id')
            ->paginate(10);
    }

    public function create(): void
    {
        $this->resetForm();

        Flux::modal('borang-soalan')->show();
    }

    public function edit(int $questionId): void
    {
        $question = Question::query()
            ->where('section', $this->section())
            ->findOrFail($questionId);

        $this->fillFormFrom($question);

        Flux::modal('borang-soalan')->show();
    }

    public function save(): void
    {
        $this->validate($this->validationRules());

        $this->beforeSave();

        $this->persistQuestion($this->section(), $this->subject(), $this->correctOptionIndex());

        unset($this->questions);

        Flux::modal('borang-soalan')->close();

        Flux::toast(
            variant: 'success',
            text: $this->editingId === null ? __('Soalan ditambah.') : __('Soalan dikemas kini.'),
        );

        $this->resetForm();
    }

    /**
     * Hook for cross-row checks that array rules cannot express.
     */
    protected function beforeSave(): void
    {
        //
    }

    public function confirmDelete(int $questionId): void
    {
        $this->deletingId = $questionId;

        Flux::modal('sahkan-padam')->show();
    }

    public function delete(): void
    {
        if ($this->deletingId === null) {
            return;
        }

        $question = Question::query()
            ->where('section', $this->section())
            ->findOrFail($this->deletingId);

        $deleted = $this->removeOrDeactivate($question);

        $this->deletingId = null;

        unset($this->questions);

        Flux::modal('sahkan-padam')->close();

        $deleted
            ? Flux::toast(variant: 'success', text: __('Soalan dipadam.'))
            : Flux::toast(
                variant: 'warning',
                text: __('Soalan ini telah digunakan dalam ujian. Ia dinyahaktifkan dan tidak akan dipilih lagi.'),
            );
    }
}
