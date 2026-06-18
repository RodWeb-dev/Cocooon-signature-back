<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

use App\Core\DBConnection;
use App\Services\OdooFactory;

$odoo = OdooFactory::create();
$subcategories = $odoo->getSubCategories();

$db = DBConnection::getInstance();

$stmt = $db->prepare(
    'INSERT INTO subcategories (id, name, synced_at)
     VALUES (:id, :name, NOW())
     ON DUPLICATE KEY UPDATE name = :name2, synced_at = NOW()'
);

$count = 0;

foreach ($subcategories as $subcategory) {
    $stmt->execute([
        'id' => (int) $subcategory['value'],
        'name' => $subcategory['name'],
        'name2' => $subcategory['name'],
    ]);
    $count++;
}

echo "Synchro subcatégories terminée : {$count} subcatégorie(s) traitée(s).\n";
