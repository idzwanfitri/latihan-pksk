<?php

namespace App\Console\Commands;

use App\Services\QuestionBankReport;
use App\Services\QuestionImporter;
use App\Services\QuestionImportResult;
use Illuminate\Console\Command;

class ImportQuestionsCommand extends Command
{
    protected $signature = 'soalan:import
                            {fail : Laluan ke fail JSON yang mengandungi soalan}
                            {--aktif : Aktifkan soalan sebaik diimport (lalai: tidak aktif untuk semakan)}
                            {--dry-run : Sahkan fail sahaja tanpa menulis apa-apa}';

    protected $description = 'Import soalan Bahagian A dan B daripada fail JSON ke dalam bank soalan';

    public function handle(QuestionImporter $importer): int
    {
        $path = (string) $this->argument('fail');

        if (! is_file($path)) {
            $this->components->error('Fail tidak dijumpai: '.$path);

            return self::FAILURE;
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            $this->components->error('Fail tidak dapat dibaca: '.$path);

            return self::FAILURE;
        }

        $payload = json_decode($contents, true);

        if (! is_array($payload)) {
            $this->components->error('JSON tidak sah: '.json_last_error_msg());

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $activate = (bool) $this->option('aktif');

        $result = $importer->import($payload, activate: $activate, dryRun: $dryRun);

        $this->report($result, $dryRun, $activate);

        return $result->hasErrors() ? self::FAILURE : self::SUCCESS;
    }

    private function report(QuestionImportResult $result, bool $dryRun, bool $activate): void
    {
        $this->newLine();

        foreach ($result->skipped as $message) {
            $this->components->warn($message);
        }

        if ($result->hasErrors()) {
            foreach ($result->errors as $message) {
                $this->components->error($message);
            }

            $this->newLine();
            $this->components->error(
                'Import dibatalkan — tiada soalan ditulis. Betulkan '.count($result->errors).' ralat di atas dan cuba semula.'
            );

            return;
        }

        if ($dryRun) {
            $this->components->info(sprintf(
                'Semakan lulus: %d soalan Bahagian A dan %d Bahagian B sedia diimport. Tiada apa-apa ditulis (--dry-run).',
                $result->importedA,
                $result->importedB,
            ));

            return;
        }

        $this->components->info(sprintf(
            '%d soalan diimport (%d Bahagian A, %d Bahagian B) sebagai %s.',
            $result->importedTotal(),
            $result->importedA,
            $result->importedB,
            $activate ? 'AKTIF' : 'TIDAK AKTIF',
        ));

        if (! $activate && $result->importedTotal() > 0) {
            $this->components->warn(
                'Soalan diimport sebagai tidak aktif. Semak dalam skrin admin, kemudian tandakan "Aktif" supaya ia boleh dipilih.'
            );
        }

        $report = app(QuestionBankReport::class);

        $this->newLine();
        $this->line('<options=bold>Kesediaan bank soalan (soalan AKTIF sahaja)</>');

        $rows = [];

        foreach ($report->health() as $row) {
            $rows[] = [
                $row['label'],
                $row['available'],
                $row['required'],
                $row['ok'] ? 'OK' : 'TIDAK CUKUP',
            ];
        }

        $this->table(['Kategori', 'Tersedia', 'Diperlukan', 'Status'], $rows);

        if (! $report->isReady()) {
            $this->components->warn('Ujian belum boleh dimulakan sehingga setiap kategori mencukupi.');
        }

        $this->newLine();
        $this->line('<options=bold>Taburan subjek Bahagian B</>');
        $this->line('<fg=gray>60 soalan bukan-Bahasa Inggeris ditarik bercampur, jadi taburan bank menentukan taburan kertas.</>');

        $spread = [];

        foreach ($report->subjectSpread() as $row) {
            $spread[] = [$row['subject'], $row['count'], $row['percent'].'%'];
        }

        $this->table(['Subjek', 'Soalan aktif', 'Peratus'], $spread);
    }
}
