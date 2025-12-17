<?php

namespace App\Exceptions;

use Exception;

class OrderNotCancellableException extends Exception
{
    public function __construct(string $message = 'Order cannot be cancelled')
    {
        parent::__construct($message);
    }
}
