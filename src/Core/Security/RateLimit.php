<?php

declare(strict_types=1);

namespace App\Core\Security;

use App\Core\DBConnection;
use PDO;

class RateLimit
{
    private const LIMITS = [
        'login'            => ['max' => 5, 'window' => 900,  'lockout' => 900],
        'register'         => ['max' => 3, 'window' => 3600, 'lockout' => 3600],
        'forgot-password'  => ['max' => 3, 'window' => 3600, 'lockout' => 3600],
    ];

    private static function getDB() : PDO {
        return DBConnection::getInstance();
    }
    
    public static function check(string $action, string $identifier): bool
    {
        $sql = "SELECT blocked_until FROM rate_limits WHERE action = :action AND identifier = :identifier";

        $stmt = self::getDB()->prepare($sql);
        $stmt->bindValue(':action', $action, PDO::PARAM_STR);
        $stmt->bindValue(':identifier', $identifier, PDO::PARAM_STR);
        $stmt->execute();

        $blocked = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$blocked || $blocked['blocked_until'] === null) {
            return true;
        }
        if (strtotime($blocked['blocked_until']) > time()) {
            return false;
        }
        return true;
    }
    
    public static function hit(string $action, string $identifier): void
    {
        $sql = "SELECT * FROM rate_limits WHERE action = :action AND identifier = :identifier";

        $stmt = self::getDB()->prepare($sql);
        $stmt->bindValue(':action', $action, PDO::PARAM_STR);
        $stmt->bindValue(':identifier', $identifier, PDO::PARAM_STR);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if(!$result) {
            $sql = "INSERT INTO rate_limits (action, identifier) VALUES (:action, :identifier)";

            $stmt = self::getDB()->prepare($sql);
            $stmt->bindValue(':action', $action, PDO::PARAM_STR);
            $stmt->bindValue(':identifier', $identifier, PDO::PARAM_STR);
            $stmt->execute();

            return;
        }

        $first_attempt = strtotime($result['first_attempt']);
        $attempts = $result['attempts'] + 1;

        if($first_attempt + self::LIMITS[$action]['window'] > time() && $attempts >= self::LIMITS[$action]['max']) {
            $sql = "UPDATE rate_limits
                SET blocked_until = :blocked_until, attempts = :attempts
                WHERE action = :action AND identifier = :identifier";

            $stmt = self::getDB()->prepare($sql);
            $stmt->bindValue(':attempts', $attempts, PDO::PARAM_INT);
            $stmt->bindValue(':blocked_until', date('Y-m-d H:i:s', time() + self::LIMITS[$action]['lockout']) , PDO::PARAM_STR);
            $stmt->bindValue(':action', $action, PDO::PARAM_STR);
            $stmt->bindValue(':identifier', $identifier, PDO::PARAM_STR);
            $stmt->execute();

        } elseif ($first_attempt + self::LIMITS[$action]['window'] > time()) {
            $sql = "UPDATE rate_limits SET attempts = :attempts WHERE action = :action AND identifier = :identifier";

            $stmt = self::getDB()->prepare($sql);
            $stmt->bindValue(':attempts', $attempts, PDO::PARAM_INT);
            $stmt->bindValue(':action', $action, PDO::PARAM_STR);
            $stmt->bindValue(':identifier', $identifier, PDO::PARAM_STR);
            $stmt->execute();

        } else {
            $sql = "UPDATE rate_limits
                SET attempts = :attempts, first_attempt = NOW(), blocked_until = NULL
                WHERE action = :action AND identifier = :identifier";

            $stmt = self::getDB()->prepare($sql);
            $stmt->bindValue(':attempts', 1, PDO::PARAM_INT);
            $stmt->bindValue(':action', $action, PDO::PARAM_STR);
            $stmt->bindValue(':identifier', $identifier, PDO::PARAM_STR);
            $stmt->execute();
        }
    }

    public static function reset(string $action, string $identifier): void
    {
        $sql = "UPDATE rate_limits
                SET attempts = 1, first_attempt = NOW(), blocked_until = NULL
                WHERE action = :action AND identifier = :identifier";

        $stmt = self::getDB()->prepare($sql);
        $stmt->bindValue(':action', $action, PDO::PARAM_STR);
        $stmt->bindValue(':identifier', $identifier, PDO::PARAM_STR);
        $stmt->execute();
    }
}
