<?php

namespace App\Services;

final class QuestionImportResult
{
    /** @var array<int, string> */
    public array $errors = [];

    /** @var array<int, string> */
    public array $skipped = [];

    public int $importedA = 0;

    public int $importedB = 0;

    public function addError(string $where, string $message): void
    {
        $this->errors[] = $where.': '.$message;
    }

    public function addSkipped(string $where, string $message): void
    {
        $this->skipped[] = $where.': '.$message;
    }

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    public function importedTotal(): int
    {
        return $this->importedA + $this->importedB;
    }
}
