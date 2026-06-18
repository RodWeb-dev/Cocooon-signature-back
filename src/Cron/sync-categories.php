<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

use App\Core\DBConnection;
use App\Services\OdooFactory;

$odoo = OdooFactory::create();
$categories = $odoo->getCategories();

$db = DBConnection::getInstance();

$stmt = $db->prepare(
    'INSERT INTO categories (id, name, synced_at)
     VALUES (:id, :name, NOW())
     ON DUPLICATE KEY UPDATE name = :name2, synced_at = NOW()'
);

$count = 0;

foreach ($categories as $category) {
    $stmt->execute([
        'id' => (int) $category['value'],
        'name' => $category['name'],
        'name2' => $category['name'],
    ]);
    $count++;
}

echo "Synchro catégories terminée : {$count} catégorie(s) traitée(s).\n";
