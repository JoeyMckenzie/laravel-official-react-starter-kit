<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

final class UserNotFoundException extends Exception
{
    public function __construct()
    {
        parent::__construct('User was not found on request.');
    }
}
