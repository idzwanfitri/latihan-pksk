<?php

use App\Enums\Section;
use App\Enums\Subject;
use App\Livewire\Admin\QuestionBankPage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;

new #[Title('Soalan Bahagian A')] class extends QuestionBankPage {
    protected function section(): Section
    {
        return Section::A;
    }

    protected function subject(): ?Subject
    {
        return null;
    }

    protected function correctOptionIndex(): ?int
    {
        // Bahagian A has no correct answer; every option carries a score instead.
        return null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function validationRules(): array
    {
        return [
            ...$this->baseRules(),
            'options.*.score' => ['required', 'integer', 'min:0', 'max:10'],
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
            'options.min' => __('Sekurang-kurangnya :min pilihan diperlukan.', ['min' => self::MIN_OPTIONS]),
            'options.*.option_text.required' => __('Teks pilihan diperlukan.'),
            'options.*.score.required' => __('Skor diperlukan.'),
            'options.*.score.integer' => __('Skor mesti nombor bulat.'),
            'options.*.score.min' => __('Skor tidak boleh negatif.'),
        ];
    }

    /**
     * A question whose options all score zero contributes nothing to the maximum
     * obtainable score, so it would silently stop counting towards Bahagian A.
     */
    protected function beforeSave(): void
    {
        $tertinggi = 0;

        foreach ($this->options as $pilihan) {
            $tertinggi = max($tertinggi, (int) $pilihan['score']);
        }

        if ($tertinggi <= 0) {
            throw ValidationException::withMessages([
                'options' => __('Sekurang-kurangnya satu pilihan mesti mempunyai skor melebihi 0.'),
            ]);
        }
    }
}; ?>

<section class="w-full p-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <flux:heading size="xl">{{ __('Soalan Bahagian A') }}</flux:heading>
            <flux:subheading class="mt-1">{{ __('Kecerdasan Insaniah — setiap pilihan membawa skor, tiada jawapan betul.') }}</flux:subheading>
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
                <flux:table.column>{{ __('Pilihan') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->questions as $soalan)
                    <flux:table.row :key="$soalan->id">
                        <flux:table.cell class="max-w-md">
                            <span class="line-clamp-2">{{ $soalan->question_text }}</span>
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
                        <flux:table.cell colspan="4">
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
                {{ $editingId === null ? __('Tambah Soalan Bahagian A') : __('Kemas Kini Soalan Bahagian A') }}
            </flux:heading>

            <flux:textarea
                wire:model="questionText"
                :label="__('Teks soalan')"
                rows="3"
                :placeholder="__('Contoh: Rakan sekelas anda meminta menyalin kerja rumah anda. Apakah tindakan anda?')"
            />

            <div>
                <flux:label>{{ __('Pilihan jawapan dan skor') }}</flux:label>
                <flux:description>
                    {{ __('Beri skor lebih tinggi kepada pilihan yang lebih menggambarkan nilai murni. Lazimnya 3 / 2 / 1 / 0.') }}
                </flux:description>

                <div class="mt-3 space-y-3">
                    @foreach ($options as $index => $pilihan)
                        <div wire:key="baris-{{ $pilihan['key'] }}" class="flex items-start gap-3">
                            <div class="flex-1">
                                <flux:input
                                    wire:model="options.{{ $index }}.option_text"
                                    :placeholder="__('Pilihan :n', ['n' => $index + 1])"
                                />
                                <flux:error name="options.{{ $index }}.option_text" />
                            </div>

                            <div class="w-24">
                                <flux:input
                                    type="number"
                                    min="0"
                                    max="10"
                                    wire:model="options.{{ $index }}.score"
                                    :placeholder="__('Skor')"
                                />
                                <flux:error name="options.{{ $index }}.score" />
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
