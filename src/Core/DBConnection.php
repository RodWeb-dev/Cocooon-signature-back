<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use App\Core\Exceptions\HttpException;

/**
 * PDO singleton — call DBConnection::getInstance() to get the shared connection.
 */
class DBConnection
{
    private static ?PDO $instance = null;

    private function __construct()
    {
        // Singleton — prevents direct instantiation
    }

    /**
     * @throws HttpException 500 if the database connection fails
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            try {
                $dsn = sprintf(
                    "mysql:host=%s;dbname=%s;charset=utf8mb4",
                    $_ENV["DB_HOST"],
                    $_ENV["DB_NAME"],
                );

                self::$instance = new PDO(
                    $dsn,
                    $_ENV["DB_USER"],
                    $_ENV["DB_PASSWORD"],
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ],
                );
            } catch (PDOException $e) {
                error_log("DB connection error: " . $e->getMessage());
                throw new HttpException(500, "Erreur interne du serveur");
            }
        }
        return self::$instance;
    }
}
