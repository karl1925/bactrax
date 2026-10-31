<?php

namespace App\Services;

use RuntimeException;

/** A failure talking to DigiSign. The message is safe to show to the user. */
class DigiSignException extends RuntimeException
{
    public function __construct(string $message, private int $httpStatus = 502)
    {
        parent::__construct($message);
    }

    public function httpStatus(): int
    {
        return $this->httpStatus;
    }
}
