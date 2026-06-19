<?php

declare(strict_types=1);

namespace App\Services;

class OdooApiService implements OdooServiceInterface
{
    public function getCategories(): array
    {

    }

    public function getSubCategories(): array
    {

    }

    public function getProducts(): array
    {
        $templates = $this->fetchTemplates();

        foreach ($templates as &$template) {
            $template['variants'] = $this->fetchVariantsForTemplate($template['id']);
        }

        return $templates;
    }

    public function getVariantsForTemplate(int $templateId): array
    {
        
    }

    /** Récupère les product.template avec leurs champs catalogue de base */
    private function fetchTemplates(): array
    {
        // search_read sur product.template
    }

    /** Récupère les product.product (variantes) d'un template donné */
    private function fetchVariantsForTemplate(int $templateId): array
    {
        // search_read sur product.product, domain [['product_tmpl_id', '=', $templateId]]
    }

    /** Résout les attribute_value_ids d'une variante en libellés dimension/matière */
    private function resolveAttributeValues(array $attributeValueIds): array
    {
        // search_read sur product.template.attribute.value
    }

    /** Construit la ref MariaDB unique : default_code du template + id variante */
    private function buildVariantRef(string|bool $templateDefaultCode, int $templateId, int $variantId): string
    {
        if ($templateDefaultCode) {
            return $templateDefaultCode . '-' . $variantId;
        }
        return 'TMPL-' . $templateId . '-' . $variantId;
    }

    public function getOrderStatus(int $odooOrderId): array
    {

    }

    public function addToNewsletter(string $email): bool
    {

    }

    public function createOrder(array $orderData): int
    {

    }

    public function confirmPayment(int $odooOrderId, array $paymentData): bool
    {

    }
}
