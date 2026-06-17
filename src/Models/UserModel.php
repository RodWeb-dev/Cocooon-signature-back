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
        $sql = "SELECT * FROM users WHERE email = ?";

        $stmt = self::getDb()->prepare($sql);
        $stmt->bindValue(1, $email, \PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /** Returns a user row by UUID, or null if not found. */
    public static function findById(string $id): ?array
    {
        $sql = "SELECT * FROM users WHERE id = :id";

        $stmt = self::getDb()->prepare($sql);
        $stmt->bindValue(':id', $id, \PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Inserts a new user and returns its generated UUID. */
    public static function create(string $firstname, string $lastname, string $email, string $hash): string
    {
        $sql = "INSERT INTO users
            (firstname, lastname, email, hash_pwd)
            VALUES (:id, :firstname, :lastname, :email, :hash_pwd)
        ";

        $stmt = self::getDb()->prepare($sql);
        $stmt->bindValue(':firstname', $firstname, PDO::PARAM_STR);
        $stmt->bindValue(':lastname', $lastname, PDO::PARAM_STR);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->bindValue(':hash_pwd', $hash, PDO::PARAM_STR);
        $stmt->execute();

        $sql = "SELECT id FROM users WHERE email = :email";

        $stmt = self::getDb()->prepare($sql);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->execute();

        $id = $stmt->fetch(PDO::FETCH_ASSOC);

        return $id['id'];
    }

    public static function markEmailVerified(string $userId): void
    {
        $sql = "UPDATE users SET email_verified = 1
                WHERE id = :id";

        $stmt = self::getDb()->prepare($sql);
        $stmt->bindValue(':id', $userId, PDO::PARAM_STR);
        $stmt->execute();
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
                $stmt->bindValue(":$field", $data[$field], PDO::PARAM_STR);
            }
        }
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
        $stmt->execute();
    }

    /** Replaces the stored bcrypt hash for the user. */
    public static function updatePassword(string $userId, string $hash): void
    {
        $sql = "UPDATE users SET hash_pwd = :hash WHERE id = :user_id";

        $stmt = self::getDb()->prepare($sql);
        $stmt->bindValue(':hash', $hash, PDO::PARAM_STR);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
        $stmt->execute();
    }

    /** Permanently deletes the user row (cascades to addresses, carts, orders via FK). */
    public static function delete(string $userId): void
    {
        $sql = "DELETE FROM users WHERE id = :user_id";

        $stmt = self::getDb()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
        $stmt->execute();
    }

    /** Returns all addresses belonging to the user. */
    public static function getAddresses(string $userId): array
    {
        $sql = "SELECT * FROM addresses WHERE owner = :user_id";

        $stmt = self::getDb()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
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
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
        $stmt->bindValue(':name', $data['name'], PDO::PARAM_STR);
        $stmt->bindValue(':address', $data['address'], PDO::PARAM_STR);
        $stmt->bindValue(':city', $data['city'], PDO::PARAM_STR);
        $stmt->bindValue(':postal_code', $data['postal_code'], PDO::PARAM_STR);
        $stmt->bindValue(':country', $data['country'], PDO::PARAM_STR);
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
                $stmt->bindValue(":$field", $data[$field], PDO::PARAM_STR);
            }
        }
        $stmt->bindValue(':address_id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
        $stmt->execute();
        
    }

    /** Deletes an address — owner check prevents cross-user deletes. */
    public static function deleteAddress(int $id, string $userId): void
    {
        $sql = "DELETE FROM addresses WHERE id = :address_id AND owner = :user_id";

        $stmt = self::getDb()->prepare($sql);
        $stmt->bindValue(':address_id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
        $stmt->execute();
    }
}
