<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DBConnection;
use PDO;

class NewsletterModel
{
    private static function getDb(): PDO
    {
        return DBConnection::getInstance();
    }

    public static function subscribe(string $email, ?string $userId = null): void
    {
        $sql = "INSERT INTO newsletter_subscribers
                (owner, email)
                VALUES (:owner, :email)
                ON DUPLICATE KEY UPDATE
                owner = COALESCE(owner, :owner)";

        $stmt = self::getDb()->prepare($sql);
        $stmt->bindValue(':owner', $userId);
        $stmt->bindValue(':email', $email);
        $stmt->execute();
    }

    public static function unsubscribe(string $userId): void
    {
        $sql = "DELETE FROM newsletter_subscribers WHERE owner = :owner";

        $stmt = self::getDb()->prepare($sql);
        $stmt->bindValue(':owner', $userId);
        $stmt->execute();
    }

    public static function findByOwner(string $userId): ?array
    {
        $sql = "SELECT * FROM newsletter_subscribers WHERE owner = :owner";

        $stmt = self::getDb()->prepare($sql);
        $stmt->bindValue(':owner', $userId);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
