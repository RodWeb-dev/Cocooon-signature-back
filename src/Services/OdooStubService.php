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
        // TODO : en attente de validation du système de prix avec Corinne
        return [];
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
