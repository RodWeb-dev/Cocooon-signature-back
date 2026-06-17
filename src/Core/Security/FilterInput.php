<?php

declare(strict_types=1);

namespace App\Core\Security;

/** Validates and sanitises request inputs before use in business logic. */
class FilterInput
{
    /**
     * Returns the list of fields that are missing or empty in the request body.
     *
     * @param  array $fields Expected field names
     * @param  array $body   Parsed request body
     * @return array         Missing or empty field names
     */
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

    /** Strips HTML tags and trims whitespace from a string. */
    public static function sanitize(string $value): string
    {
        return trim(strip_tags($value));
    }

    /** Returns true if the value passes FILTER_VALIDATE_EMAIL after sanitisation. */
    public static function email(string $email): bool
    {
        $email = trim($email);
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /** Returns true if the password meets strength requirements: min 10 chars, upper, lower, digit, special char. */
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
