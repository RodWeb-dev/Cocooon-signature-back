<?php

declare(strict_types=1);

namespace App\Services;

interface OdooServiceInterface
{
    // --- CRON : synchro catalogue (1h du matin) ---

    /** Récupère toutes les catégories produit */
    public function getCategories(): array;

    /** Récupère toutes les sous-catégories produit */
    public function getSubCategories(): array;

    /** Récupère le catalogue complet : templates + variantes + prix + matières */
    public function getProducts(): array;

    // --- CRON : suivi des commandes ---

    /** Récupère le statut actuel d'une commande Odoo (Corinne pilote depuis Odoo) */
    public function getOrderStatus(int $odooOrderId): array;

    // --- CRON : newsletter ---

    /** Transmet un nouvel inscrit newsletter vers Odoo */
    public function addToNewsletter(string $email): bool;

    // --- Accès direct : déclenché par une action utilisateur ---

    /** Envoie une commande validée à Odoo après paiement confirmé */
    public function createOrder(array $orderData): int;

    /** Confirme/notifie un paiement reçu côté Odoo */
    public function confirmPayment(int $odooOrderId, array $paymentData): bool;
}
