<?php

namespace Tests\Feature;

use App\Enums\Subject;
use App\Models\User;
use App\Services\AttemptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsQuestionBank;
use Tests\TestCase;

class PapanPemukaTest extends TestCase
{
    use RefreshDatabase, SeedsQuestionBank;

    public function test_it_greets_the_signed_in_user_by_name(): void
    {
        $this->actingAs(User::factory()->pelajar()->create(['name' => 'Aiman Hakim']));

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSeeText('Selamat kembali, Aiman Hakim');
    }

    public function test_the_starter_kit_placeholder_boxes_are_gone(): void
    {
        $this->actingAs(User::factory()->pelajar()->create());

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('placeholder-pattern');
    }

    public function test_the_sidebar_no_longer_links_to_the_starter_kit_repository_or_docs(): void
    {
        $this->actingAs(User::factory()->pelajar()->create());

        $response = $this->get(route('dashboard'));

        $response->assertDontSeeText('Repository');
        $response->assertDontSeeText('Documentation');
        $response->assertDontSee('github.com/laravel/livewire-starter-kit');
        $response->assertDontSee('laravel.com/docs/starter-kits');
    }

    // --- Pelajar ---------------------------------------------------------

    public function test_a_pelajar_with_no_history_sees_an_empty_state(): void
    {
        $this->actingAs(User::factory()->pelajar()->create());

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSeeText('Anda belum menduduki sebarang ujian.')
            ->assertSeeText('Mula Ujian');
    }

    public function test_a_pelajar_sees_their_score_summary_after_submitting(): void
    {
        $this->seedQuestionBank();

        $user = User::factory()->pelajar()->create();
        $attempt = app(AttemptService::class)->startOrResume($user);
        app(AttemptService::class)->submit($attempt);

        $this->actingAs($user);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSeeText('Ujian selesai')
            ->assertSeeText('Skor terbaik')
            ->assertSeeText('Keputusan Terkini')
            ->assertDontSeeText('Anda belum menduduki sebarang ujian.');
    }

    public function test_a_pelajar_with_a_live_attempt_is_offered_a_resume_link(): void
    {
        $this->seedQuestionBank();

        $user = User::factory()->pelajar()->create();
        $attempt = app(AttemptService::class)->startOrResume($user);

        $this->actingAs($user);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSeeText('Sambung Ujian')
            ->assertSee(route('ujian.jawab', ['attempt' => $attempt->id]), escape: false);
    }

    public function test_an_expired_attempt_is_not_offered_for_resuming(): void
    {
        $this->seedQuestionBank();

        $user = User::factory()->pelajar()->create();
        $attempt = app(AttemptService::class)->startOrResume($user);

        $this->travelTo($attempt->expires_at->addMinute());
        $this->actingAs($user);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertDontSeeText('Sambung Ujian');
    }

    public function test_a_pelajar_never_sees_the_admin_panels(): void
    {
        $this->seedQuestionBank();

        $this->actingAs(User::factory()->pelajar()->create());

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertDontSeeText('Kesediaan bank soalan')
            ->assertDontSeeText('Taburan subjek Bahagian B')
            ->assertDontSeeText('Urus Soalan Bahagian A');
    }

    // --- Admin -----------------------------------------------------------

    public function test_an_admin_sees_the_bank_health_and_subject_spread(): void
    {
        $this->seedQuestionBank();

        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSeeText('Kesediaan bank soalan')
            ->assertSeeText('Taburan subjek Bahagian B')
            ->assertSeeText('Bahagian B (Bahasa Inggeris)')
            ->assertSeeText('Urus Soalan Bahagian A');
    }

    public function test_an_admin_sees_question_counts(): void
    {
        $this->seedQuestionBank(sectionA: 40, bahasaInggeris: 15, lain: 75);

        $this->actingAs(User::factory()->admin()->create());

        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertSeeText('Jumlah soalan');
        $response->assertSeeText('130');   // 40 + 15 + 75
        $response->assertSeeText('Tidak aktif');
    }

    public function test_an_admin_is_warned_when_the_bank_cannot_fill_a_paper(): void
    {
        $this->seedQuestionBank(sectionA: 5, bahasaInggeris: 2, lain: 5);

        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSeeText('Bank soalan belum mencukupi untuk satu kertas penuh.')
            ->assertSeeText('Tidak cukup');
    }

    public function test_an_admin_is_not_warned_when_the_bank_is_ready(): void
    {
        $this->seedQuestionBank();

        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertDontSeeText('Bank soalan belum mencukupi untuk satu kertas penuh.')
            ->assertSeeText('Cukup');
    }

    public function test_inactive_questions_are_excluded_from_the_readiness_check(): void
    {
        // Exactly the quota, but every English question is inactive.
        $this->seedQuestionBank(sectionA: 30, bahasaInggeris: 0, lain: 60);

        for ($i = 0; $i < 10; $i++) {
            $this->makeSectionBQuestion(Subject::BahasaInggeris, isActive: false);
        }

        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSeeText('Bank soalan belum mencukupi untuk satu kertas penuh.');
    }

    public function test_an_admin_never_sees_the_student_panels(): void
    {
        $this->seedQuestionBank();

        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertDontSeeText('Skor terbaik')
            ->assertDontSeeText('Anda belum menduduki sebarang ujian.');
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }
}
