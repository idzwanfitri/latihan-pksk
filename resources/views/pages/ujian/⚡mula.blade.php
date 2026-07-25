<?php

use App\Enums\AttemptStatus;
use App\Exceptions\InsufficientQuestionBankException;
use App\Models\Attempt;
use App\Models\User;
use App\Services\AttemptService;
use App\Services\QuestionSelector;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Ujian PKSK')] class extends Component {
    public string $ralat = '';

    #[Computed]
    public function pengguna(): User
    {
        return Auth::user();
    }

    #[Computed]
    public function percubaanAktif(): ?Attempt
    {
        return app(AttemptService::class)->activeAttempt($this->pengguna);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Attempt>
     */
    #[Computed]
    public function percubaanLepas()
    {
        return $this->pengguna->attempts()
            ->whereIn('status', [AttemptStatus::Submitted, AttemptStatus::Expired])
            ->latest('id')
            ->limit(5)
            ->get();
    }

    public function mula(AttemptService $attempts): void
    {
        $this->ralat = '';

        try {
            $attempt = $attempts->startOrResume($this->pengguna);
        } catch (InsufficientQuestionBankException) {
            $this->ralat = __('Bank soalan belum mencukupi untuk memulakan ujian. Sila hubungi pentadbir.');

            return;
        }

        $this->redirectRoute('ujian.jawab', ['attempt' => $attempt->id], navigate: true);
    }
}; ?>

<section class="w-full max-w-3xl p-6">
    <flux:heading size="xl">{{ __('Ujian PKSK Tahun 6 — Fasa 2') }}</flux:heading>
    <flux:subheading class="mt-1">
        {{ __('Simulasi peperiksaan penuh mengikut format sebenar.') }}
    </flux:subheading>

    @if ($ralat !== '')
        <flux:callout variant="danger" icon="exclamation-triangle" class="mt-6">
            {{ $ralat }}
        </flux:callout>
    @endif

    <flux:card class="mt-6 space-y-4">
        <flux:heading size="lg">{{ __('Arahan') }}</flux:heading>

        <ul class="list-inside list-disc space-y-2 text-sm text-zinc-600 dark:text-zinc-300">
            <li>{{ __('Ujian ini mengandungi :jumlah soalan dalam satu sesi.', ['jumlah' => QuestionSelector::TOTAL]) }}</li>
            <li>{{ __('Bahagian A — Kecerdasan Insaniah: :bil soalan.', ['bil' => QuestionSelector::SECTION_A_COUNT]) }}</li>
            <li>{{ __('Bahagian B — Kecerdasan Intelek: :bil soalan.', ['bil' => QuestionSelector::SECTION_B_COUNT]) }}</li>
            <li>{{ __('Masa yang diperuntukkan ialah :minit minit untuk keseluruhan ujian.', ['minit' => AttemptService::DURATION_MINUTES]) }}</li>
            <li>{{ __('Anda bebas melangkau dan kembali kepada mana-mana soalan.') }}</li>
            <li>{{ __('Setiap jawapan disimpan secara automatik sebaik sahaja anda memilihnya.') }}</li>
            <li>{{ __('Bahagian A tiada jawapan betul atau salah — pilih yang paling menggambarkan diri anda.') }}</li>
            <li>{{ __('Ujian akan dihantar secara automatik apabila masa tamat.') }}</li>
        </ul>
    </flux:card>

    <div class="mt-6">
        @if ($this->percubaanAktif)
            <flux:callout variant="warning" icon="clock" class="mb-4">
                {{ __('Anda mempunyai ujian yang belum selesai. Baki masa: :masa.', [
                    'masa' => gmdate('H:i:s', $this->percubaanAktif->secondsRemaining()),
                ]) }}
            </flux:callout>

            <flux:button variant="primary" icon="play" wire:click="mula" wire:loading.attr="disabled">
                {{ __('Sambung Ujian') }}
            </flux:button>
        @else
            <flux:button variant="primary" icon="play" wire:click="mula" wire:loading.attr="disabled">
                {{ __('Mula Ujian') }}
            </flux:button>
        @endif
    </div>

    @if ($this->percubaanLepas->isNotEmpty())
        <flux:card class="mt-8">
            <flux:heading size="lg">{{ __('Keputusan Lepas') }}</flux:heading>

            <flux:table class="mt-4">
                <flux:table.columns>
                    <flux:table.column>{{ __('Tarikh') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                    <flux:table.column>{{ __('Jumlah') }}</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->percubaanLepas as $percubaan)
                        <flux:table.row :key="$percubaan->id">
                            <flux:table.cell>{{ $percubaan->started_at->format('d/m/Y H:i') }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" :color="$percubaan->status->badgeColor()">
                                    {{ $percubaan->status->label() }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>{{ number_format($percubaan->score_total ?? 0, 2) }} / 90</flux:table.cell>
                            <flux:table.cell>
                                <flux:link :href="route('ujian.keputusan', ['attempt' => $percubaan->id])" wire:navigate>
                                    {{ __('Lihat') }}
                                </flux:link>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </flux:card>
    @endif
</section>
