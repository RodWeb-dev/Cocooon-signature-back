<?php

declare(strict_types=1);

namespace App\Services;

class OdooStubService implements OdooServiceInterface
{
    public function getCategories(): array
    {
        return [
            ['value' => '1', 'name' => 'Mobilier intérieur'],
            ['value' => '2', 'name' => 'Mobilier extérieur'],
            ['value' => '3', 'name' => 'Luminaire'],
            ['value' => '4', 'name' => 'Déco'],
            ['value' => '5', 'name' => 'Art de la table'],
            ['value' => '6', 'name' => 'Jeux'],
        ];
    }

    public function getSubCategories(): array
    {
        return [
            ['value' => '11', 'name' => 'Table basse'],
            ['value' => '12', 'name' => 'Meuble TV'],
            ['value' => '13', 'name' => 'Fauteuil'],
            ['value' => '14', 'name' => 'Canapé'],
            ['value' => '15', 'name' => 'Pouf'],
            ['value' => '21', 'name' => 'Bureau'],
            ['value' => '22', 'name' => 'Bibliothèque'],
            ['value' => '23', 'name' => 'Etagère'],
            ['value' => '31', 'name' => 'Table'],
            ['value' => '32', 'name' => 'Chaise'],
            ['value' => '33', 'name' => 'Buffet'],
            ['value' => '34', 'name' => 'Vaisselier'],
            ['value' => '35', 'name' => 'Banc'],
            ['value' => '41', 'name' => 'Console'],
            ['value' => '42', 'name' => 'Armoire'],
            ['value' => '43', 'name' => 'Meuble à chaussure'],
            ['value' => '44', 'name' => 'Porte manteau'],
            ['value' => '51', 'name' => 'Ilot'],
            ['value' => '52', 'name' => 'Tabouret de bar'],
            ['value' => '53', 'name' => 'Façade de cuisine'],
            ['value' => '61', 'name' => 'Lit'],
            ['value' => '62', 'name' => 'Chevet'],
            ['value' => '63', 'name' => 'Commode'],
            ['value' => '64', 'name' => 'Armoire'],
            ['value' => '65', 'name' => 'Coiffeuse'],
            ['value' => '66', 'name' => 'Bout de lit'],
            ['value' => '67', 'name' => 'Penderie'],
            ['value' => '71', 'name' => 'Meuble sous vasque'],
            ['value' => '72', 'name' => 'Tabouret'],
            ['value' => '73', 'name' => 'Mirroir'],
            ['value' => '74', 'name' => 'Cadre'],
            ['value' => '75', 'name' => 'Planche / Plateau'],
            ['value' => '81', 'name' => 'Table de jardin'],
            ['value' => '82', 'name' => 'Chaise de jardin'],
            ['value' => '83', 'name' => 'Banc de jardin'],
            ['value' => '84', 'name' => 'Transat'],
            ['value' => '85', 'name' => 'Coffre de rangement'],
            ['value' => '86', 'name' => 'Balancelle'],
            ['value' => '91', 'name' => 'Lampe à poser'],
            ['value' => '92', 'name' => 'Lampadaire'],
            ['value' => '93', 'name' => 'Lampe sol plafond'],
        ];
    }

    public function getProducts(): array
    {
        return [
            [
                'id'               => 26,
                'name'             => 'Plateau',
                'default_code'     => 'D-PLA-',
                'list_price'       => 116.67,
                'x_categorie'      => '4',
                'x_souscategorie'  => '75',
                'product_variant_ids' => [50, 51, 52],
            ],
            [
                'id'               => 17,
                'name'             => 'Cadres N°520',
                'default_code'     => false,
                'list_price'       => 75.0,
                'x_categorie'      => '4',
                'x_souscategorie'  => '74',
                'product_variant_ids' => [35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48, 49],
            ],
        ];
    }

    public function getVariantsForTemplate(int $templateId): array
    {
        $variants = [
            // Plateau (id: 26)
            26 => [
                ['id' => 50, 'display_name' => 'Plateau (Chêne)',  'lst_price' => 116.67],
                ['id' => 51, 'display_name' => 'Plateau (Noyer)',  'lst_price' => 116.67],
                ['id' => 52, 'display_name' => 'Plateau (Frêne)',  'lst_price' => 116.67],
            ],
            // Cadres N°520 (id: 17)
            17 => [
                ['id' => 35, 'display_name' => 'Cadres N°520 (40 x 50, Chêne)',  'lst_price' => 116.67],
                ['id' => 39, 'display_name' => 'Cadres N°520 (50 x 70, Noyer)',  'lst_price' => 150.0],
                ['id' => 43, 'display_name' => 'Cadres N°520 (70 x 100, Frêne)', 'lst_price' => 191.67],
            ],
        ];
    
        return $variants[$templateId] ?? [];
    }

    public function getOrderStatus(int $odooOrderId): array
    {
        // TODO
        return [];
    }

    public function addToNewsletter(string $email): bool
    {
        // TODO
        return true;
    }

    public function createOrder(array $orderData): int
    {
        // TODO
        return 0;
    }

    public function confirmPayment(int $odooOrderId, array $paymentData): bool
    {
        // TODO
        return true;
    }
}
