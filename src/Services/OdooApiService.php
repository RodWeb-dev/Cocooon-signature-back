<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\OdooConnection;

/**
 * Live Odoo implementation of OdooServiceInterface using JSON-RPC (via OdooConnection).
 *
 * getCategories, getSubCategories, getProducts, getVariantsForTemplate,
 * addToNewsletter and removeFromNewsletter are implemented. getOrderStatus,
 * createOrder and confirmPayment remain empty stubs.
 */
class OdooApiService implements OdooServiceInterface
{
    /** Odoo x_categorie selection value for "Services" — not a real product category, excluded from site display */
    private const EXCLUDED_CATEGORY_ID = 7;
    /** Odoo categ_id selection for catalog - products to display on website */
    private const CATALOG_CATEGORY_ID = 7;
    private const DIMENSION_ATTRIBUTE_ID = 9;
    private const MATERIAL_ATTRIBUTE_ID = 10;
    private const NEWSLETTER_LIST_ID = 1;

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
     * Fetches all product templates from Odoo.
     *
     * @return array<int, array{id: int, name: string, default_code: string|false, list_price: float, x_categorie: string|false, x_souscategorie: string|false}>
     */
    public function getProducts(): array
    {
        return $this->fetchTemplates();
    }

    /**
     * Fetches and enriches all variants for a given template, resolving
     * attribute values into dimension/material and building the variant_ref.
     *
     * @param  int         $templateId          Odoo product.template id
     * @param  string|bool $templateDefaultCode Odoo default_code of the parent template (false when not set)
     * @return array<int, array{id: int, variant_ref: string, display_name: string, lst_price: float, dimension: string|null, material: string|null}>
     */
    public function getVariantsForTemplate(
        int $templateId,
        string|bool $templateDefaultCode,
    ): array {
        $rawVariants = $this->fetchVariantsForTemplate($templateId);
        $variants = [];

        foreach ($rawVariants as $row) {
            $attributes = $this->resolveAttributeValues(
                $row["product_template_variant_value_ids"],
            );
            $variantRef = $this->buildVariantRef(
                $templateDefaultCode,
                $templateId,
                $row["id"],
            );
            $variants[] = [
                "id" => $row["id"],
                "variant_ref" => $variantRef,
                "display_name" => $row["display_name"],
                "lst_price" => $row["lst_price"],
                ...$attributes,
            ];
        }

        return $variants;
    }

    /**
     * Executes a search_read on product.template to retrieve catalogue fields.
     *
     * Fields: id, name, default_code, list_price, x_categorie, x_souscategorie.
     *
     * @return array<int, array> Raw Odoo template rows
     */
    private function fetchTemplates(): array
    {
        $args = [[["categ_id", "=", self::CATALOG_CATEGORY_ID]]];
        $fields = [
            "id",
            "name",
            "default_code",
            "list_price",
            "x_categorie",
            "x_souscategorie",
        ];

        $result = OdooConnection::getInstance()->executeKw(
            "product.template",
            "search_read",
            $args,
            ["fields" => $fields],
        );

        return $result;
    }

    /**
     * Executes a search_read on product.product filtered by product_tmpl_id.
     *
     * @param  int   $templateId Odoo product.template id
     * @return array<int, array> Raw Odoo variant rows
     */
    private function fetchVariantsForTemplate(int $templateId): array
    {
        $args = [[["product_tmpl_id", "=", $templateId]]];
        $fields = [
            "id",
            "default_code",
            "lst_price",
            "product_template_variant_value_ids",
            "display_name",
        ];

        $result = OdooConnection::getInstance()->executeKw(
            "product.product",
            "search_read",
            $args,
            ["fields" => $fields],
        );

        return $result;
    }

    /**
     * Resolves attribute_value_ids on a variant to human-readable dimension and material labels.
     *
     * @param  int[] $attributeValueIds Odoo product.template.attribute.value ids
     * @return array{dimension: string|null, material: string|null}
     */
    private function resolveAttributeValues(array $attributeValueIds): array
    {
        if ($attributeValueIds === []) {
            return ["dimension" => null, "material" => null];
        }

        $args = [[["id", "in", $attributeValueIds]]];
        $fields = ["id", "name", "attribute_id"];

        $results = OdooConnection::getInstance()->executeKw(
            "product.template.attribute.value",
            "search_read",
            $args,
            ["fields" => $fields],
        );

        $dimension = null;
        $material = null;

        foreach ($results as $result) {
            if ($result["attribute_id"][0] === self::DIMENSION_ATTRIBUTE_ID) {
                $dimension = $result["name"];
            }
            if ($result["attribute_id"][0] === self::MATERIAL_ATTRIBUTE_ID) {
                $material = $result["name"];
            }
        }

        return ["dimension" => $dimension, "material" => $material];
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
     */
    public function addToNewsletter(string $email): void
    {
        $contacts = $this->findMailingContactByEmail($email);

        foreach ($contacts as $contact) {
            if (in_array(self::NEWSLETTER_LIST_ID, $contact["list_ids"])) {
                return;
            }
        }

        $args = [
            [
                [
                    "email" => $email,
                    "list_ids" => [[4, self::NEWSLETTER_LIST_ID, 0]],
                ],
            ],
        ];

        OdooConnection::getInstance()->executeKw(
            "mailing.contact",
            "create",
            $args,
        );
    }

    /**
     * Returns the mailing.contact rows matching an email, or null if none exist.
     *
     * @param  string $email Subscriber email address
     * @return array<int, array{id: int, list_ids: int[]}>|null
     */
    private function findMailingContactByEmail(string $email): ?array
    {
        $args = [[["email", "=", $email]]];
        $fields = ["id", "list_ids"];

        $result = OdooConnection::getInstance()->executeKw(
            "mailing.contact",
            "search_read",
            $args,
            ["fields" => $fields],
        );

        if (empty($result)) {
            return null;
        }

        return $result;
    }

    /**
     * Removes an email from the newsletter mailing list in Odoo.
     *
     * No-op if the email has no mailing.contact row on the newsletter list.
     *
     * @param  string $email Subscriber email address
     */
    public function removeFromNewsletter(string $email): void
    {
        $contacts = $this->findMailingContactByEmail($email);

        $ids = [];
        foreach ($contacts as $contact) {
            if (in_array(self::NEWSLETTER_LIST_ID, $contact["list_ids"])) {
                $ids[] = $contact["id"];
            }
        }

        if (empty($ids)) {
            return;
        }

        $args = [$ids, ["list_ids" => [[3, self::NEWSLETTER_LIST_ID, 0]]]];

        OdooConnection::getInstance()->executeKw(
            "mailing.contact",
            "write",
            $args,
        );
    }
}
