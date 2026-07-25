<?php

use App\Enums\Section;
use App\Enums\Subject;
use App\Livewire\Admin\QuestionBankPage;
use App\Models\Question;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;

new #[Title('Soalan Bahagian B')] class extends QuestionBankPage {
    public string $subjectValue = '';

    /**
     * Bound to a radio group, so "exactly one correct" cannot be expressed
     * incorrectly in the interface at all. The rule below then defends the same
     * invariant against a tampered payload.
     */
    public ?int $correctIndex = null;

    protected function section(): Section
    {
        return Section::B;
    }

    protected function subject(): ?Subject
    {
        return Subject::tryFrom($this->subjectValue);
    }

    protected function correctOptionIndex(): ?int
    {
        return $this->correctIndex;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function validationRules(): array
    {
        return [
            ...$this->baseRules(),
            'subjectValue' => ['required', Rule::enum(Subject::class)],
            'correctIndex' => ['required', 'integer', Rule::in(array_keys($this->options))],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'questionText.required' => __('Teks soalan diperlukan.'),
            'questionText.min' => __('Teks soalan terlalu pendek.'),
            'subjectValue.required' => __('Sila pilih subjek.'),
            'options.min' => __('Sekurang-kurangnya :min pilihan diperlukan.', ['min' => self::MIN_OPTIONS]),
            'options.*.option_text.required' => __('Teks pilihan diperlukan.'),
            'correctIndex.required' => __('Sila tandakan satu jawapan yang betul.'),
            'correctIndex.in' => __('Jawapan betul yang dipilih tidak sah.'),
        ];
    }

    protected function resetForm(): void
    {
        parent::resetForm();

        $this->subjectValue = '';
        $this->correctIndex = null;
    }

    protected function fillFormFrom(Question $question): void
    {
        parent::fillFormFrom($question);

        $this->subjectValue = $question->subject?->value ?? '';
        $this->correctIndex = null;

        foreach ($question->options()->orderBy('id')->get()->values() as $index => $pilihan) {
            if ($pilihan->is_correct) {
                $this->correctIndex = $index;
            }
        }
    }

    /**
     * Removing a row shifts every later index, so the stored answer has to move
     * with it. Without this, deleting option 1 silently reassigns the correct
     * answer to a different option.
     */
    protected function handleOptionRemoved(int $index): void
    {
        if ($this->correctIndex === null) {
            return;
        }

        if ($this->correctIndex === $index) {
            $this->correctIndex = null;

            return;
        }

        if ($this->correctIndex > $index) {
            $this->correctIndex--;
        }
    }
}; ?>

<section class="w-full p-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <flux:heading size="xl">{{ __('Soalan Bahagian B') }}</flux:heading>
            <flux:subheading class="mt-1">{{ __('Kecerdasan Intelek — tepat satu jawapan betul bagi setiap soalan.') }}</flux:subheading>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="create">
            {{ __('Tambah Soalan') }}
        </flux:button>
    </div>

    <flux:input
        class="mt-6 max-w-md"
        wire:model.live.debounce.300ms="search"
        icon="magnifying-glass"
        :placeholder="__('Cari teks soalan...')"
    />

    <flux:card class="mt-4">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Soalan') }}</flux:table.column>
                <flux:table.column>{{ __('Subjek') }}</flux:table.column>
                <flux:table.column>{{ __('Pilihan') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->questions as $soalan)
                    <flux:table.row :key="$soalan->id">
                        <flux:table.cell class="max-w-sm">
                            <span class="line-clamp-2">{{ $soalan->question_text }}</span>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" color="blue">{{ $soalan->subject?->label() }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ $soalan->options_count }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$soalan->is_active ? 'green' : 'zinc'">
                                {{ $soalan->is_active ? __('Aktif') : __('Tidak aktif') }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex justify-end gap-1">
                                <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="edit({{ $soalan->id }})" />
                                <flux:button size="sm" variant="ghost" icon="trash" wire:click="confirmDelete({{ $soalan->id }})" />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5">
                            <flux:text>{{ __('Tiada soalan dijumpai.') }}</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        <div class="mt-4">
            <flux:pagination :paginator="$this->questions" />
        </div>
    </flux:card>

    {{-- Borang tambah / kemas kini --}}
    <flux:modal name="borang-soalan" class="max-w-2xl">
        <form wire:submit="save" class="space-y-6">
            <flux:heading size="lg">
                {{ $editingId === null ? __('Tambah Soalan Bahagian B') : __('Kemas Kini Soalan Bahagian B') }}
            </flux:heading>

            <flux:select wire:model="subjectValue" :label="__('Subjek')" :placeholder="__('Pilih subjek...')">
                @foreach (\App\Enums\Subject::cases() as $subjek)
                    <flux:select.option value="{{ $subjek->value }}">{{ $subjek->label() }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:textarea
                wire:model="questionText"
                :label="__('Teks soalan')"
                rows="3"
                :placeholder="__('Contoh: Lengkapkan siri nombor berikut: 2, 4, 8, 16, ___')"
            />

            <div>
                <flux:label>{{ __('Pilihan jawapan') }}</flux:label>
                <flux:description>{{ __('Isi teks setiap pilihan, kemudian tandakan satu jawapan yang betul di bawah.') }}</flux:description>

                <div class="mt-3 space-y-3">
                    @foreach ($options as $index => $pilihan)
                        <div wire:key="baris-{{ $pilihan['key'] }}" class="flex items-start gap-3">
                            <div class="flex-1">
                                <flux:input
                                    wire:model.live.debounce.500ms="options.{{ $index }}.option_text"
                                    :placeholder="__('Pilihan :n', ['n' => $index + 1])"
                                />
                                <flux:error name="options.{{ $index }}.option_text" />
                            </div>

                            <flux:button
                                type="button"
                                variant="subtle"
                                icon="trash"
                                wire:click="removeOption({{ $index }})"
                            />
                        </div>
                    @endforeach
                </div>

                <flux:error name="options" />

                <flux:button
                    type="button"
                    variant="ghost"
                    size="sm"
                    icon="plus"
                    class="mt-3"
                    wire:click="addOption"
                >
                    {{ __('Tambah pilihan') }}
                </flux:button>
            </div>

            {{-- A radio group can only ever hold one value, so "no correct
                 answer" and "two correct answers" are both unreachable states. --}}
            <div>
                <flux:radio.group wire:model="correctIndex" variant="cards" :label="__('Jawapan betul')" class="flex-col">
                    @foreach ($options as $index => $pilihan)
                        <flux:radio
                            wire:key="betul-{{ $pilihan['key'] }}"
                            :value="$index"
                            :label="filled($pilihan['option_text'])
                                ? $pilihan['option_text']
                                : __('Pilihan :n (belum diisi)', ['n' => $index + 1])"
                        />
                    @endforeach
                </flux:radio.group>

                <flux:error name="correctIndex" />
            </div>

            <flux:switch wire:model="isActive" :label="__('Aktif')" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button type="button" variant="ghost">{{ __('Batal') }}</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary">{{ __('Simpan') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Sahkan padam --}}
    <flux:modal name="sahkan-padam" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Padam soalan ini?') }}</flux:heading>

            <flux:text>
                {{ __('Jika soalan ini pernah digunakan dalam sesuatu ujian, ia akan dinyahaktifkan dan bukan dipadam, supaya keputusan lampau kekal tepat.') }}
            </flux:text>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Batal') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="delete">{{ __('Padam') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</section>
