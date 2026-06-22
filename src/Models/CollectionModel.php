<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DBConnection;
use PDO;

/** PDO queries for the collections and collection_images tables. */
class CollectionModel
{
    /** Returns the shared PDO connection. */
    private static function getDb(): PDO
    {
        return DBConnection::getInstance();
    }

    /** Returns all collections with their images ordered by display_order. */
    public static function findAll(string $lang = 'fr') : array
    {
        $sql = "SELECT * FROM collections";

        if ($lang !== 'fr') {
            $sql = "SELECT c.id,
                        COALESCE(t.slug, c.slug)               AS slug,
                        COALESCE(t.name, c.name)               AS name,
                        COALESCE(t.description, c.description) AS description
                    FROM collections AS c
                    LEFT JOIN collection_translations AS t ON t.collection_id = c.id AND t.lang = :lang";
        }

        $stmt = self::getDb()->prepare($sql);
        if ($lang !== 'fr') {
            $stmt->bindValue(':lang', $lang, PDO::PARAM_STR);
        }
        $stmt->execute();

        $collections = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sql = "SELECT * FROM collection_images ORDER BY collection_id, display_order";
        $stmt = self::getDb()->prepare($sql);
        $stmt->execute();

        $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $imageByCollection = [];
        foreach($images as $image) {
            $imageByCollection[$image['collection_id']][] = $image;
        }

        foreach($collections as &$collection) {
            $collection['images'] = $imageByCollection[$collection['id']] ?? [];
        }

        return $collections;
    }

    /** Returns a collection with its images and its products (each with images), or null. */
    public static function findBySlug(string $slug, string $lang): ?array
    {
        $collectionImages = [];
        if ($lang !== 'fr') {
            $sql = "SELECT c.id, i.url, i.alt,
                        COALESCE(t.slug, c.slug)               AS slug,
                        COALESCE(t.name, c.name)               AS name,
                        COALESCE(t.description, c.description) AS description
                    FROM collections AS c
                    INNER JOIN collection_translations AS t ON t.collection_id = c.id AND t.lang = :lang
                    LEFT JOIN collection_images AS i ON i.collection_id = c.id
                    WHERE t.slug = :slug
                    ORDER BY i.display_order";

            $stmt = self::getDb()->prepare($sql);
            $stmt->bindValue(':slug', $slug, PDO::PARAM_STR);
            $stmt->bindValue(':lang', $lang, PDO::PARAM_STR);
            $stmt->execute();
            $collectionImages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        if (empty($collectionImages)) {
            $sql = "SELECT c.*, i.url, i.alt
                    FROM collections AS c
                    LEFT JOIN collection_images AS i ON i.collection_id = c.id
                    WHERE slug = :slug
                    ORDER BY i.display_order";

            $stmt = self::getDb()->prepare($sql);
            $stmt->bindValue(':slug', $slug, PDO::PARAM_STR);
            $stmt->execute();
            $collectionImages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        if (empty($collectionImages)) {
            return null;
        }

        $collection = [
            'id'          => $collectionImages[0]['id'],
            'slug'        => $collectionImages[0]['slug'],
            'name'        => $collectionImages[0]['name'],
            'description' => $collectionImages[0]['description'],
            'images'      => [],
            'products'    => []
        ];

        foreach($collectionImages as $image) {
            if ($image['url'] !== null) {
                $collection['images'][] = ['url' => $image['url'], 'alt' => $image['alt']];
            }
        }

        $sql = "SELECT * FROM products WHERE collection_id = :collection_id";

        if ($lang !== 'fr') {
            $sql = "SELECT p.ref, p.delay, p.stock, p.category_id, p.subcategory_id, p.collection_id,
                        COALESCE(t.slug, p.slug)               AS slug,
                        COALESCE(t.name, p.name)               AS name,
                        COALESCE(t.description, p.description) AS description,
                        COALESCE(t.materials, p.materials)     AS materials,
                        COALESCE(t.dimensions, p.dimensions)   AS dimensions
                    FROM products AS p
                    LEFT JOIN product_translations AS t ON t.product_ref = p.ref AND t.lang = :lang
                    WHERE p.collection_id = :collection_id";
        }

        $stmt = self::getDb()->prepare($sql);
        $stmt->bindValue(':collection_id', $collection['id'], PDO::PARAM_INT);
        if ($lang !== 'fr') {
            $stmt->bindValue(':lang', $lang, PDO::PARAM_STR);
        }
        $stmt->execute();

        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sql = "SELECT pi.*
                FROM product_images AS pi
                JOIN products ON pi.ref = products.ref
                WHERE products.collection_id = :collection_id
                ORDER BY pi.ref, pi.display_order";

        $stmt = self::getDb()->prepare($sql);
        $stmt->bindValue(':collection_id', $collection['id'], PDO::PARAM_INT);
        $stmt->execute();

        $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $imagesByProduct = [];

        foreach($images as $image) {
            $imagesByProduct[$image['ref']][] = $image;
        }

        foreach($products as &$product) {
            $product['images'] = $imagesByProduct[$product['ref']] ?? [];
        }

        $collection['products'] = $products;

        return $collection;
    }

    /** Inserts or updates a collection row on Odoo sync (INSERT … ON DUPLICATE KEY UPDATE on slug). */
    public static function upsert(array $data): void
    {
        if (empty($data)) {
            return;
        }

        $allowed = ['name', 'slug', 'description'];
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
            INSERT INTO collections (" . implode(', ', $fields) . ", synced_at)
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
}
