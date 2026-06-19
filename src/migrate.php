<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use App\Core\DBConnection;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ );
$dotenv->load();

$pdo = DBConnection::getInstance();

$pdo->exec("
    CREATE TABLE IF NOT EXISTS migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        filename VARCHAR(255) NOT NULL UNIQUE,
        executed_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
");

$stmt = $pdo->prepare("SELECT filename FROM migrations");
$stmt->execute();
$completed = $stmt->fetchAll(PDO::FETCH_COLUMN);

$migrations = glob(__DIR__ . '/migrations/*.sql');
sort($migrations);

$count = 0;
foreach ($migrations as $migration) {
    $filename = basename($migration);

    if (!in_array($filename, $completed)) {
        $content = file_get_contents($migration);
        $pdo->exec($content);

        $stmt = $pdo->prepare("INSERT INTO migrations (filename) VALUES (?)");
        $stmt->execute([$filename]);
        $count++;
    }
}

$alreadyDone = count($completed);
echo "Migrations exécutées : $count\r\nMigrations déjà à jour : $alreadyDone\r\n";
