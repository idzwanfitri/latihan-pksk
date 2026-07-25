<?php

namespace App\Exceptions;

use RuntimeException;

final class AttemptExpiredException extends RuntimeException
{
    public function __construct(string $message = 'Masa ujian telah tamat.')
    {
        parent::__construct($message);
    }
}
