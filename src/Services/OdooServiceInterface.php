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
     * Returns all variants (product.product) for a given template id, enriched
     * with dimension/material and a MariaDB-ready variant_ref.
     *
     * @param  int         $templateId          Odoo product.template id
     * @param  string|bool $templateDefaultCode Odoo default_code of the parent template (false when not set)
     * @return array<int, array{id: int, variant_ref: string, display_name: string, lst_price: float, dimension: string|null, material: string|null}> Variant rows
     */
    public function getVariantsForTemplate(
        int $templateId,
        string|bool $templateDefaultCode,
    ): array;

    // --- CRON: order status sync ---

    /**
     * Returns the current status of an Odoo sale order.
     *
     * @param  int   $odooOrderId Odoo sale.order id
     * @return array Order status data
     */
    public function getOrderStatus(int $odooOrderId): array;

    // --- Direct calls: triggered by user action ---

    /**
     * Registers a new newsletter subscriber in Odoo.
     *
     * @param  string $email Subscriber email address
     */
    public function addToNewsletter(string $email): void;

    /**
     * Remove a newsletter subscriber in Odoo.
     *
     * @param  string $email Subscriber email address
     */
    public function removeFromNewsletter(string $email): void;

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
