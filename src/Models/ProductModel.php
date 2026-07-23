<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DBConnection;
use PDO;

/** PDO queries for the products, product_images and reviews tables. */
class ProductModel
{
    /** Returns the shared PDO connection. */
    private static function getDb(): PDO
    {
        return DBConnection::getInstance();
    }

    /** Returns all products, optionally filtered by collection_id, category_id or subcategory_id. */
    public static function findAll(array $filters = [], string $lang = 'fr'): array
    {
        $sql = "SELECT * FROM products AS p";
        if ($lang !== 'fr') {
            $sql = "SELECT p.ref, p.delay, p.stock, p.category_id, p.subcategory_id, p.collection_id,
                        COALESCE(t.slug, p.slug)               AS slug,
                        COALESCE(t.name, p.name)               AS name,
                        COALESCE(t.description, p.description) AS description,
                        COALESCE(t.materials, p.materials)     AS materials,
                        COALESCE(t.dimensions, p.dimensions)   AS dimensions
                    FROM products AS p
                    LEFT JOIN product_translations AS t ON t.product_ref = p.ref AND t.lang = :lang";
        }
        
        $allowed = ['collection_id', 'category_id', 'subcategory_id'];
        $whereClauses = [];
        $params = [];
        if (!empty($filters) ) {
            foreach ($filters as $key => $value) {
                if (!in_array($key, $allowed)) {
                    continue;
                }
                $whereClauses[] = "p.$key = :$key";
                $params[":$key"] = $value;
            }
            $sql .= ' WHERE ' . implode(' AND ', $whereClauses);
        }

        $stmt = self::getDb()->prepare($sql);

        if (!empty($filters)) {
            foreach ($params as $param => $value) {
                $stmt->bindValue($param, $value, PDO::PARAM_INT);
            }
        }
        if ($lang !== 'fr') {
            $stmt->bindValue(':lang', $lang, PDO::PARAM_STR);
        }

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Returns a product with its images by slug, or null if not found. */
    public static function findBySlug(string $slug, string $lang): ?array
    {
        $product = [];
        if ($lang !== 'fr') {
            $sql = "SELECT p.ref, p.delay, p.stock, p.category_id, p.subcategory_id, p.collection_id,
                        COALESCE(t.slug, p.slug)               AS slug,
                        COALESCE(t.name, p.name)               AS name,
                        COALESCE(t.description, p.description) AS description,
                        COALESCE(t.materials, p.materials)     AS materials,
                        COALESCE(t.dimensions, p.dimensions)   AS dimensions
                    FROM products AS p
                    INNER JOIN product_translations AS t ON t.product_ref = p.ref AND t.lang = :lang
                    WHERE t.slug = :slug";

            $stmt = self::getDb()->prepare($sql);
            $stmt->bindValue(':slug', $slug, PDO::PARAM_STR);
            $stmt->bindValue(':lang', $lang, PDO::PARAM_STR);
            $stmt->execute();
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        if (empty($product)) {
            $sql = "SELECT * FROM products WHERE slug = :slug";

            $stmt = self::getDb()->prepare($sql);
            $stmt->bindValue(':slug', $slug, PDO::PARAM_STR);
            $stmt->execute();
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        $sql = "SELECT pi.*
                FROM product_images AS pi
                JOIN products AS p ON pi.ref = p.ref
                WHERE p.ref = :ref
                ORDER BY pi.ref, pi.display_order";

        $stmt = self::getDb()->prepare($sql);
        $stmt->bindValue(':ref', $product['ref'], PDO::PARAM_STR);
        $stmt->execute();
        $product['images'] = $stmt->fetchAll(PDO::PARAM_STR);

        return $product ?: null;
    }

    /** Returns all reviews for a product, joined with reviewer firstname and lastname. */
    public static function getReviews(string $slug, string $lang): array
    {
        $ref = self::findRefBySlug($slug, $lang);
        $sql = "SELECT r.*, u.firstname, u.lastname
                FROM reviews AS r
                JOIN users AS u ON r.owner = u.id
                WHERE r.ref = :ref
        ";

        $stmt = self::getDb()->prepare($sql);
        $stmt->bindValue(':ref', $ref, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Inserts a review. Eligibility (delivered order) must be checked by the controller. */
    public static function addReview(string $ref, string $userId, int $rating, ?string $comment): void
    {
        $sql = "INSERT INTO reviews (ref, owner, rating, comment) VALUES (:ref, :owner, :rating, :comment)";

        $stmt = self::getDb()->prepare($sql);
        $stmt->bindValue(':ref', $ref, PDO::PARAM_STR);
        $stmt->bindValue(':owner', $userId, PDO::PARAM_STR);
        $stmt->bindValue(':rating', $rating, PDO::PARAM_INT);
        $stmt->bindValue(':comment', $comment, PDO::PARAM_STR);
        $stmt->execute();
    }

    /** Inserts or updates a product row on Odoo sync (INSERT … ON DUPLICATE KEY UPDATE on ref). */
    public static function upsert(array $data): void
    {
        if (empty($data)) {
            return;
        }

        $allowed = ['ref', 'name', 'slug', 'description', 'dimensions', 'materials', 'price', 'availability', 'stock', 'collection_id', 'category_id', 'subcategory_id'];
        $fields = [];
        $setParts = [];
        $values = [];
        $params = [];

        foreach ($allowed as $field) {
            if (isset($data[$field])) {
                $fields[] = $field;
                $setParts[] = "$field = :$field";
                $values[] = ":$field";
                $params[":$field"] = $data[$field];
            }
        }

        $sql = "
            INSERT INTO products (" . implode(', ', $fields) . ", synced_at)
            VALUES (" . implode(', ', $values) . ", :synced_at)
            ON DUPLICATE KEY UPDATE
                " . implode(', ', $setParts) . ", synced_at = :synced_at
        ";

        $stmt = self::getDb()->prepare($sql);
        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':synced_at', date('Y-m-d H:i:s'), PDO::PARAM_STR);
        $stmt->execute();
    }

    /** Returns a product row by Odoo ref, or null if not found. */
    public static function findByRef(string $ref): ?array
    {
        $sql = "SELECT * FROM products WHERE ref = :ref";

        $stmt = self::getDb()->prepare($sql);
        $stmt->bindValue(':ref', $ref, PDO::PARAM_STR);
        $stmt->execute();
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        return $product ?: null;
    }

    /** Resolves a product ref from its slug, checking the translated table first, then the default one. */
    private static function findRefBySlug(string $slug, string $lang): ?string
    {
        $ref = '';
        if ($lang !== 'fr') {
            $sql = "SELECT product_ref AS ref FROM product_translations
                    WHERE slug = :slug AND lang = :lang";

            $stmt = self::getDb()->prepare($sql);
            $stmt->bindValue(':slug', $slug, PDO::PARAM_STR);
            $stmt->bindValue(':lang', $lang, PDO::PARAM_STR);
            $stmt->execute();

            $ref = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        if (!$ref) {
             $sql = "SELECT ref FROM products
                    WHERE slug = :slug";

            $stmt = self::getDb()->prepare($sql);
            $stmt->bindValue(':slug', $slug, PDO::PARAM_STR);
            $stmt->execute();

            $ref = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        return $ref ? $ref['ref'] : null;
    }
}
