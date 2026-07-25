<?php

namespace Tests\Feature\Admin;

use App\Enums\Section;
use App\Enums\Subject;
use App\Models\Option;
use App\Models\Question;
use App\Models\User;
use App\Services\AttemptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsQuestionBank;
use Tests\TestCase;

class ImportSoalanTest extends TestCase
{
    use RefreshDatabase, SeedsQuestionBank;

    private string $path = '';

    protected function tearDown(): void
    {
        if ($this->path !== '' && is_file($this->path)) {
            unlink($this->path);
        }

        parent::tearDown();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function fileWith(array $payload): string
    {
        $this->path = tempnam(sys_get_temp_dir(), 'soalan').'.json';

        file_put_contents($this->path, json_encode($payload, JSON_THROW_ON_ERROR));

        return $this->path;
    }

    /**
     * @return array{text: string, options: array<int, array{text: string, score: int}>}
     */
    private function soalanA(string $text = 'Rakan anda meminta menyalin kerja rumah. Apakah tindakan anda?'): array
    {
        return [
            'text' => $text,
            'options' => [
                ['text' => 'Menolak dan menawarkan bantuan mengajarnya.', 'score' => 3],
                ['text' => 'Memberi kerja rumah untuk disalin.', 'score' => 0],
                ['text' => 'Menolak tanpa penjelasan.', 'score' => 1],
                ['text' => 'Menasihatinya berjumpa guru.', 'score' => 2],
            ],
        ];
    }

    /**
     * @return array{subject: string, text: string, options: array<int, string>, correct: int}
     */
    private function soalanB(string $text = 'Lengkapkan siri nombor berikut: 2, 4, 8, 16, ___', string $subject = 'iq'): array
    {
        return [
            'subject' => $subject,
            'text' => $text,
            'options' => ['Dua puluh', 'Tiga puluh dua', 'Dua puluh empat', 'Dua puluh lapan'],
            'correct' => 1,
        ];
    }

    public function test_it_imports_both_sections_from_a_json_file(): void
    {
        $file = $this->fileWith([
            'section_a' => [$this->soalanA()],
            'section_b' => [$this->soalanB()],
        ]);

        $this->artisan('soalan:import', ['fail' => $file])->assertSuccessful();

        $this->assertDatabaseCount('questions', 2);

        $a = Question::query()->where('section', Section::A)->firstOrFail();
        $this->assertNull($a->subject);
        $this->assertSame(4, $a->options()->count());
        $this->assertSame(0, $a->options()->where('is_correct', true)->count());

        $b = Question::query()->where('section', Section::B)->firstOrFail();
        $this->assertSame(Subject::Iq, $b->subject);
        $this->assertSame(1, $b->options()->where('is_correct', true)->count());
        $this->assertSame('Tiga puluh dua', $b->options()->where('is_correct', true)->value('option_text'));
    }

    public function test_imported_questions_are_inactive_by_default(): void
    {
        $file = $this->fileWith(['section_b' => [$this->soalanB()]]);

        $this->artisan('soalan:import', ['fail' => $file])->assertSuccessful();

        $this->assertFalse(Question::query()->firstOrFail()->is_active);
    }

    public function test_the_aktif_flag_imports_them_active(): void
    {
        $file = $this->fileWith(['section_b' => [$this->soalanB()]]);

        $this->artisan('soalan:import', ['fail' => $file, '--aktif' => true])->assertSuccessful();

        $this->assertTrue(Question::query()->firstOrFail()->is_active);
    }

    public function test_inactive_imports_are_never_drawn_into_an_attempt(): void
    {
        $this->seedQuestionBank();

        $file = $this->fileWith(['section_b' => [$this->soalanB()]]);
        $this->artisan('soalan:import', ['fail' => $file])->assertSuccessful();

        $imported = Question::query()->where('is_active', false)->firstOrFail();

        $attempt = app(AttemptService::class)->startOrResume(User::factory()->pelajar()->create());

        $this->assertNotContains($imported->id, $attempt->attemptQuestions()->pluck('question_id')->all());
    }

    public function test_a_dry_run_writes_nothing(): void
    {
        $file = $this->fileWith([
            'section_a' => [$this->soalanA()],
            'section_b' => [$this->soalanB()],
        ]);

        $this->artisan('soalan:import', ['fail' => $file, '--dry-run' => true])->assertSuccessful();

        $this->assertDatabaseCount('questions', 0);
    }

    public function test_a_missing_file_fails_cleanly(): void
    {
        $this->artisan('soalan:import', ['fail' => 'tiada-fail-begini.json'])->assertFailed();
    }

    public function test_malformed_json_fails_cleanly(): void
    {
        $this->path = tempnam(sys_get_temp_dir(), 'soalan').'.json';
        file_put_contents($this->path, '{ ini bukan json');

        $this->artisan('soalan:import', ['fail' => $this->path])->assertFailed();

        $this->assertDatabaseCount('questions', 0);
    }

    public function test_one_bad_item_rejects_the_entire_file(): void
    {
        $bad = $this->soalanB(text: 'Soalan kedua yang rosak indeksnya.');
        $bad['correct'] = 9;

        $file = $this->fileWith([
            'section_b' => [$this->soalanB(), $bad],
        ]);

        $this->artisan('soalan:import', ['fail' => $file])->assertFailed();

        // All-or-nothing: the good item must not slip in either.
        $this->assertDatabaseCount('questions', 0);
    }

    public function test_it_rejects_a_section_b_question_with_an_out_of_range_correct_index(): void
    {
        $item = $this->soalanB();
        $item['correct'] = 4;

        $this->artisan('soalan:import', ['fail' => $this->fileWith(['section_b' => [$item]])])->assertFailed();

        $this->assertDatabaseCount('questions', 0);
    }

    public function test_it_rejects_a_section_b_question_with_an_unknown_subject(): void
    {
        $item = $this->soalanB();
        $item['subject'] = 'bahasa_melayu';

        $this->artisan('soalan:import', ['fail' => $this->fileWith(['section_b' => [$item]])])->assertFailed();

        $this->assertDatabaseCount('questions', 0);
    }

    public function test_it_rejects_fewer_than_four_options(): void
    {
        $item = $this->soalanB();
        $item['options'] = ['Satu', 'Dua', 'Tiga'];
        $item['correct'] = 0;

        $this->artisan('soalan:import', ['fail' => $this->fileWith(['section_b' => [$item]])])->assertFailed();

        $this->assertDatabaseCount('questions', 0);
    }

    public function test_it_rejects_more_than_six_options(): void
    {
        $item = $this->soalanB();
        $item['options'] = ['Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh'];

        $this->artisan('soalan:import', ['fail' => $this->fileWith(['section_b' => [$item]])])->assertFailed();

        $this->assertDatabaseCount('questions', 0);
    }

    public function test_it_rejects_duplicate_options_within_a_question(): void
    {
        $item = $this->soalanB();
        $item['options'] = ['Sama', 'Sama', 'Tiga', 'Empat'];

        $this->artisan('soalan:import', ['fail' => $this->fileWith(['section_b' => [$item]])])->assertFailed();

        $this->assertDatabaseCount('questions', 0);
    }

    public function test_it_rejects_a_section_a_question_where_every_option_scores_zero(): void
    {
        $item = $this->soalanA();

        foreach ($item['options'] as $index => $option) {
            $item['options'][$index]['score'] = 0;
        }

        $this->artisan('soalan:import', ['fail' => $this->fileWith(['section_a' => [$item]])])->assertFailed();

        $this->assertDatabaseCount('questions', 0);
    }

    public function test_it_rejects_question_text_that_is_too_short(): void
    {
        $this->artisan('soalan:import', [
            'fail' => $this->fileWith(['section_b' => [$this->soalanB(text: 'Pendek')]]),
        ])->assertFailed();

        $this->assertDatabaseCount('questions', 0);
    }

    public function test_it_skips_a_question_that_already_exists_in_the_bank(): void
    {
        $file = $this->fileWith(['section_b' => [$this->soalanB()]]);

        $this->artisan('soalan:import', ['fail' => $file])->assertSuccessful();
        $this->assertDatabaseCount('questions', 1);

        // Re-running the same file must not duplicate it.
        $this->artisan('soalan:import', ['fail' => $file])->assertSuccessful();
        $this->assertDatabaseCount('questions', 1);
    }

    public function test_it_skips_duplicates_within_the_same_file(): void
    {
        $file = $this->fileWith([
            'section_b' => [$this->soalanB(), $this->soalanB()],
        ]);

        $this->artisan('soalan:import', ['fail' => $file])->assertSuccessful();

        $this->assertDatabaseCount('questions', 1);
    }

    public function test_an_empty_payload_succeeds_without_writing(): void
    {
        $this->artisan('soalan:import', ['fail' => $this->fileWith([])])->assertSuccessful();

        $this->assertDatabaseCount('questions', 0);
    }

    public function test_it_reports_when_the_bank_is_still_short_of_a_full_paper(): void
    {
        $this->artisan('soalan:import', ['fail' => $this->fileWith(['section_b' => [$this->soalanB()]])])
            ->expectsOutputToContain('TIDAK CUKUP')
            ->assertSuccessful();
    }

    public function test_a_large_import_produces_a_bank_that_can_run_an_attempt(): void
    {
        $sectionA = [];
        $sectionB = [];

        for ($i = 0; $i < 30; $i++) {
            $sectionA[] = $this->soalanA('Soalan insaniah bernombor '.$i.' untuk ujian import pukal.');
        }

        $subjects = ['iq', 'matematik', 'sains', 'pengetahuan_am'];

        for ($i = 0; $i < 60; $i++) {
            $sectionB[] = $this->soalanB(
                'Soalan intelek bernombor '.$i.' untuk ujian import pukal.',
                $subjects[$i % 4],
            );
        }

        for ($i = 0; $i < 10; $i++) {
            $sectionB[] = $this->soalanB(
                'Soalan bahasa Inggeris bernombor '.$i.' untuk ujian import pukal.',
                'bahasa_inggeris',
            );
        }

        $file = $this->fileWith(['section_a' => $sectionA, 'section_b' => $sectionB]);

        $this->artisan('soalan:import', ['fail' => $file, '--aktif' => true])->assertSuccessful();

        $this->assertDatabaseCount('questions', 100);
        $this->assertDatabaseCount('options', 400);

        $attempt = app(AttemptService::class)->startOrResume(User::factory()->pelajar()->create());

        $this->assertSame(100, $attempt->attemptQuestions()->count());

        // Every imported section-B question still holds exactly one correct answer.
        $bad = Question::query()
            ->where('section', Section::B)
            ->withCount(['options as correct_count' => fn ($query) => $query->where('is_correct', true)])
            ->get()
            ->filter(fn (Question $question): bool => $question->correct_count !== 1);

        $this->assertCount(0, $bad);

        // Section B options must carry no score, section A options must carry one.
        $this->assertSame(0, Option::query()->whereNotNull('score')->whereHas(
            'question', fn ($query) => $query->where('section', Section::B)
        )->count());
    }
}
