<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidStatusTransition extends RuntimeException
{
    public static function make(string $from, string $to): self
    {
        return new self("A listing cannot go from \"{$from}\" to \"{$to}\".");
    }
}
