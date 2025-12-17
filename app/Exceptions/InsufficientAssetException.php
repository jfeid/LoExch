<?php

namespace App\Exceptions;

use Exception;

class InsufficientAssetException extends Exception
{
    public function __construct(string $symbol, string $message = '')
    {
        parent::__construct($message ?: "Insufficient {$symbol} balance");
    }
}
