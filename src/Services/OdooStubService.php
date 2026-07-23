<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Hard-coded stub implementation of OdooServiceInterface for local development.
 *
 * Uses real catalogue data from Corinne's Odoo instance (Plateau, Cadres N°520)
 * so Cron scripts and controllers can be exercised without an Odoo connection.
 */
class OdooStubService implements OdooServiceInterface
{
    /**
     * Returns a fixed list of product categories matching the Odoo production set.
     *
     * @return array<int, array{id: string, name: string}>
     */
    public function getCategories(): array
    {
        return [
            ["id" => "1", "name" => "Mobilier intérieur"],
            ["id" => "2", "name" => "Mobilier extérieur"],
            ["id" => "3", "name" => "Luminaire"],
            ["id" => "4", "name" => "Déco"],
            ["id" => "5", "name" => "Art de la table"],
            ["id" => "6", "name" => "Jeux"],
        ];
    }

    /**
     * Returns a fixed list of product subcategories matching the Odoo production set.
     *
     * @return array<int, array{id: string, name: string}>
     */
    public function getSubCategories(): array
    {
        return [
            ["id" => "11", "name" => "Table basse"],
            ["id" => "12", "name" => "Meuble TV"],
            ["id" => "13", "name" => "Fauteuil"],
            ["id" => "14", "name" => "Canapé"],
            ["id" => "15", "name" => "Pouf"],
            ["id" => "21", "name" => "Bureau"],
            ["id" => "22", "name" => "Bibliothèque"],
            ["id" => "23", "name" => "Etagère"],
            ["id" => "31", "name" => "Table"],
            ["id" => "32", "name" => "Chaise"],
            ["id" => "33", "name" => "Buffet"],
            ["id" => "34", "name" => "Vaisselier"],
            ["id" => "35", "name" => "Banc"],
            ["id" => "41", "name" => "Console"],
            ["id" => "42", "name" => "Armoire"],
            ["id" => "43", "name" => "Meuble à chaussure"],
            ["id" => "44", "name" => "Porte manteau"],
            ["id" => "51", "name" => "Ilot"],
            ["id" => "52", "name" => "Tabouret de bar"],
            ["id" => "53", "name" => "Façade de cuisine"],
            ["id" => "61", "name" => "Lit"],
            ["id" => "62", "name" => "Chevet"],
            ["id" => "63", "name" => "Commode"],
            ["id" => "64", "name" => "Armoire"],
            ["id" => "65", "name" => "Coiffeuse"],
            ["id" => "66", "name" => "Bout de lit"],
            ["id" => "67", "name" => "Penderie"],
            ["id" => "71", "name" => "Meuble sous vasque"],
            ["id" => "72", "name" => "Tabouret"],
            ["id" => "73", "name" => "Mirroir"],
            ["id" => "74", "name" => "Cadre"],
            ["id" => "75", "name" => "Planche / Plateau"],
            ["id" => "81", "name" => "Table de jardin"],
            ["id" => "82", "name" => "Chaise de jardin"],
            ["id" => "83", "name" => "Banc de jardin"],
            ["id" => "84", "name" => "Transat"],
            ["id" => "85", "name" => "Coffre de rangement"],
            ["id" => "86", "name" => "Balancelle"],
            ["id" => "91", "name" => "Lampe à poser"],
            ["id" => "92", "name" => "Lampadaire"],
            ["id" => "93", "name" => "Lampe sol plafond"],
        ];
    }

    /**
     * Returns two real product templates from Corinne's catalogue (Plateau, Cadres N°520).
     *
     * @return array<int, array{id: int, name: string, default_code: string|false, list_price: float, x_categorie: string|false, x_souscategorie: string|false}>
     */
    public function getProducts(): array
    {
        return [
            [
                "id" => 26,
                "name" => "Plateau",
                "default_code" => "D-PLA-",
                "list_price" => 116.67,
                "x_categorie" => "4",
                "x_souscategorie" => "75",
            ],
            [
                "id" => 17,
                "name" => "Cadres N°520",
                "default_code" => false,
                "list_price" => 75.0,
                "x_categorie" => "4",
                "x_souscategorie" => "74",
            ],
        ];
    }

    /**
     * Returns stub variants for Plateau (id 26) and Cadres N°520 (id 17).
     *
     * display_name format: "Product name (dimension, Material)" or "Product name (Material)".
     *
     * @param  int         $templateId          Odoo product.template id
     * @param  string|bool $templateDefaultCode Unused in the stub (kept for interface compatibility)
     * @return array<int, array{id: int, display_name: string, lst_price: float}> Empty array for unknown template ids
     */
    public function getVariantsForTemplate(
        int $templateId,
        string|bool $templateDefaultCode,
    ): array {
        $variants = [
            // Plateau (id: 26)
            26 => [
                [
                    "id" => 50,
                    "display_name" => "Plateau (Chêne)",
                    "lst_price" => 116.67,
                ],
                [
                    "id" => 51,
                    "display_name" => "Plateau (Noyer)",
                    "lst_price" => 116.67,
                ],
                [
                    "id" => 52,
                    "display_name" => "Plateau (Frêne)",
                    "lst_price" => 116.67,
                ],
            ],
            // Cadres N°520 (id: 17)
            17 => [
                [
                    "id" => 35,
                    "display_name" => "Cadres N°520 (40 x 50, Chêne)",
                    "lst_price" => 116.67,
                ],
                [
                    "id" => 39,
                    "display_name" => "Cadres N°520 (50 x 70, Noyer)",
                    "lst_price" => 150.0,
                ],
                [
                    "id" => 43,
                    "display_name" => "Cadres N°520 (70 x 100, Frêne)",
                    "lst_price" => 191.67,
                ],
            ],
        ];

        return $variants[$templateId] ?? [];
    }

    /**
     * Not yet stubbed — returns an empty array.
     *
     * @param  int   $odooOrderId Odoo sale.order id
     * @return array Empty until stub data is defined
     */
    public function getOrderStatus(int $odooOrderId): array
    {
        // TODO
        return [];
    }

    /**
     * Not yet stubbed.
     *
     * @param  string $email Subscriber email address
     */
    public function addToNewsletter(string $email): void
    {
        // TODO
        return;
    }

    /**
     * Not yet stubbed.
     *
     * @param  string $email Subscriber email address
     */
    public function removeFromNewsletter(string $email): void
    {
        return;
    }

    /**
     * Not yet stubbed — always returns 0.
     *
     * @param  array $orderData Order payload
     * @return int   Always 0 in stub
     */
    public function createOrder(array $orderData): int
    {
        // TODO
        return 0;
    }

    /**
     * Not yet stubbed — always returns true.
     *
     * @param  int   $odooOrderId  Odoo sale.order id
     * @param  array $paymentData  Payment details
     * @return bool  Always true in stub
     */
    public function confirmPayment(int $odooOrderId, array $paymentData): bool
    {
        // TODO
        return true;
    }
}
