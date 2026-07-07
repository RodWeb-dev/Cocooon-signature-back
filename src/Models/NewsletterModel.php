<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DBConnection;
use PDO;

/** Manages newsletter subscriptions in the newsletter_subscribers table. */
class NewsletterModel
{
    /** Returns the shared PDO connection. */
    private static function getDb(): PDO
    {
        return DBConnection::getInstance();
    }

    /** Inserts a subscriber; if the email already exists, links it to the user if not yet linked. */
    public static function subscribe(string $email, ?string $userId = null): void
    {
        $sql = "INSERT INTO newsletter_subscribers
                (owner, email)
                VALUES (:owner, :email)
                ON DUPLICATE KEY UPDATE
                owner = COALESCE(owner, :owner_update)";

        $stmt = self::getDb()->prepare($sql);
        $stmt->bindValue(':owner', $userId);
        $stmt->bindValue(':owner_update', $userId);
        $stmt->bindValue(':email', $email);
        $stmt->execute();
    }

    /** Removes the newsletter subscription for the given user. */
    public static function unsubscribe(string $userId): void
    {
        $sql = "DELETE FROM newsletter_subscribers WHERE owner = :owner";

        $stmt = self::getDb()->prepare($sql);
        $stmt->bindValue(':owner', $userId);
        $stmt->execute();
    }

    /** Returns the subscription row for a given user, or null. */
    public static function findByOwner(string $userId): ?array
    {
        $sql = "SELECT * FROM newsletter_subscribers WHERE owner = :owner";

        $stmt = self::getDb()->prepare($sql);
        $stmt->bindValue(':owner', $userId);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
