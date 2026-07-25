<?php

namespace App\Exceptions;

use RuntimeException;

final class AttemptNotActiveException extends RuntimeException
{
    public function __construct(string $message = 'Percubaan ujian ini telah tamat.')
    {
        parent::__construct($message);
    }
}
