<?php

declare(strict_types=1);

namespace App\Core\Security;

class FilterInput
{
    public static function required(array $fields, array $body): array
    {
        $missingFields = [];
        foreach($fields as $field) {
            if(!array_key_exists($field, $body) || empty(trim($body[$field]))) {
                $missingFields[] = $field;
            }
        }
        
        return $missingFields;
    }

    public static function sanitize(string $value): string
    {
        return trim(strip_tags($value));
    }

    public static function email(string $email): bool
    {
        $email = trim($email);
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function password(string $password): bool
    {
        if(strlen($password) < 10) {return false;}
        if(!preg_match('/[A-Z]/', $password)) {return false;}
        if(!preg_match('/[a-z]/', $password)) {return false;}
        if(!preg_match('/[\d]/', $password)) {return false;}
        if(!preg_match('/[!@#$%^&*()\-_=+\[\]{}|;:,.<>?\/~]/', $password)) {return false;}

        return true;
    }
}
