<?php

namespace Tests\Feature\Admin;

use App\Enums\Section;
use App\Enums\Subject;
use App\Models\Option;
use App\Models\Question;
use App\Models\User;
use App\Services\AttemptService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\Concerns\SeedsQuestionBank;
use Tests\TestCase;

class SoalanBTest extends TestCase
{
    use RefreshDatabase, SeedsQuestionBank;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->admin()->create());
    }

    /**
     * @param  array<int, string>  $options
     */
    private function fillForm(
        array $options = ['Empat belas', 'Tiga puluh dua', 'Dua puluh', 'Dua puluh lapan'],
        ?int $correctIndex = 1,
        string $subject = 'iq',
        string $text = 'Lengkapkan siri nombor berikut: 2, 4, 8, 16, ___',
    ): Testable {
        $component = Livewire::test('pages::admin.soalan-b')
            ->call('create')
            ->set('subjectValue', $subject)
            ->set('questionText', $text);

        foreach ($options as $index => $option) {
            $component->set("options.{$index}.option_text", $option);
        }

        return $component->set('correctIndex', $correctIndex);
    }

    public function test_the_admin_page_loads(): void
    {
        $this->get(route('admin.soalan-b'))->assertOk();
    }

    public function test_an_admin_can_create_a_section_b_question_with_exactly_one_correct_option(): void
    {
        $this->fillForm()->call('save')->assertHasNoErrors();

        $question = Question::query()->firstOrFail();

        $this->assertSame(Section::B, $question->section);
        $this->assertSame(Subject::Iq, $question->subject);
        $this->assertSame(4, $question->options()->count());

        // The whole point of the radio group: exactly one, never zero, never two.
        $this->assertSame(1, $question->options()->where('is_correct', true)->count());

        $correct = $question->options()->where('is_correct', true)->firstOrFail();
        $this->assertSame('Tiga puluh dua', $correct->option_text);

        // Section B options carry no score.
        $this->assertSame(0, $question->options()->whereNotNull('score')->count());
    }

    public function test_the_subject_is_required(): void
    {
        $this->fillForm(subject: '')->call('save')->assertHasErrors('subjectValue');

        $this->assertDatabaseCount('questions', 0);
    }

    public function test_a_correct_answer_must_be_chosen(): void
    {
        $this->fillForm(correctIndex: null)->call('save')->assertHasErrors('correctIndex');

        $this->assertDatabaseCount('questions', 0);
    }

    public function test_a_correct_index_outside_the_option_range_is_rejected(): void
    {
        // Simulates a tampered payload: the UI cannot produce this value.
        $this->fillForm(correctIndex: 9)->call('save')->assertHasErrors('correctIndex');

        $this->assertDatabaseCount('questions', 0);
    }

    public function test_it_requires_question_text_and_option_text(): void
    {
        $this->fillForm(text: '')->call('save')->assertHasErrors('questionText');

        $this->fillForm(options: ['Pertama', '', 'Ketiga', 'Keempat'])
            ->call('save')
            ->assertHasErrors('options.1.option_text');

        $this->assertDatabaseCount('questions', 0);
    }

    public function test_options_cannot_be_removed_below_four(): void
    {
        Livewire::test('pages::admin.soalan-b')
            ->call('create')
            ->call('removeOption', 0)
            ->assertHasErrors('options')
            ->assertCount('options', 4);
    }

    public function test_removing_the_row_marked_correct_clears_the_selection(): void
    {
        Livewire::test('pages::admin.soalan-b')
            ->call('create')
            ->call('addOption')
            ->set('correctIndex', 2)
            ->call('removeOption', 2)
            ->assertSet('correctIndex', null);
    }

    public function test_removing_a_row_before_the_correct_one_shifts_the_selection(): void
    {
        // Without the shift fix this silently reassigns the correct answer.
        $component = Livewire::test('pages::admin.soalan-b')
            ->call('create')
            ->call('addOption')
            ->set('options.0.option_text', 'Pertama')
            ->set('options.1.option_text', 'Kedua')
            ->set('options.2.option_text', 'Ketiga')
            ->set('options.3.option_text', 'Keempat')
            ->set('options.4.option_text', 'Kelima')
            ->set('correctIndex', 3)
            ->call('removeOption', 1);

        $component->assertSet('correctIndex', 2);
        $component->assertSet('options.2.option_text', 'Keempat');
    }

    public function test_removing_a_row_after_the_correct_one_leaves_the_selection_alone(): void
    {
        Livewire::test('pages::admin.soalan-b')
            ->call('create')
            ->call('addOption')
            ->set('correctIndex', 1)
            ->call('removeOption', 4)
            ->assertSet('correctIndex', 1);
    }

    public function test_the_shifted_selection_saves_the_intended_option(): void
    {
        $component = Livewire::test('pages::admin.soalan-b')
            ->call('create')
            ->call('addOption')
            ->set('subjectValue', 'matematik')
            ->set('questionText', 'Berapakah hasil tambah lima belas dan dua puluh lima?')
            ->set('options.0.option_text', 'Tiga puluh')
            ->set('options.1.option_text', 'Tiga puluh lima')
            ->set('options.2.option_text', 'Empat puluh')
            ->set('options.3.option_text', 'Empat puluh lima')
            ->set('options.4.option_text', 'Lima puluh')
            ->set('correctIndex', 2)
            ->call('removeOption', 0);

        $component->call('save')->assertHasNoErrors();

        $correct = Option::query()->where('is_correct', true)->firstOrFail();

        // "Empat puluh" was correct before the removal and must still be.
        $this->assertSame('Empat puluh', $correct->option_text);
        $this->assertSame(1, Option::query()->where('is_correct', true)->count());
    }

    public function test_editing_loads_the_existing_correct_answer(): void
    {
        $question = $this->makeSectionBQuestion(Subject::Sains);

        // makeSectionBQuestion marks the first option correct.
        Livewire::test('pages::admin.soalan-b')
            ->call('edit', $question->id)
            ->assertSet('subjectValue', 'sains')
            ->assertSet('correctIndex', 0)
            ->assertCount('options', 4);
    }

    public function test_editing_can_move_the_correct_answer(): void
    {
        $question = $this->makeSectionBQuestion(Subject::Sains);

        Livewire::test('pages::admin.soalan-b')
            ->call('edit', $question->id)
            ->set('correctIndex', 2)
            ->call('save')
            ->assertHasNoErrors();

        $options = $question->refresh()->options()->orderBy('id')->get();

        $this->assertFalse($options[0]->is_correct);
        $this->assertTrue($options[2]->is_correct);
        $this->assertSame(1, $question->options()->where('is_correct', true)->count());
    }

    public function test_editing_syncs_options_without_leaving_orphans(): void
    {
        $question = $this->makeSectionBQuestion(Subject::Matematik);

        Livewire::test('pages::admin.soalan-b')
            ->call('edit', $question->id)
            ->call('addOption')
            ->set('options.4.option_text', 'Pilihan kelima yang baharu.')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(5, $question->refresh()->options()->count());
        $this->assertDatabaseCount('options', 5);
    }

    public function test_deleting_an_unused_question_removes_it(): void
    {
        $question = $this->makeSectionBQuestion(Subject::Iq);

        Livewire::test('pages::admin.soalan-b')
            ->call('confirmDelete', $question->id)
            ->call('delete');

        $this->assertDatabaseMissing('questions', ['id' => $question->id]);
    }

    public function test_deleting_a_question_used_in_an_attempt_deactivates_it_instead(): void
    {
        $this->seedQuestionBank();

        $pelajar = User::factory()->pelajar()->create();
        $attempt = app(AttemptService::class)->startOrResume($pelajar);

        $usedId = (int) $attempt->attemptQuestions()->where('position', 100)->value('question_id');

        Livewire::test('pages::admin.soalan-b')
            ->call('confirmDelete', $usedId)
            ->call('delete');

        $this->assertDatabaseHas('questions', ['id' => $usedId, 'is_active' => false]);
        $this->assertSame(100, $attempt->attemptQuestions()->count());
    }

    public function test_a_deactivated_question_is_never_drawn_again(): void
    {
        $this->seedQuestionBank();

        $target = Question::query()->where('subject', Subject::BahasaInggeris)->firstOrFail();

        Livewire::test('pages::admin.soalan-b')
            ->call('confirmDelete', $target->id)
            ->call('delete');

        $attempt = app(AttemptService::class)->startOrResume(User::factory()->pelajar()->create());

        $this->assertNotContains(
            $target->id,
            $attempt->attemptQuestions()->pluck('question_id')->all(),
        );
    }

    public function test_a_section_a_question_cannot_be_edited_from_the_section_b_screen(): void
    {
        $aQuestion = $this->makeSectionAQuestion();

        $this->expectException(ModelNotFoundException::class);

        Livewire::test('pages::admin.soalan-b')->call('edit', $aQuestion->id);
    }

    public function test_the_listing_only_shows_section_b_questions(): void
    {
        $aQuestion = $this->makeSectionAQuestion();
        $aQuestion->update(['question_text' => 'Soalan insaniah tentang kejujuran di sekolah.']);

        $bQuestion = $this->makeSectionBQuestion(Subject::Matematik);
        $bQuestion->update(['question_text' => 'Soalan intelek tentang pendaraban nombor bulat.']);

        Livewire::test('pages::admin.soalan-b')
            ->assertSee('Soalan intelek tentang pendaraban nombor bulat.')
            ->assertDontSee('Soalan insaniah tentang kejujuran di sekolah.');
    }

    public function test_a_pelajar_cannot_reach_either_admin_screen(): void
    {
        $this->actingAs(User::factory()->pelajar()->create());

        $this->get(route('admin.soalan-a'))->assertForbidden();
        $this->get(route('admin.soalan-b'))->assertForbidden();
    }
}
