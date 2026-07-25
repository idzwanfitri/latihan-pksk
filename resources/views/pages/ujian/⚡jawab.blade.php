<?php

use App\Enums\AttemptStatus;
use App\Enums\Section;
use App\Exceptions\AttemptExpiredException;
use App\Exceptions\AttemptNotActiveException;
use App\Exceptions\InvalidOptionException;
use App\Models\Attempt;
use App\Models\AttemptQuestion;
use App\Services\AttemptService;
use App\Services\QuestionSelector;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Ujian PKSK')] class extends Component {
    #[Locked]
    public int $attemptId = 0;

    public int $position = 1;

    /** @var array<int, bool> keyed by position */
    #[Locked]
    public array $answered = [];

    /** @var array<int, bool> keyed by position */
    #[Locked]
    public array $flagged = [];

    #[Locked]
    public int $secondsRemaining = 0;

    public function mount(Attempt $attempt): void
    {
        abort_unless($attempt->user_id === Auth::id(), 403);

        if ($attempt->status !== AttemptStatus::InProgress) {
            $this->redirectRoute('ujian.keputusan', ['attempt' => $attempt->id], navigate: true);

            return;
        }

        $this->attemptId = $attempt->id;
        $this->syncState();
    }

    #[Computed]
    public function attempt(): Attempt
    {
        return Attempt::query()->findOrFail($this->attemptId);
    }

    /**
     * Options are loaded with an explicit column list. Selecting the whole model
     * would put `score` and `is_correct` — the entire answer key — into memory
     * where a stray dump or JS binding could leak it into the page source.
     */
    #[Computed]
    public function current(): AttemptQuestion
    {
        return $this->attempt->attemptQuestions()
            ->with([
                'question:id,section,subject,question_text',
                'question.options' => fn ($query) => $query
                    ->select('id', 'question_id', 'option_text')
                    ->orderBy('id'),
            ])
            ->where('position', $this->position)
            ->firstOrFail();
    }

    #[Computed]
    public function section(): Section
    {
        return $this->position <= QuestionSelector::SECTION_A_COUNT ? Section::A : Section::B;
    }

    #[Computed]
    public function answeredCount(): int
    {
        return count($this->answered);
    }

    public function jumpTo(int $position): void
    {
        $this->position = max(1, min(QuestionSelector::TOTAL, $position));

        unset($this->current);
    }

    public function next(): void
    {
        $this->jumpTo($this->position + 1);
    }

    public function previous(): void
    {
        $this->jumpTo($this->position - 1);
    }

    public function selectOption(int $optionId, AttemptService $attempts): void
    {
        try {
            $attempts->saveAnswer($this->attempt, $this->position, $optionId);
        } catch (AttemptExpiredException|AttemptNotActiveException) {
            $this->handleExpiry();

            return;
        } catch (InvalidOptionException) {
            Flux::toast(variant: 'danger', text: __('Pilihan tidak sah bagi soalan ini.'));

            return;
        }

        $this->answered[$this->position] = true;

        unset($this->current);

        $this->pushClock();
    }

    public function toggleFlag(AttemptService $attempts): void
    {
        try {
            $isFlagged = $attempts->toggleFlag($this->attempt, $this->position);
        } catch (AttemptExpiredException|AttemptNotActiveException) {
            $this->handleExpiry();

            return;
        }

        if ($isFlagged) {
            $this->flagged[$this->position] = true;
        } else {
            unset($this->flagged[$this->position]);
        }

        unset($this->current);

        $this->pushClock();
    }

    public function submit(AttemptService $attempts): void
    {
        $attempts->submit($this->attempt);

        $this->redirectRoute('ujian.keputusan', ['attempt' => $this->attemptId], navigate: true);
    }

    /**
     * Fired by the countdown when it reaches zero. The server re-checks the
     * deadline itself, so a tampered or throttled client cannot gain time.
     */
    public function autoSubmit(AttemptService $attempts): void
    {
        $this->submit($attempts);
    }

    private function handleExpiry(): void
    {
        Flux::toast(
            variant: 'warning',
            text: __('Masa ujian telah tamat. Jawapan anda telah dihantar.'),
        );

        $this->redirectRoute('ujian.keputusan', ['attempt' => $this->attemptId], navigate: true);
    }

    private function syncState(): void
    {
        $rows = $this->attempt->attemptQuestions()
            ->select('position', 'selected_option_id', 'is_flagged')
            ->get();

        $this->answered = [];
        $this->flagged = [];

        foreach ($rows as $row) {
            if ($row->selected_option_id !== null) {
                $this->answered[$row->position] = true;
            }

            if ($row->is_flagged) {
                $this->flagged[$row->position] = true;
            }
        }

        $this->secondsRemaining = $this->attempt->secondsRemaining();
    }

    private function pushClock(): void
    {
        $this->secondsRemaining = $this->attempt->secondsRemaining();

        $this->dispatch('masa-dikemaskini', seconds: $this->secondsRemaining);
    }
}; ?>

<section class="w-full p-4 lg:p-6">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <flux:heading size="lg">{{ __('Ujian PKSK Tahun 6 — Fasa 2') }}</flux:heading>
            <flux:text size="sm">
                {{ __('Dijawab: :dijawab / :jumlah', [
                    'dijawab' => $this->answeredCount,
                    'jumlah' => \App\Services\QuestionSelector::TOTAL,
                ]) }}
            </flux:text>
        </div>

        {{-- Display-only countdown. wire:ignore keeps Livewire's DOM morph from
             restarting the interval on every action; the server remains the
             authority on whether time has actually run out. --}}
        <div
            wire:ignore
            x-data="{
                remaining: @js($secondsRemaining),
                sent: false,
                tick: null,
                get label() {
                    const total = Math.max(0, this.remaining)
                    const h = String(Math.floor(total / 3600)).padStart(2, '0')
                    const m = String(Math.floor((total % 3600) / 60)).padStart(2, '0')
                    const s = String(total % 60).padStart(2, '0')
                    return h + ':' + m + ':' + s
                },
                get urgent() { return this.remaining <= 300 },
                init() {
                    this.tick = setInterval(() => {
                        if (this.remaining > 0) { this.remaining--; return }
                        clearInterval(this.tick)
                        if (this.sent) return
                        this.sent = true
                        $wire.autoSubmit()
                    }, 1000)
                },
            }"
            x-on:masa-dikemaskini.window="remaining = $event.detail.seconds"
            x-on:destroy="clearInterval(tick)"
            class="flex items-center gap-2"
        >
            <flux:icon icon="clock" class="size-5 text-zinc-500" />
            <span
                class="font-mono text-xl font-semibold tabular-nums"
                :class="urgent ? 'text-red-600 dark:text-red-400' : 'text-zinc-900 dark:text-zinc-100'"
                x-text="label"
            ></span>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-[1fr_320px]">
        {{-- Question --}}
        <flux:card class="space-y-5">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <flux:badge size="sm" color="zinc">
                        {{ __('Soalan :n', ['n' => $position]) }}
                    </flux:badge>
                    <flux:badge size="sm" :color="$this->section === \App\Enums\Section::A ? 'purple' : 'blue'">
                        {{ $this->section->label() }}
                    </flux:badge>
                    @if ($this->section === \App\Enums\Section::B && $this->current->question->subject)
                        <flux:badge size="sm" color="zinc">
                            {{ $this->current->question->subject->label() }}
                        </flux:badge>
                    @endif
                </div>

                <flux:button
                    size="sm"
                    :variant="isset($flagged[$position]) ? 'filled' : 'ghost'"
                    icon="flag"
                    wire:click="toggleFlag"
                >
                    {{ isset($flagged[$position]) ? __('Ditanda') : __('Tanda') }}
                </flux:button>
            </div>

            @if ($this->section === \App\Enums\Section::A)
                <flux:text size="sm" class="italic">
                    {{ __('Tiada jawapan betul atau salah. Pilih yang paling menggambarkan diri anda.') }}
                </flux:text>
            @endif

            <flux:heading size="lg" class="leading-relaxed">
                {{ $this->current->question->question_text }}
            </flux:heading>

            <div class="space-y-2">
                @foreach ($this->current->question->options as $option)
                    @php($dipilih = $this->current->selected_option_id === $option->id)

                    <button
                        type="button"
                        wire:key="opsyen-{{ $option->id }}"
                        wire:click="selectOption({{ $option->id }})"
                        wire:loading.attr="disabled"
                        @class([
                            'flex w-full items-start gap-3 rounded-lg border p-4 text-start transition',
                            'border-zinc-900 bg-zinc-900/5 dark:border-zinc-100 dark:bg-zinc-100/10' => $dipilih,
                            'border-zinc-200 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800' => ! $dipilih,
                        ])
                    >
                        <span @class([
                            'mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full border text-xs',
                            'border-zinc-900 bg-zinc-900 text-white dark:border-zinc-100 dark:bg-zinc-100 dark:text-zinc-900' => $dipilih,
                            'border-zinc-300 dark:border-zinc-600' => ! $dipilih,
                        ])>
                            {{ chr(65 + $loop->index) }}
                        </span>

                        <flux:text>{{ $option->option_text }}</flux:text>
                    </button>
                @endforeach
            </div>

            <flux:separator />

            <div class="flex items-center justify-between gap-3">
                <flux:button
                    size="sm"
                    icon="chevron-left"
                    wire:click="previous"
                    :disabled="$position <= 1"
                >
                    {{ __('Sebelum') }}
                </flux:button>

                <flux:modal.trigger name="sahkan-hantar">
                    <flux:button size="sm" variant="primary" icon="paper-airplane">
                        {{ __('Hantar Jawapan') }}
                    </flux:button>
                </flux:modal.trigger>

                <flux:button
                    size="sm"
                    icon-trailing="chevron-right"
                    wire:click="next"
                    :disabled="$position >= \App\Services\QuestionSelector::TOTAL"
                >
                    {{ __('Seterusnya') }}
                </flux:button>
            </div>
        </flux:card>

        {{-- Palette --}}
        <flux:card class="h-fit">
            <flux:heading size="sm">{{ __('Senarai Soalan') }}</flux:heading>

            <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-zinc-500">
                <span class="flex items-center gap-1.5">
                    <span class="size-3 rounded bg-emerald-600"></span>{{ __('Dijawab') }}
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="size-3 rounded bg-zinc-200 dark:bg-zinc-700"></span>{{ __('Belum dijawab') }}
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="size-3 rounded-full bg-amber-400"></span>{{ __('Ditanda') }}
                </span>
            </div>

            <div class="mt-4 grid grid-cols-8 gap-1.5 sm:grid-cols-10">
                @for ($i = 1; $i <= \App\Services\QuestionSelector::TOTAL; $i++)
                    @if ($i === \App\Services\QuestionSelector::SECTION_A_COUNT + 1)
                        <div class="col-span-full my-2 flex items-center gap-2">
                            <flux:separator class="flex-1" />
                            <flux:text size="sm">{{ __('Bahagian B') }}</flux:text>
                            <flux:separator class="flex-1" />
                        </div>
                    @endif

                    <button
                        type="button"
                        wire:key="palet-{{ $i }}"
                        wire:click="jumpTo({{ $i }})"
                        @class([
                            'relative h-9 rounded-md text-xs font-medium transition',
                            'ring-2 ring-offset-1 ring-zinc-900 dark:ring-zinc-100 dark:ring-offset-zinc-800' => $i === $position,
                            'bg-emerald-600 text-white hover:bg-emerald-700' => isset($answered[$i]),
                            'bg-zinc-200 text-zinc-700 hover:bg-zinc-300 dark:bg-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-600' => ! isset($answered[$i]),
                        ])
                    >
                        {{ $i }}

                        @if (isset($flagged[$i]))
                            <span class="absolute -right-0.5 -top-0.5 size-2 rounded-full bg-amber-400"></span>
                        @endif
                    </button>
                @endfor
            </div>
        </flux:card>
    </div>

    <flux:modal name="sahkan-hantar" class="max-w-md">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Hantar jawapan?') }}</flux:heading>

            <flux:text>
                {{ __('Anda telah menjawab :dijawab daripada :jumlah soalan. Jawapan tidak boleh diubah selepas dihantar.', [
                    'dijawab' => $this->answeredCount,
                    'jumlah' => \App\Services\QuestionSelector::TOTAL,
                ]) }}
            </flux:text>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Batal') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="primary" wire:click="submit" wire:loading.attr="disabled">
                    {{ __('Ya, Hantar') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</section>
