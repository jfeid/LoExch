<?php

namespace App\Exceptions;

use Exception;

class InsufficientBalanceException extends Exception
{
    public function __construct(string $message = 'Insufficient USD balance')
    {
        parent::__construct($message);
    }
}
