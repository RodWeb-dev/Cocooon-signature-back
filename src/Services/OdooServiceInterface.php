<?php

declare(strict_types=1);

namespace App\Services;

interface OdooServiceInterface
{
    // --- CRON: catalogue sync (runs nightly) ---

    /**
     * Returns all product categories from Odoo.
     *
     * @return array<int, array{id: int, name: string}> Category rows
     */
    public function getCategories(): array;

    /**
     * Returns all product subcategories from Odoo.
     *
     * @return array<int, array{id: int, name: string}> Subcategory rows
     */
    public function getSubCategories(): array;

    /**
     * Returns all product templates from Odoo with their base price and category fields.
     *
     * @return array<int, array{id: int, name: string, default_code: string|false, list_price: float, x_categorie: string|false, x_souscategorie: string|false}> Template rows
     */
    public function getProducts(): array;

    /**
     * Returns all variants (product.product) for a given template id.
     *
     * @param  int   $templateId Odoo product.template id
     * @return array<int, array{id: int, display_name: string, lst_price: float}> Variant rows
     */
    public function getVariantsForTemplate(int $templateId): array;

    // --- CRON: order status sync ---

    /**
     * Returns the current status of an Odoo sale order.
     *
     * @param  int   $odooOrderId Odoo sale.order id
     * @return array Order status data
     */
    public function getOrderStatus(int $odooOrderId): array;

    // --- CRON: newsletter sync ---

    /**
     * Registers a new newsletter subscriber in Odoo.
     *
     * @param  string $email Subscriber email address
     * @return bool   True on success
     */
    public function addToNewsletter(string $email): bool;

    // --- Direct calls: triggered by user actions ---

    /**
     * Pushes a validated order to Odoo after payment confirmation.
     *
     * @param  array $orderData Order payload (items, address, totals)
     * @return int   Odoo sale.order id
     */
    public function createOrder(array $orderData): int;

    /**
     * Notifies Odoo of a confirmed payment for an existing order.
     *
     * @param  int   $odooOrderId  Odoo sale.order id
     * @param  array $paymentData  Payment details (amount, method, reference)
     * @return bool  True on success
     */
    public function confirmPayment(int $odooOrderId, array $paymentData): bool;
}
