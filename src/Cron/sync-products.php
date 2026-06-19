<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\DBConnection;
use App\Services\OdooFactory;

$odoo = OdooFactory::create();
$db = DBConnection::getInstance();

$templates = $odoo->getProducts();

$stmt = $db->prepare(
    'INSERT INTO products (ref, slug, name, price, category_id, subcategory_id, synced_at)
     VALUES (:ref, :slug, :name, :price, :category_id, :subcategory_id, NOW())
     ON DUPLICATE KEY UPDATE
         name = :name2,
         price = :price2,
         category_id = :category_id2,
         subcategory_id = :subcategory_id2,
         synced_at = NOW()'
);

$count = 0;

foreach ($templates as $template) {
    $ref = buildRef($template);
    $slug = buildSlug($template['name']);

    $stmt->execute([
        'ref'            => $ref,
        'slug'           => $slug,
        'name'           => $template['name'],
        'price'          => $template['list_price'],
        'category_id'    => $template['x_categorie'] ?: null,
        'subcategory_id' => $template['x_souscategorie'] ?: null,
        'name2'          => $template['name'],
        'price2'         => $template['list_price'],
        'category_id2'   => $template['x_categorie'] ?: null,
        'subcategory_id2'=> $template['x_souscategorie'] ?: null,
    ]);

    $count++;
}

echo "Synchro produits terminée : {$count} produit(s) traité(s).\n";

// ---------------------------------------------------------------------------

function buildRef(array $template): string
{
    $defaultCode = $template['default_code'];
    $templateId  = $template['id'];

    if ($defaultCode) {
        return $defaultCode;
    }

    return 'TMPL-' . $templateId;
}

function buildSlug(string $name): string
{
    // Remplacement du signe degré (°) par "numero-"
    $slug = str_replace('N°', 'numero-', $name);

    // Translittération des accents
    $slug = transliterator_transliterate('Any-Latin; Latin-ASCII', $slug);

    // Lowercase
    $slug = strtolower($slug);

    // Remplacement des espaces et caractères non alphanumériques par des tirets
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);

    // Suppression des tirets en début et fin
    $slug = trim($slug, '-');

    return $slug;
}
