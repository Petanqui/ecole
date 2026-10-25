<?php

namespace App\Exceptions\Core;

use RuntimeException;

class PhoneNumberAlreadyUsedException extends RuntimeException
{
    public function __construct(public readonly string $phone)
    {
        parent::__construct("Un compte utilise déjà le numéro {$phone}.");
    }
}
