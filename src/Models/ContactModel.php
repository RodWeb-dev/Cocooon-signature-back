<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DBConnection;
use PDO;

class ContactModel
{
    private static function getDb(): PDO
    {
        return DBConnection::getInstance();
    }

    public static function create(
        string $name,
        string $email,
        string $subject,
        string $content,
    ): bool {
        $sql = "INSERT INTO contact_messages (name, mail, subject, content)
                VALUES (:name, :mail, :subject, :content)";

        $stmt = self::getDb()->prepare($sql);
        $stmt->bindValue("name", $name, PDO::PARAM_STR);
        $stmt->bindValue("mail", $email, PDO::PARAM_STR);
        $stmt->bindValue("subject", $subject, PDO::PARAM_STR);
        $stmt->bindValue("content", $content, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->rowCount() === 1;
    }
}
