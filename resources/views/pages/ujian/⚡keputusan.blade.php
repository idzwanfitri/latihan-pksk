<?php

use App\Enums\AttemptStatus;
use App\Enums\Section;
use App\Models\Attempt;
use App\Services\AttemptService;
use App\Services\QuestionSelector;
use App\Services\ScoringService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Keputusan Ujian')] class extends Component {
    #[Locked]
    public int $attemptId = 0;

    public function mount(Attempt $attempt, AttemptService $attempts): void
    {
        abort_unless($attempt->user_id === Auth::id(), 403);

        // No peeking at a score while the clock is still running.
        if ($attempt->isActive()) {
            $this->redirectRoute('ujian.jawab', ['attempt' => $attempt->id], navigate: true);

            return;
        }

        // Someone deep-linking here after a crash finalises the stale attempt.
        if ($attempt->status === AttemptStatus::InProgress) {
            $attempts->expire($attempt);
        }

        $this->attemptId = $attempt->id;
    }

    #[Computed]
    public function attempt(): Attempt
    {
        return Attempt::query()->findOrFail($this->attemptId);
    }

    /**
     * @return array<string, array{subject: \App\Enums\Subject, correct: int, total: int, percent: float}>
     */
    #[Computed]
    public function pecahan(): array
    {
        return app(ScoringService::class)->sectionBBreakdown($this->attempt);
    }

    /**
     * Bahagian B only. Filtering in the query rather than the template means no
     * template mistake can ever reveal a Bahagian A score, which has no right
     * or wrong answer to reveal in the first place.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\AttemptQuestion>
     */
    #[Computed]
    public function semakanB()
    {
        return $this->attempt->attemptQuestions()
            ->whereHas('question', fn (Builder $query) => $query->where('section', Section::B))
            ->with(['question', 'question.options', 'selectedOption'])
            ->orderBy('position')
            ->get();
    }

    #[Computed]
    public function dijawabBahagianA(): int
    {
        return $this->attempt->attemptQuestions()
            ->whereHas('question', fn (Builder $query) => $query->where('section', Section::A))
            ->whereNotNull('selected_option_id')
            ->count();
    }
}; ?>

<section class="w-full max-w-4xl p-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <flux:heading size="xl">{{ __('Keputusan Ujian') }}</flux:heading>
            <flux:subheading class="mt-1">
                {{ $this->attempt->started_at->format('d/m/Y, H:i') }}
            </flux:subheading>
        </div>

        <flux:badge :color="$this->attempt->status->badgeColor()">
            {{ $this->attempt->status->label() }}
        </flux:badge>
    </div>

    <div class="mt-6 grid gap-4 md:grid-cols-3">
        <flux:card>
            <flux:text size="sm">{{ __('Bahagian A') }}</flux:text>
            <flux:heading size="xl" class="mt-1">
                {{ number_format($this->attempt->score_a ?? 0, 2) }}
                <span class="text-base font-normal text-zinc-500">/ 20</span>
            </flux:heading>
            <flux:progress class="mt-3" :value="$this->attempt->score_a ?? 0" max="20" />
            <flux:text size="sm" class="mt-2">{{ __('Kecerdasan Insaniah') }}</flux:text>
        </flux:card>

        <flux:card>
            <flux:text size="sm">{{ __('Bahagian B') }}</flux:text>
            <flux:heading size="xl" class="mt-1">
                {{ number_format($this->attempt->score_b ?? 0, 2) }}
                <span class="text-base font-normal text-zinc-500">/ 70</span>
            </flux:heading>
            <flux:progress class="mt-3" :value="$this->attempt->score_b ?? 0" max="70" />
            <flux:text size="sm" class="mt-2">{{ __('Kecerdasan Intelek') }}</flux:text>
        </flux:card>

        <flux:card>
            <flux:text size="sm">{{ __('Jumlah') }}</flux:text>
            <flux:heading size="xl" class="mt-1">
                {{ number_format($this->attempt->score_total ?? 0, 2) }}
                <span class="text-base font-normal text-zinc-500">/ 90</span>
            </flux:heading>
            <flux:progress class="mt-3" :value="$this->attempt->score_total ?? 0" max="90" />
            <flux:text size="sm" class="mt-2">{{ __('Fasa 2 keseluruhan') }}</flux:text>
        </flux:card>
    </div>

    <flux:card class="mt-6">
        <flux:heading size="lg">{{ __('Bahagian A — Kecerdasan Insaniah') }}</flux:heading>
        <flux:text class="mt-2">
            {{ __('Anda menjawab :dijawab daripada :jumlah soalan.', [
                'dijawab' => $this->dijawabBahagianA,
                'jumlah' => QuestionSelector::SECTION_A_COUNT,
            ]) }}
        </flux:text>
        <flux:text size="sm" class="mt-2 italic">
            {{ __('Bahagian ini tiada jawapan betul atau salah. Skor mencerminkan kecenderungan sikap dan nilai diri anda.') }}
        </flux:text>
    </flux:card>

    <flux:card class="mt-6">
        <flux:heading size="lg">{{ __('Pecahan Bahagian B Mengikut Subjek') }}</flux:heading>

        <flux:table class="mt-4">
            <flux:table.columns>
                <flux:table.column>{{ __('Subjek') }}</flux:table.column>
                <flux:table.column>{{ __('Betul') }}</flux:table.column>
                <flux:table.column>{{ __('Jumlah') }}</flux:table.column>
                <flux:table.column>{{ __('Peratus') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->pecahan as $kunci => $baris)
                    <flux:table.row :key="$kunci">
                        <flux:table.cell>{{ $baris['subject']->label() }}</flux:table.cell>
                        <flux:table.cell>{{ $baris['correct'] }}</flux:table.cell>
                        <flux:table.cell>{{ $baris['total'] }}</flux:table.cell>
                        <flux:table.cell>{{ number_format($baris['percent'], 1) }}%</flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </flux:card>

    <flux:card class="mt-6">
        <flux:heading size="lg">{{ __('Semakan Bahagian B') }}</flux:heading>

        <div class="mt-4 space-y-4">
            @foreach ($this->semakanB as $baris)
                @php($pilihan = $baris->selectedOption)
                @php($betul = $baris->question->options->firstWhere('is_correct', true))
                @php($adalahBetul = $pilihan !== null && $pilihan->is_correct)

                <div wire:key="semak-{{ $baris->id }}" class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <div class="flex flex-wrap items-center gap-2">
                        <flux:badge size="sm" color="zinc">{{ __('Soalan :n', ['n' => $baris->position]) }}</flux:badge>

                        @if ($baris->question->subject)
                            <flux:badge size="sm" color="zinc">{{ $baris->question->subject->label() }}</flux:badge>
                        @endif

                        @if ($pilihan === null)
                            <flux:badge size="sm" color="amber">{{ __('Tidak dijawab') }}</flux:badge>
                        @elseif ($adalahBetul)
                            <flux:badge size="sm" color="green">{{ __('Betul') }}</flux:badge>
                        @else
                            <flux:badge size="sm" color="red">{{ __('Salah') }}</flux:badge>
                        @endif
                    </div>

                    <flux:text class="mt-3 font-medium">{{ $baris->question->question_text }}</flux:text>

                    <div class="mt-2 space-y-1 text-sm">
                        <div>
                            <span class="text-zinc-500">{{ __('Jawapan anda:') }}</span>
                            <span class="{{ $adalahBetul ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $pilihan?->option_text ?? __('—') }}
                            </span>
                        </div>

                        @unless ($adalahBetul)
                            <div>
                                <span class="text-zinc-500">{{ __('Jawapan betul:') }}</span>
                                <span class="text-emerald-600 dark:text-emerald-400">{{ $betul?->option_text }}</span>
                            </div>
                        @endunless
                    </div>
                </div>
            @endforeach
        </div>
    </flux:card>

    <div class="mt-6 flex flex-wrap gap-3">
        <flux:button :href="route('dashboard')" wire:navigate icon="home">
            {{ __('Kembali ke Papan Pemuka') }}
        </flux:button>

        <flux:button variant="primary" :href="route('ujian.mula')" wire:navigate icon="play">
            {{ __('Mula Ujian Baharu') }}
        </flux:button>
    </div>
</section>
