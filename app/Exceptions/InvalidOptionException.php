<?php

namespace App\Exceptions;

use RuntimeException;

final class InvalidOptionException extends RuntimeException
{
    public function __construct(string $message = 'Pilihan tidak sah bagi soalan ini.')
    {
        parent::__construct($message);
    }
}
