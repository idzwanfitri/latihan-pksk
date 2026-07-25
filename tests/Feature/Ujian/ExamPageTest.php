<?php

namespace Tests\Feature\Ujian;

use App\Enums\AttemptStatus;
use App\Enums\Section;
use App\Models\Attempt;
use App\Models\Option;
use App\Models\User;
use App\Services\AttemptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\SeedsQuestionBank;
use Tests\TestCase;

class ExamPageTest extends TestCase
{
    use RefreshDatabase, SeedsQuestionBank;

    private function pelajar(): User
    {
        return User::factory()->pelajar()->create();
    }

    private function start(User $user): Attempt
    {
        return app(AttemptService::class)->startOrResume($user);
    }

    public function test_a_pelajar_can_start_an_exam_from_the_start_page(): void
    {
        $this->seedQuestionBank();

        $this->actingAs($this->pelajar());

        Livewire::test('pages::ujian.mula')
            ->call('mula')
            ->assertRedirect();

        $this->assertDatabaseCount('attempts', 1);
        $this->assertDatabaseCount('attempt_questions', 100);
    }

    public function test_the_start_page_reports_an_insufficient_question_bank(): void
    {
        $this->seedQuestionBank(sectionA: 5, bahasaInggeris: 2, lain: 5);

        $this->actingAs($this->pelajar());

        Livewire::test('pages::ujian.mula')
            ->call('mula')
            ->assertSee('Bank soalan belum mencukupi', escape: false)
            ->assertNoRedirect();

        $this->assertDatabaseCount('attempts', 0);
    }

    public function test_selecting_an_option_saves_immediately_without_submitting(): void
    {
        $this->seedQuestionBank();

        $user = $this->pelajar();
        $attempt = $this->start($user);
        $this->actingAs($user);

        $target = $attempt->attemptQuestions()->where('position', 1)->firstOrFail();
        $optionId = (int) $target->question->options()->value('id');

        Livewire::test('pages::ujian.jawab', ['attempt' => $attempt])
            ->call('selectOption', $optionId);

        $this->assertDatabaseHas('attempt_questions', [
            'attempt_id' => $attempt->id,
            'position' => 1,
            'selected_option_id' => $optionId,
        ]);

        $this->assertSame(AttemptStatus::InProgress, $attempt->refresh()->status);
    }

    public function test_jump_to_moves_the_current_position_and_clamps_out_of_range_values(): void
    {
        $this->seedQuestionBank();

        $user = $this->pelajar();
        $attempt = $this->start($user);
        $this->actingAs($user);

        Livewire::test('pages::ujian.jawab', ['attempt' => $attempt])
            ->call('jumpTo', 57)
            ->assertSet('position', 57)
            ->call('jumpTo', 999)
            ->assertSet('position', 100)
            ->call('jumpTo', -5)
            ->assertSet('position', 1)
            ->call('next')
            ->assertSet('position', 2)
            ->call('previous')
            ->assertSet('position', 1);
    }

    public function test_toggling_a_flag_persists_and_toggles_back(): void
    {
        $this->seedQuestionBank();

        $user = $this->pelajar();
        $attempt = $this->start($user);
        $this->actingAs($user);

        $component = Livewire::test('pages::ujian.jawab', ['attempt' => $attempt])
            ->call('jumpTo', 4)
            ->call('toggleFlag');

        $this->assertTrue((bool) $attempt->attemptQuestions()->where('position', 4)->value('is_flagged'));

        $component->call('toggleFlag');

        $this->assertFalse((bool) $attempt->attemptQuestions()->where('position', 4)->value('is_flagged'));
    }

    public function test_an_option_belonging_to_a_different_question_is_rejected(): void
    {
        $this->seedQuestionBank();

        $user = $this->pelajar();
        $attempt = $this->start($user);
        $this->actingAs($user);

        $firstQuestionId = $attempt->attemptQuestions()->where('position', 1)->value('question_id');

        $foreignOptionId = (int) Option::query()
            ->where('question_id', '!=', $firstQuestionId)
            ->value('id');

        Livewire::test('pages::ujian.jawab', ['attempt' => $attempt])
            ->call('selectOption', $foreignOptionId);

        $this->assertNull($attempt->attemptQuestions()->where('position', 1)->value('selected_option_id'));
    }

    public function test_a_pelajar_cannot_open_another_pelajars_attempt(): void
    {
        $this->seedQuestionBank();

        $owner = $this->pelajar();
        $attempt = $this->start($owner);

        $this->actingAs($this->pelajar());

        $this->get(route('ujian.jawab', ['attempt' => $attempt->id]))->assertForbidden();
        $this->get(route('ujian.keputusan', ['attempt' => $attempt->id]))->assertForbidden();
    }

    public function test_section_a_option_scores_never_appear_in_the_exam_page_html(): void
    {
        // Distinctive scores that could not plausibly appear as page furniture.
        $this->seedQuestionBank(sectionA: 0, bahasaInggeris: 15, lain: 75);

        for ($i = 0; $i < 40; $i++) {
            $this->makeSectionAQuestion([9137, 9271, 9355, 9419]);
        }

        $user = $this->pelajar();
        $attempt = $this->start($user);
        $this->actingAs($user);

        $response = $this->get(route('ujian.jawab', ['attempt' => $attempt->id]));

        $response->assertOk();
        $response->assertDontSee('9137');
        $response->assertDontSee('9271');
        $response->assertDontSee('9355');
        $response->assertDontSee('9419');
    }

    public function test_the_exam_page_redirects_to_the_result_once_the_attempt_is_finalised(): void
    {
        $this->seedQuestionBank();

        $user = $this->pelajar();
        $attempt = $this->start($user);
        app(AttemptService::class)->submit($attempt);

        $this->actingAs($user);

        $this->get(route('ujian.jawab', ['attempt' => $attempt->id]))
            ->assertRedirect(route('ujian.keputusan', ['attempt' => $attempt->id]));
    }

    public function test_the_result_page_redirects_back_while_the_attempt_is_still_active(): void
    {
        $this->seedQuestionBank();

        $user = $this->pelajar();
        $attempt = $this->start($user);

        $this->actingAs($user);

        $this->get(route('ujian.keputusan', ['attempt' => $attempt->id]))
            ->assertRedirect(route('ujian.jawab', ['attempt' => $attempt->id]));
    }

    public function test_the_result_page_never_shows_section_a_question_text(): void
    {
        $this->seedQuestionBank();

        $user = $this->pelajar();
        $attempt = $this->start($user);
        app(AttemptService::class)->submit($attempt);

        $this->actingAs($user);

        $response = $this->get(route('ujian.keputusan', ['attempt' => $attempt->id]));
        $response->assertOk();

        $sectionAQuestions = $attempt->attemptQuestions()
            ->with('question')
            ->get()
            ->filter(fn ($row): bool => $row->question->section === Section::A);

        $this->assertCount(30, $sectionAQuestions);

        foreach ($sectionAQuestions as $row) {
            $response->assertDontSeeText($row->question->question_text);
        }
    }

    public function test_the_result_page_shows_the_section_b_subject_breakdown(): void
    {
        $this->seedQuestionBank();

        $user = $this->pelajar();
        $attempt = $this->start($user);
        app(AttemptService::class)->submit($attempt);

        $this->actingAs($user);

        $this->get(route('ujian.keputusan', ['attempt' => $attempt->id]))
            ->assertOk()
            ->assertSeeText('Pecahan Bahagian B Mengikut Subjek')
            ->assertSeeText('Bahasa Inggeris');
    }

    public function test_the_result_page_finalises_an_attempt_that_lapsed_without_being_submitted(): void
    {
        $this->seedQuestionBank();

        $user = $this->pelajar();
        $attempt = $this->start($user);

        $this->travelTo($attempt->expires_at->addMinute());
        $this->actingAs($user);

        $this->get(route('ujian.keputusan', ['attempt' => $attempt->id]))->assertOk();

        $this->assertSame(AttemptStatus::Expired, $attempt->refresh()->status);
        $this->assertNotNull($attempt->score_total);
    }

    public function test_submitting_from_the_exam_page_scores_and_redirects(): void
    {
        $this->seedQuestionBank();

        $user = $this->pelajar();
        $attempt = $this->start($user);
        $this->actingAs($user);

        Livewire::test('pages::ujian.jawab', ['attempt' => $attempt])
            ->call('submit')
            ->assertRedirect(route('ujian.keputusan', ['attempt' => $attempt->id]));

        $attempt->refresh();

        $this->assertSame(AttemptStatus::Submitted, $attempt->status);
        $this->assertNotNull($attempt->score_total);
    }

    public function test_a_fully_answered_attempt_stays_within_the_score_ceilings(): void
    {
        $this->seedQuestionBank();

        $user = $this->pelajar();
        $attempt = $this->start($user);

        foreach ($attempt->attemptQuestions()->with('question.options')->get() as $row) {
            $optionId = (int) $row->question->options->first()->id;
            app(AttemptService::class)->saveAnswer($attempt, $row->position, $optionId);
        }

        app(AttemptService::class)->submit($attempt);
        $attempt->refresh();

        $this->assertLessThanOrEqual(20.0, $attempt->score_a);
        $this->assertLessThanOrEqual(70.0, $attempt->score_b);
        $this->assertEqualsWithDelta($attempt->score_a + $attempt->score_b, $attempt->score_total, 0.001);
    }

    public function test_the_exam_page_renders_the_full_hundred_question_palette(): void
    {
        $this->seedQuestionBank();

        $user = $this->pelajar();
        $attempt = $this->start($user);
        $this->actingAs($user);

        $response = $this->get(route('ujian.jawab', ['attempt' => $attempt->id]));

        $response->assertOk();

        foreach ([1, 30, 31, 100] as $position) {
            $response->assertSee('jumpTo('.$position.')', escape: false);
        }
    }

    public function test_an_admin_cannot_reach_the_exam_pages(): void
    {
        $this->seedQuestionBank();

        $this->actingAs(User::factory()->admin()->create());

        $this->get(route('ujian.mula'))->assertForbidden();
    }
}
