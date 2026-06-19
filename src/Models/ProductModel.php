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
    public static function findAll(array $filters = []): array
    {
        $sql = "SELECT * FROM products";
        
        $allowed = ['collection_id', 'category_id', 'subcategory_id'];
        $whereClauses = [];
        $params = [];
        if (!empty($filters)) {
            foreach ($filters as $key => $value) {
                if (!in_array($key, $allowed)) {
                    continue;
                }
                $whereClauses[] = "$key = :$key";
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

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Returns a product row by slug, or null if not found. */
    public static function findBySlug(string $slug): ?array
    {
        $sql = "SELECT * FROM products WHERE slug = :slug";

        $stmt = self::getDb()->prepare($sql);
        $stmt->bindValue(':slug', $slug, PDO::PARAM_STR);
        $stmt->execute();
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        return $product ?: null;
    }

    /** Returns all reviews for a product, joined with reviewer firstname and lastname. */
    public static function getReviews(string $slug): array
    {
        $sql = "
            SELECT r.*, u.firstname, u.lastname
            FROM reviews AS r
            JOIN users AS u ON r.owner = u.id
            JOIN products AS p ON r.ref = p.ref
            WHERE p.slug = :slug
        ";

        $stmt = self::getDb()->prepare($sql);
        $stmt->bindValue(':slug', $slug, PDO::PARAM_STR);
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
}
