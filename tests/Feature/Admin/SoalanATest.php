<?php

namespace Tests\Feature\Admin;

use App\Enums\Section;
use App\Enums\Subject;
use App\Models\Question;
use App\Models\User;
use App\Services\AttemptService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\Concerns\SeedsQuestionBank;
use Tests\TestCase;

class SoalanATest extends TestCase
{
    use RefreshDatabase, SeedsQuestionBank;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->admin()->create());
    }

    /**
     * @param  array<int, array{text: string, score: int}>  $options
     */
    private function fillForm(array $options, string $text = 'Rakan anda meminta bantuan menyiapkan kerja rumah. Apakah tindakan anda?'): Testable
    {
        $component = Livewire::test('pages::admin.soalan-a')
            ->call('create')
            ->set('questionText', $text);

        foreach ($options as $index => $option) {
            $component->set("options.{$index}.option_text", $option['text']);
            $component->set("options.{$index}.score", $option['score']);
        }

        return $component;
    }

    public function test_the_admin_page_loads(): void
    {
        $this->get(route('admin.soalan-a'))->assertOk();
    }

    public function test_an_admin_can_create_a_section_a_question_with_scored_options(): void
    {
        $this->fillForm([
            ['text' => 'Menolak dengan sopan dan menawarkan bantuan mengajarnya.', 'score' => 3],
            ['text' => 'Memberi kerja rumah anda untuk disalin.', 'score' => 0],
            ['text' => 'Menolak tanpa memberi penjelasan.', 'score' => 1],
            ['text' => 'Menasihatinya berjumpa guru.', 'score' => 2],
        ])->call('save')->assertHasNoErrors();

        $question = Question::query()->firstOrFail();

        $this->assertSame(Section::A, $question->section);
        $this->assertNull($question->subject);
        $this->assertTrue($question->is_active);
        $this->assertSame(4, $question->options()->count());
        $this->assertSame([0, 1, 2, 3], $question->options()->orderBy('id')->pluck('score')->sort()->values()->all());

        // Section A has no correct answer at all.
        $this->assertSame(0, $question->options()->where('is_correct', true)->count());
    }

    public function test_it_rejects_a_question_where_every_option_scores_zero(): void
    {
        $this->fillForm([
            ['text' => 'Pilihan pertama yang panjang sedikit.', 'score' => 0],
            ['text' => 'Pilihan kedua yang panjang sedikit.', 'score' => 0],
            ['text' => 'Pilihan ketiga yang panjang sedikit.', 'score' => 0],
            ['text' => 'Pilihan keempat yang panjang sedikit.', 'score' => 0],
        ])->call('save')->assertHasErrors('options');

        $this->assertDatabaseCount('questions', 0);
    }

    public function test_it_requires_question_text(): void
    {
        $this->fillForm([
            ['text' => 'Pilihan pertama yang panjang sedikit.', 'score' => 3],
            ['text' => 'Pilihan kedua yang panjang sedikit.', 'score' => 2],
            ['text' => 'Pilihan ketiga yang panjang sedikit.', 'score' => 1],
            ['text' => 'Pilihan keempat yang panjang sedikit.', 'score' => 0],
        ], text: '')->call('save')->assertHasErrors('questionText');

        $this->assertDatabaseCount('questions', 0);
    }

    public function test_it_requires_every_option_to_have_text(): void
    {
        $this->fillForm([
            ['text' => 'Pilihan pertama yang panjang sedikit.', 'score' => 3],
            ['text' => '', 'score' => 2],
            ['text' => 'Pilihan ketiga yang panjang sedikit.', 'score' => 1],
            ['text' => 'Pilihan keempat yang panjang sedikit.', 'score' => 0],
        ])->call('save')->assertHasErrors('options.1.option_text');

        $this->assertDatabaseCount('questions', 0);
    }

    public function test_a_new_form_starts_with_exactly_four_option_rows(): void
    {
        Livewire::test('pages::admin.soalan-a')
            ->call('create')
            ->assertCount('options', 4);
    }

    public function test_options_cannot_be_removed_below_four(): void
    {
        Livewire::test('pages::admin.soalan-a')
            ->call('create')
            ->call('removeOption', 0)
            ->assertHasErrors('options')
            ->assertCount('options', 4);
    }

    public function test_options_cannot_exceed_six(): void
    {
        Livewire::test('pages::admin.soalan-a')
            ->call('create')
            ->call('addOption')
            ->call('addOption')
            ->assertCount('options', 6)
            ->call('addOption')
            ->assertHasErrors('options')
            ->assertCount('options', 6);
    }

    public function test_removing_an_option_reindexes_the_remaining_rows(): void
    {
        $component = Livewire::test('pages::admin.soalan-a')
            ->call('create')
            ->call('addOption')
            ->set('options.0.option_text', 'Pertama')
            ->set('options.1.option_text', 'Kedua')
            ->set('options.2.option_text', 'Ketiga')
            ->set('options.3.option_text', 'Keempat')
            ->set('options.4.option_text', 'Kelima')
            ->call('removeOption', 1);

        // Indices must stay contiguous, otherwise wire:model binds to a hole.
        $component->assertCount('options', 4);
        $component->assertSet('options.0.option_text', 'Pertama');
        $component->assertSet('options.1.option_text', 'Ketiga');
        $component->assertSet('options.2.option_text', 'Keempat');
        $component->assertSet('options.3.option_text', 'Kelima');
    }

    public function test_every_option_row_has_a_stable_unique_key(): void
    {
        $component = Livewire::test('pages::admin.soalan-a')->call('create');

        /** @var array<int, array{key: string}> $options */
        $options = $component->get('options');

        $keys = array_column($options, 'key');

        $this->assertCount(4, array_unique($keys));
        $this->assertNotContains('', $keys);
    }

    public function test_an_admin_can_edit_a_question_and_options_are_synced_without_orphans(): void
    {
        $question = $this->makeSectionAQuestion([3, 2, 1, 0]);

        $component = Livewire::test('pages::admin.soalan-a')
            ->call('edit', $question->id)
            ->assertSet('questionText', $question->question_text)
            ->assertCount('options', 4)
            ->call('addOption')
            ->set('options.4.option_text', 'Pilihan tambahan yang kelima.')
            ->set('options.4.score', 2)
            ->set('questionText', 'Teks soalan yang telah dikemas kini sepenuhnya.');

        $component->call('save')->assertHasNoErrors();

        $question->refresh();

        $this->assertSame('Teks soalan yang telah dikemas kini sepenuhnya.', $question->question_text);
        $this->assertSame(5, $question->options()->count());
        $this->assertDatabaseCount('questions', 1);
        $this->assertDatabaseCount('options', 5);
    }

    public function test_removing_an_option_while_editing_deletes_the_row(): void
    {
        $question = $this->makeSectionAQuestion([3, 2, 1, 0]);

        Livewire::test('pages::admin.soalan-a')
            ->call('edit', $question->id)
            ->call('addOption')
            ->set('options.4.option_text', 'Sementara sahaja untuk diuji.')
            ->set('options.4.score', 1)
            ->call('removeOption', 0)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(4, $question->refresh()->options()->count());
        $this->assertDatabaseCount('options', 4);
    }

    public function test_deleting_an_unused_question_removes_it(): void
    {
        $question = $this->makeSectionAQuestion();

        Livewire::test('pages::admin.soalan-a')
            ->call('confirmDelete', $question->id)
            ->call('delete');

        $this->assertDatabaseMissing('questions', ['id' => $question->id]);
    }

    public function test_deleting_a_question_used_in_an_attempt_deactivates_it_instead(): void
    {
        $this->seedQuestionBank();

        $pelajar = User::factory()->pelajar()->create();
        $attempt = app(AttemptService::class)->startOrResume($pelajar);

        $usedId = (int) $attempt->attemptQuestions()->where('position', 1)->value('question_id');

        Livewire::test('pages::admin.soalan-a')
            ->call('confirmDelete', $usedId)
            ->call('delete');

        $this->assertDatabaseHas('questions', ['id' => $usedId, 'is_active' => false]);
        $this->assertSame(100, $attempt->attemptQuestions()->count());
    }

    public function test_the_listing_only_shows_section_a_questions(): void
    {
        $aQuestion = $this->makeSectionAQuestion();
        $aQuestion->update(['question_text' => 'Soalan insaniah tentang kejujuran di sekolah.']);

        $bQuestion = $this->makeSectionBQuestion(Subject::Matematik);
        $bQuestion->update(['question_text' => 'Soalan intelek tentang pendaraban nombor bulat.']);

        Livewire::test('pages::admin.soalan-a')
            ->assertSee('Soalan insaniah tentang kejujuran di sekolah.')
            ->assertDontSee('Soalan intelek tentang pendaraban nombor bulat.');
    }

    public function test_a_section_b_question_cannot_be_edited_from_the_section_a_screen(): void
    {
        $bQuestion = $this->makeSectionBQuestion(Subject::Sains);

        // findOrFail surfaces as a thrown exception under Livewire::test, not a
        // response status.
        $this->expectException(ModelNotFoundException::class);

        Livewire::test('pages::admin.soalan-a')->call('edit', $bQuestion->id);
    }

    public function test_searching_filters_the_listing(): void
    {
        $first = $this->makeSectionAQuestion();
        $first->update(['question_text' => 'Soalan tentang kejujuran di sekolah rendah.']);

        $second = $this->makeSectionAQuestion();
        $second->update(['question_text' => 'Soalan tentang kerjasama dalam kumpulan.']);

        Livewire::test('pages::admin.soalan-a')
            ->set('search', 'kejujuran')
            ->assertSee('Soalan tentang kejujuran di sekolah rendah.')
            ->assertDontSee('Soalan tentang kerjasama dalam kumpulan.');
    }
}
