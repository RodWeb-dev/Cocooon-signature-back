<?php

declare(strict_types=1);

require __DIR__ . "/../vendor/autoload.php";

use App\Core\DBConnection;
use App\Services\OdooFactory;

$odoo = OdooFactory::create();
$db = DBConnection::getInstance();

// Chargement des matières en mémoire (3 lignes max)
$materialsMap = [];
$rows = $db
    ->query("SELECT id, name FROM materials")
    ->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    $materialsMap[$row["name"]] = (int) $row["id"];
}

$stmt = $db->prepare(
    'INSERT INTO product_variants (product_ref, variant_ref, odoo_variant_id, dimension, material_id, base_price, synced_at)
     VALUES (:product_ref, :variant_ref, :odoo_variant_id, :dimension, :material_id, :base_price, NOW())
     ON DUPLICATE KEY UPDATE
         odoo_variant_id = :odoo_variant_id2,
         base_price      = :base_price2,
         synced_at       = NOW()',
);

$templates = $odoo->getProducts();
$count = 0;
$errors = 0;

foreach ($templates as $template) {
    $templateRef = buildTemplateRef($template);
    $variants = $odoo->getVariantsForTemplate(
        (int) $template["id"],
        $template["default_code"],
    );

    foreach ($variants as $variant) {
        [$dimension, $materialName] = parseDisplayName(
            $variant["display_name"],
        );

        if (!isset($materialsMap[$materialName])) {
            echo "Matière inconnue '{$materialName}' pour variante {$variant["id"]} — ignorée.\n";
            $errors++;
            continue;
        }

        $variantRef = $templateRef . "-" . $variant["id"];

        $stmt->execute([
            "product_ref" => $templateRef,
            "variant_ref" => $variantRef,
            "odoo_variant_id" => $variant["id"],
            "dimension" => $dimension,
            "material_id" => $materialsMap[$materialName],
            "base_price" => $variant["lst_price"],
            "odoo_variant_id2" => $variant["id"],
            "base_price2" => $variant["lst_price"],
        ]);

        $count++;
    }
}

echo "Synchro variantes terminée : {$count} variante(s) traitée(s), {$errors} erreur(s).\n";

// ---------------------------------------------------------------------------

function buildTemplateRef(array $template): string
{
    if ($template["default_code"]) {
        return $template["default_code"];
    }
    return "TMPL-" . $template["id"];
}

function parseDisplayName(string $displayName): array
{
    // Extrait la partie entre parenthèses : "Cadres N°520 (40 x 50, Chêne)" → "40 x 50, Chêne"
    preg_match("/\(([^)]+)\)/", $displayName, $matches);

    if (empty($matches[1])) {
        return [null, ""];
    }

    $content = $matches[1];

    // Si contient une virgule → dimension + matière
    if (str_contains($content, ",")) {
        [$dimension, $material] = explode(",", $content, 2);
        return [trim($dimension), trim($material)];
    }

    // Sinon → matière seule, pas de dimension
    return [null, trim($content)];
}
