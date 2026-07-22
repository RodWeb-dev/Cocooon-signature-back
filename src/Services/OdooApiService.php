<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\OdooConnection;

/**
 * Live Odoo implementation of OdooServiceInterface using XML-RPC / JSON-RPC.
 *
 * All methods are structurally declared but not yet implemented — real API calls
 * are deferred until the Odoo catalogue is validated with Corinne.
 */
class OdooApiService implements OdooServiceInterface
{
    /** Odoo x_categorie selection value for "Services" — not a real product category, excluded from site display */
    private const EXCLUDED_CATEGORY_ID = 7;

    /**
     * Fetches all product categories from Odoo via search_read on product.category.
     *
     * @return array<int, array{id: int, name: string}>
     */
    public function getCategories(): array
    {
        $result = OdooConnection::getInstance()->executeKw(
            "product.template",
            "fields_get",
            [["x_categorie"]],
            ["attributes" => ["string", "selection"]],
        );

        $selection = $result["x_categorie"]["selection"];

        $categories = [];
        foreach ($selection as $pair) {
            $categories[] = [
                "id" => (int) $pair[0],
                "name" => $pair[1],
            ];
        }

        $categories = array_values(
            array_filter(
                $categories,
                fn(array $category): bool => $category["id"] !==
                    self::EXCLUDED_CATEGORY_ID,
            ),
        );

        return $categories;
    }

    /**
     * Fetches all product subcategories from Odoo via search_read on product.category.
     *
     * @return array<int, array{id: int, name: string}>
     */
    public function getSubCategories(): array
    {
        $result = OdooConnection::getInstance()->executeKw(
            "product.template",
            "fields_get",
            [["x_souscategorie"]],
            ["attributes" => ["string", "selection"]],
        );

        $selection = $result["x_souscategorie"]["selection"];

        $subcategories = [];
        foreach ($selection as $pair) {
            $subcategories[] = [
                "id" => (int) $pair[0],
                "name" => $pair[1],
            ];
        }

        return $subcategories;
    }

    /**
     * Fetches all product templates from Odoo and attaches their variants.
     *
     * @return array<int, array{id: int, name: string, default_code: string|false, list_price: float, x_categorie: string|false, x_souscategorie: string|false, variants: array}>
     */
    public function getProducts(): array
    {
        $templates = $this->fetchTemplates();

        foreach ($templates as &$template) {
            $template["variants"] = $this->fetchVariantsForTemplate(
                $template["id"],
            );
        }

        return $templates;
    }

    /**
     * Fetches all variants (product.product) for a given template id from Odoo.
     *
     * @param  int   $templateId Odoo product.template id
     * @return array<int, array{id: int, display_name: string, lst_price: float}>
     */
    public function getVariantsForTemplate(int $templateId): array {}

    /**
     * Executes a search_read on product.template to retrieve catalogue fields.
     *
     * Fields: id, name, default_code, list_price, x_categorie, x_souscategorie, product_variant_ids.
     *
     * @return array<int, array> Raw Odoo template rows
     */
    private function fetchTemplates(): array
    {
        // search_read on product.template
    }

    /**
     * Executes a search_read on product.product filtered by product_tmpl_id.
     *
     * @param  int   $templateId Odoo product.template id
     * @return array<int, array> Raw Odoo variant rows
     */
    private function fetchVariantsForTemplate(int $templateId): array
    {
        // search_read on product.product, domain [['product_tmpl_id', '=', $templateId]]
    }

    /**
     * Resolves attribute_value_ids on a variant to human-readable dimension and material labels.
     *
     * @param  int[] $attributeValueIds Odoo product.template.attribute.value ids
     * @return array{dimension: string|null, material: string|null}
     */
    private function resolveAttributeValues(array $attributeValueIds): array
    {
        // search_read on product.template.attribute.value
    }

    /**
     * Builds the unique MariaDB variant_ref from the template default_code and the variant id.
     *
     * Falls back to "TMPL-{templateId}-{variantId}" when default_code is absent.
     *
     * @param  string|bool $templateDefaultCode Odoo default_code (false when not set)
     * @param  int         $templateId          Odoo product.template id
     * @param  int         $variantId           Odoo product.product id
     * @return string
     */
    private function buildVariantRef(
        string|bool $templateDefaultCode,
        int $templateId,
        int $variantId,
    ): string {
        if ($templateDefaultCode) {
            return $templateDefaultCode . "-" . $variantId;
        }
        return "TMPL-" . $templateId . "-" . $variantId;
    }

    /**
     * Returns the current status of an Odoo sale order.
     *
     * @param  int   $odooOrderId Odoo sale.order id
     * @return array Order status data
     */
    public function getOrderStatus(int $odooOrderId): array {}

    /**
     * Registers a newsletter subscriber in Odoo.
     *
     * @param  string $email Subscriber email address
     * @return bool   True on success
     */
    public function addToNewsletter(string $email): bool {}

    /**
     * Creates a sale order in Odoo after payment confirmation.
     *
     * @param  array $orderData Order payload (items, address, totals)
     * @return int   Odoo sale.order id
     */
    public function createOrder(array $orderData): int {}

    /**
     * Notifies Odoo of a confirmed payment.
     *
     * @param  int   $odooOrderId  Odoo sale.order id
     * @param  array $paymentData  Payment details (amount, method, reference)
     * @return bool  True on success
     */
    public function confirmPayment(
        int $odooOrderId,
        array $paymentData,
    ): bool {}
}
