<?php

use App\Models\Attempt;
use App\Models\User;
use App\Services\PelajarProgress;
use App\Services\QuestionBankReport;
use App\Services\QuestionSelector;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Papan Pemuka')] class extends Component {
    #[Computed]
    public function pengguna(): User
    {
        return Auth::user();
    }

    // --- Pelajar ---------------------------------------------------------

    #[Computed]
    public function percubaanAktif(): ?Attempt
    {
        return app(PelajarProgress::class)->activeAttempt($this->pengguna);
    }

    #[Computed]
    public function keputusanTerakhir(): ?Attempt
    {
        return app(PelajarProgress::class)->latestResult($this->pengguna);
    }

    /**
     * @return Collection<int, Attempt>
     */
    #[Computed]
    public function keputusanTerkini(): Collection
    {
        return app(PelajarProgress::class)->recentResults($this->pengguna);
    }

    #[Computed]
    public function bilanganPercubaan(): int
    {
        return app(PelajarProgress::class)->attemptCount($this->pengguna);
    }

    #[Computed]
    public function skorTerbaik(): ?float
    {
        return app(PelajarProgress::class)->bestTotal($this->pengguna);
    }

    // --- Admin -----------------------------------------------------------

    /**
     * @return array<int, array{label: string, available: int, required: int, ok: bool}>
     */
    #[Computed]
    public function kesediaan(): array
    {
        return app(QuestionBankReport::class)->health();
    }

    #[Computed]
    public function bankSedia(): bool
    {
        return app(QuestionBankReport::class)->isReady();
    }

    /**
     * @return array<int, array{subject: string, count: int, percent: float}>
     */
    #[Computed]
    public function taburan(): array
    {
        return app(QuestionBankReport::class)->subjectSpread();
    }

    /**
     * @return array{total: int, active: int, inactive: int, section_a: int, section_b: int, attempts: int}
     */
    #[Computed]
    public function kiraan(): array
    {
        return app(QuestionBankReport::class)->counts();
    }
}; ?>

<section class="w-full p-6">
    <flux:heading size="xl">
        {{ __('Selamat kembali, :nama', ['nama' => $this->pengguna->name]) }}
    </flux:heading>

    @if ($this->pengguna->isAdmin())
        {{-- ================= ADMIN ================= --}}
        <flux:subheading class="mt-1">{{ __('Ringkasan bank soalan PKSK.') }}</flux:subheading>

        @unless ($this->bankSedia)
            <flux:callout variant="danger" icon="exclamation-triangle" class="mt-6">
                {{ __('Bank soalan belum mencukupi untuk satu kertas penuh. Pelajar tidak boleh memulakan ujian sehingga setiap kategori di bawah mencukupi.') }}
            </flux:callout>
        @endunless

        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <flux:card>
                <flux:text size="sm">{{ __('Jumlah soalan') }}</flux:text>
                <flux:heading size="xl" class="mt-1">{{ $this->kiraan['total'] }}</flux:heading>
                <flux:text size="sm" class="mt-1">
                    {{ __(':a Bahagian A · :b Bahagian B', [
                        'a' => $this->kiraan['section_a'],
                        'b' => $this->kiraan['section_b'],
                    ]) }}
                </flux:text>
            </flux:card>

            <flux:card>
                <flux:text size="sm">{{ __('Aktif') }}</flux:text>
                <flux:heading size="xl" class="mt-1">{{ $this->kiraan['active'] }}</flux:heading>
                <flux:text size="sm" class="mt-1">{{ __('Boleh dipilih dalam ujian') }}</flux:text>
            </flux:card>

            <flux:card>
                <flux:text size="sm">{{ __('Tidak aktif') }}</flux:text>
                <flux:heading size="xl" class="mt-1">{{ $this->kiraan['inactive'] }}</flux:heading>
                <flux:text size="sm" class="mt-1">{{ __('Menunggu semakan') }}</flux:text>
            </flux:card>

            <flux:card>
                <flux:text size="sm">{{ __('Percubaan pelajar') }}</flux:text>
                <flux:heading size="xl" class="mt-1">{{ $this->kiraan['attempts'] }}</flux:heading>
                <flux:text size="sm" class="mt-1">{{ __('Sepanjang masa') }}</flux:text>
            </flux:card>
        </div>

        <flux:card class="mt-6">
            <flux:heading size="lg">{{ __('Kesediaan bank soalan') }}</flux:heading>
            <flux:text size="sm" class="mt-1">
                {{ __('Setiap percubaan menarik :jumlah soalan daripada kolam aktif.', ['jumlah' => QuestionSelector::TOTAL]) }}
            </flux:text>

            <div class="mt-4 space-y-4">
                @foreach ($this->kesediaan as $baris)
                    <div wire:key="sedia-{{ $loop->index }}">
                        <div class="flex items-center justify-between gap-3">
                            <flux:text>{{ $baris['label'] }}</flux:text>
                            <div class="flex items-center gap-2">
                                <flux:text size="sm">
                                    {{ $baris['available'] }} / {{ $baris['required'] }}
                                </flux:text>
                                <flux:badge size="sm" :color="$baris['ok'] ? 'green' : 'red'">
                                    {{ $baris['ok'] ? __('Cukup') : __('Tidak cukup') }}
                                </flux:badge>
                            </div>
                        </div>

                        <flux:progress
                            class="mt-2"
                            :value="min($baris['available'], $baris['required'])"
                            :max="$baris['required']"
                        />
                    </div>
                @endforeach
            </div>
        </flux:card>

        <flux:card class="mt-6">
            <flux:heading size="lg">{{ __('Taburan subjek Bahagian B') }}</flux:heading>
            <flux:text size="sm" class="mt-1">
                {{ __('60 soalan bukan-Bahasa Inggeris ditarik bercampur, jadi taburan bank menentukan taburan kertas.') }}
            </flux:text>

            <flux:table class="mt-4">
                <flux:table.columns>
                    <flux:table.column>{{ __('Subjek') }}</flux:table.column>
                    <flux:table.column>{{ __('Soalan aktif') }}</flux:table.column>
                    <flux:table.column>{{ __('Peratus') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($this->taburan as $baris)
                        <flux:table.row wire:key="taburan-{{ $loop->index }}">
                            <flux:table.cell>{{ $baris['subject'] }}</flux:table.cell>
                            <flux:table.cell>{{ $baris['count'] }}</flux:table.cell>
                            <flux:table.cell>{{ number_format($baris['percent'], 1) }}%</flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </flux:card>

        <div class="mt-6 flex flex-wrap gap-3">
            <flux:button variant="primary" icon="clipboard-document-list" :href="route('admin.soalan-a')" wire:navigate>
                {{ __('Urus Soalan Bahagian A') }}
            </flux:button>

            <flux:button icon="academic-cap" :href="route('admin.soalan-b')" wire:navigate>
                {{ __('Urus Soalan Bahagian B') }}
            </flux:button>
        </div>
    @else
        {{-- ================= PELAJAR ================= --}}
        <flux:subheading class="mt-1">{{ __('Ringkasan latihan PKSK anda.') }}</flux:subheading>

        @if ($this->percubaanAktif)
            <flux:callout variant="warning" icon="clock" class="mt-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <span>
                        {{ __('Anda mempunyai ujian yang belum selesai. Baki masa: :masa.', [
                            'masa' => gmdate('H:i:s', $this->percubaanAktif->secondsRemaining()),
                        ]) }}
                    </span>

                    <flux:button
                        size="sm"
                        variant="primary"
                        icon="play"
                        :href="route('ujian.jawab', ['attempt' => $this->percubaanAktif->id])"
                        wire:navigate
                    >
                        {{ __('Sambung Ujian') }}
                    </flux:button>
                </div>
            </flux:callout>
        @endif

        <div class="mt-6 grid gap-4 sm:grid-cols-3">
            <flux:card>
                <flux:text size="sm">{{ __('Ujian selesai') }}</flux:text>
                <flux:heading size="xl" class="mt-1">{{ $this->bilanganPercubaan }}</flux:heading>
            </flux:card>

            <flux:card>
                <flux:text size="sm">{{ __('Skor terbaik') }}</flux:text>
                <flux:heading size="xl" class="mt-1">
                    @if ($this->skorTerbaik === null)
                        —
                    @else
                        {{ number_format($this->skorTerbaik, 2) }}
                        <span class="text-base font-normal text-zinc-500">/ 90</span>
                    @endif
                </flux:heading>
            </flux:card>

            <flux:card>
                <flux:text size="sm">{{ __('Skor terakhir') }}</flux:text>
                <flux:heading size="xl" class="mt-1">
                    @if ($this->keputusanTerakhir === null)
                        —
                    @else
                        {{ number_format($this->keputusanTerakhir->score_total ?? 0, 2) }}
                        <span class="text-base font-normal text-zinc-500">/ 90</span>
                    @endif
                </flux:heading>
            </flux:card>
        </div>

        @if ($this->keputusanTerakhir)
            <flux:card class="mt-6">
                <flux:heading size="lg">{{ __('Keputusan Terkini') }}</flux:heading>

                <flux:table class="mt-4">
                    <flux:table.columns>
                        <flux:table.column>{{ __('Tarikh') }}</flux:table.column>
                        <flux:table.column>{{ __('Status') }}</flux:table.column>
                        <flux:table.column>{{ __('Bahagian A') }}</flux:table.column>
                        <flux:table.column>{{ __('Bahagian B') }}</flux:table.column>
                        <flux:table.column>{{ __('Jumlah') }}</flux:table.column>
                        <flux:table.column></flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($this->keputusanTerkini as $percubaan)
                            <flux:table.row :key="$percubaan->id">
                                <flux:table.cell>{{ $percubaan->started_at->format('d/m/Y H:i') }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge size="sm" :color="$percubaan->status->badgeColor()">
                                        {{ $percubaan->status->label() }}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell>{{ number_format($percubaan->score_a ?? 0, 2) }}</flux:table.cell>
                                <flux:table.cell>{{ number_format($percubaan->score_b ?? 0, 2) }}</flux:table.cell>
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
        @else
            <flux:callout icon="information-circle" class="mt-6">
                {{ __('Anda belum menduduki sebarang ujian. Mulakan ujian pertama anda untuk melihat keputusan di sini.') }}
            </flux:callout>
        @endif

        <div class="mt-6">
            <flux:button variant="primary" icon="pencil-square" :href="route('ujian.mula')" wire:navigate>
                {{ $this->percubaanAktif ? __('Ke Halaman Ujian') : __('Mula Ujian') }}
            </flux:button>
        </div>
    @endif
</section>
