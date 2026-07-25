<?php

namespace App\Exceptions;

use App\Enums\Section;
use App\Enums\Subject;
use RuntimeException;

final class InsufficientQuestionBankException extends RuntimeException
{
    public static function for(Section $section, ?Subject $subject, int $needed, int $available): self
    {
        $scope = $subject instanceof Subject
            ? $section->label().' ('.$subject->label().')'
            : $section->label();

        return new self(sprintf(
            'Bank soalan tidak mencukupi untuk %s: %d diperlukan, %d tersedia.',
            $scope,
            $needed,
            $available,
        ));
    }
}
