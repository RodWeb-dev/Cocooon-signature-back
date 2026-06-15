<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DBConnection;
use PDO;

/** PDO queries for the users, addresses, reset_pwd_tokens and refresh_tokens tables. */
class UserModel
{
    private static function getDb(): PDO
    {
        return DBConnection::getInstance();
    }

    /** Returns a user row by email, or null if not found. */
    public static function findByEmail(string $email): ?array
    {
        $sql = "SELECT * FROM users WHERE email = :email";

        $stmt = self::getDb()->prepare($sql);
        $stmt->bindParam(':email', $email, \PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /** Returns a user row by UUID, or null if not found. */
    public static function findById(string $id): ?array
    {
        $sql = "SELECT * FROM users WHERE id = :id";

        $stmt = self::getDb()->prepare($sql);
        $stmt->bindParam(':id', $id, \PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Inserts a new user and returns its generated UUID. */
    public static function create(string $firstname, string $lastname, string $email, string $hash): void
    {
        $sql = "INSERT INTO users
            (firstname, lastname, email, hash_pwd)
            VALUES (:id, :firstname, :lastname, :email, :hash_pwd)
        ";

        $stmt = self::getDb()->prepare($sql);
        $stmt->bindParam(':firstname', $firstname, PDO::PARAM_STR);
        $stmt->bindParam(':lastname', $lastname, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':hash_pwd', $hash, PDO::PARAM_STR);
        $stmt->execute();
    }

    /** Inserts a token row into the given table. */
    private static function saveToken(string $table, string $userId, string $token, int $expiresAt): void
    {
        $sql = "INSERT INTO $table (owner, value, expires_at) VALUES (:user_id, :token, :expires_at)";

        $expiresAt = date('Y-m-d H:i:s', $expiresAt);

        $stmt = self::getDb()->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_STR);
        $stmt->bindParam(':token', $token, PDO::PARAM_STR);
        $stmt->bindParam(':expires_at', $expiresAt, PDO::PARAM_STR);
        $stmt->execute();
        
    }

    /** Returns a token row by value, or null. */
    private static function findToken(string $table, string $token): ?array
    {
        $sql = "SELECT * FROM $table WHERE value = :token";

        $stmt = self::getDb()->prepare($sql);
        $stmt->bindParam(':token', $token, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Deletes a token row by value. */
    private static function deleteToken(string $table, string $token): void
    {
        $sql = "DELETE FROM $table WHERE value = :token";
        $stmt = self::getDb()->prepare($sql);
        $stmt->bindParam(':token', $token, PDO::PARAM_STR);
        $stmt->execute();
    }

    /** Persists a password-reset token tied to the user. */
    public static function saveResetToken(string $userId, string $token, int $expiresAt): void
    {
        Self::saveToken('reset_pwd_tokens', $userId, $token, $expiresAt);
    }

    /** Returns a reset_pwd_tokens row by token value, or null. */
    public static function findResetToken(string $token): ?array
    {
        return Self::findToken('reset_pwd_tokens', $token);
    }

    /** Deletes a reset token after use (single-use enforcement). */
    public static function deleteResetToken(string $token): void
    {
        Self::deleteToken('reset_pwd_tokens', $token);
    }

    /** Persists a JWT refresh token tied to the user. */
    public static function saveRefreshToken(string $userId, string $token, int $expiresAt): void
    {
        Self::saveToken('refresh_tokens', $userId, $token, $expiresAt);
    }

    /** Returns a refresh_tokens row by token value, or null. */
    public static function findRefreshToken(string $token): ?array
    {
        return Self::findToken('refresh_tokens', $token);
    }

    /** Deletes a refresh token on logout or rotation. */
    public static function deleteRefreshToken(string $token): void
    {
        Self::deleteToken('refresh_tokens', $token);
    }

    /** Updates allowed profile fields (firstname, lastname, email, phone_nbr, birthday) for the given user. */
    public static function update(string $userId, array $data): void
    {
        if (empty($data)) {
            return;
        }

        $allowed = ['firstname', 'lastname', 'email', 'phone_nbr', 'birthday'];
        $setParts = [];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $setParts[] = "$field = :$field";
            }
        }

        $sql = "UPDATE users SET " . implode(', ', $setParts) . "
            WHERE id = :user_id
        ";

        $stmt = self::getDb()->prepare($sql);

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $stmt->bindParam(":$field", $data[$field], PDO::PARAM_STR);
            }
        }
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_STR);
        $stmt->execute();
    }

    /** Replaces the stored bcrypt hash for the user. */
    public static function updatePassword(string $userId, string $hash): void
    {
        $sql = "UPDATE users SET hash_pwd = :hash WHERE id = :user_id";

        $stmt = self::getDb()->prepare($sql);
        $stmt->bindParam(':hash', $hash, PDO::PARAM_STR);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_STR);
        $stmt->execute();
    }

    /** Permanently deletes the user row (cascades to addresses, carts, orders via FK). */
    public static function delete(string $userId): void
    {
        $sql = "DELETE FROM users WHERE id = :user_id";

        $stmt = self::getDb()->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_STR);
        $stmt->execute();
    }

    /** Returns all addresses belonging to the user. */
    public static function getAddresses(string $userId): array
    {
        $sql = "SELECT * FROM addresses WHERE owner = :user_id";

        $stmt = self::getDb()->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Inserts a delivery address and returns its new id. */
    public static function addAddress(string $userId, array $data): int
    {
        $sql = "
            INSERT INTO addresses (owner, name, address, city, postal_code, country)
            VALUES (:user_id, :name, :address, :city, :postal_code, :country)
        ";

        $stmt = self::getDb()->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_STR);
        $stmt->bindParam(':name', $data['name'], PDO::PARAM_STR);
        $stmt->bindParam(':address', $data['address'], PDO::PARAM_STR);
        $stmt->bindParam(':city', $data['city'], PDO::PARAM_STR);
        $stmt->bindParam(':postal_code', $data['postal_code'], PDO::PARAM_STR);
        $stmt->bindParam(':country', $data['country'], PDO::PARAM_STR);
        $stmt->execute();

        return (int)self::getDb()->lastInsertId();
    }

    /** Updates allowed fields on an address — owner check prevents cross-user edits. */
    public static function updateAddress(int $id, string $userId, array $data): void
    {
        if (empty($data)) {
            return;
        }

        $allowed = ['name', 'address', 'city', 'postal_code', 'country'];
        $setParts = [];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $setParts[] = "$field = :$field";
            }
        }
        
        $sql = "UPDATE addresses SET " . implode(', ', $setParts) . "
            WHERE id = :address_id AND owner = :user_id
        ";
        $stmt = self::getDb()->prepare($sql);
        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $stmt->bindParam(":$field", $data[$field], PDO::PARAM_STR);
            }
        }
        $stmt->bindParam(':address_id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_STR);
        $stmt->execute();
        
    }

    /** Deletes an address — owner check prevents cross-user deletes. */
    public static function deleteAddress(int $id, string $userId): void
    {
        $sql = "DELETE FROM addresses WHERE id = :address_id AND owner = :user_id";

        $stmt = self::getDb()->prepare($sql);
        $stmt->bindParam(':address_id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_STR);
        $stmt->execute();
    }
}
