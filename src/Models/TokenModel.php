<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DBConnection;
use PDO;

class TokenModel
{
    private static function getDb(): PDO
    {
        return DBConnection::getInstance();
    }

    /** Inserts a token row into the given table. */
    private static function saveToken(string $table, string $userId, string $token, int $expiresAt): void
    {
        $sql = "INSERT INTO $table (owner, value, expires_at) VALUES (:user_id, :token, :expires_at)";

        $expiresAt = date('Y-m-d H:i:s', $expiresAt);

        $stmt = self::getDb()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_STR);
        $stmt->bindValue(':token', $token, PDO::PARAM_STR);
        $stmt->bindValue(':expires_at', $expiresAt, PDO::PARAM_STR);
        $stmt->execute();
        
    }

    /** Returns a token row by value, or null. */
    private static function findToken(string $table, string $token): ?array
    {
        $sql = "SELECT * FROM $table WHERE value = ?";

        $stmt = self::getDb()->prepare($sql);
        $stmt->bindValue(1, $token, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** Deletes a token row by value. */
    private static function deleteToken(string $table, string $token): void
    {
        $sql = "DELETE FROM $table WHERE value = ?";
        $stmt = self::getDb()->prepare($sql);
        $stmt->bindValue(1, $token, PDO::PARAM_STR);
        $stmt->execute();
    }

    /** Persists a password-reset token tied to the user. */
    public static function saveResetToken(string $userId, string $token, int $expiresAt): void
    {
        self::saveToken('reset_pwd_tokens', $userId, $token, $expiresAt);
    }

    /** Returns a reset_pwd_tokens row by token value, or null. */
    public static function findResetToken(string $token): ?array
    {
        return self::findToken('reset_pwd_tokens', $token);
    }

    /** Deletes a reset token after use (single-use enforcement). */
    public static function deleteResetToken(string $token): void
    {
        self::deleteToken('reset_pwd_tokens', $token);
    }

    /** Persists a JWT refresh token tied to the user. */
    public static function saveRefreshToken(string $userId, string $token, int $expiresAt): void
    {
        self::saveToken('refresh_tokens', $userId, $token, $expiresAt);
    }

    /** Returns a refresh_tokens row by token value, or null. */
    public static function findRefreshToken(string $token): ?array
    {
        return self::findToken('refresh_tokens', $token);
    }

    /** Deletes a refresh token on logout or rotation. */
    public static function deleteRefreshToken(string $token): void
    {
        self::deleteToken('refresh_tokens', $token);
    }

    public static function saveVerifyToken(string $userId, string $token, int $expiresAt): void
    {
        self::saveToken('verify_email_tokens', $userId, $token, $expiresAt);
    }

    public static function findVerifyToken(string $token): ?array
    {
        return self::findToken('verify_email_tokens', $token);
    }

    public static function deleteVerifyToken(string $token): void
    {
        self::deleteToken('verify_email_tokens', $token);
    }
}
