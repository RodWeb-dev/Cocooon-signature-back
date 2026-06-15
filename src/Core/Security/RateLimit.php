<?php

declare(strict_types=1);

namespace App\Core\Security;

class RateLimit
{
    public static function check(string $action, string $identifier): bool
    {
        return true;
        // A completer
    }
    
    public static function hit(string $action, string $identifier): void
    {
        // A completer
    }
}
