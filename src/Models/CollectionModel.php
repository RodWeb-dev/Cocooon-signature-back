<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DBConnection;
use PDO;

class CollectionModel
{
    private static function getDb(): PDO
    {
        return DBConnection::getInstance();
    }

    public static function findAll() : array
    {
        $sql = "SELECT * FROM collections";

        $stmt = self::getDb()->prepare($sql);
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

    public static function findBySlug(string $slug): ?array
    {
        $sql = "
            SELECT c.*, i.url, i.alt
            FROM collections AS c
            LEFT JOIN collection_images AS i ON i.collection_id = c.id
            WHERE slug = :slug
            ORDER BY i.display_order
        ";

        $stmt = self::getDb()->prepare($sql);
        $stmt->bindValue(':slug', $slug, PDO::PARAM_STR);
        $stmt->execute();

        $collectionImages = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

        $stmt = self::getDb()->prepare($sql);
        $stmt->bindValue(':collection_id', $collection['id'], PDO::PARAM_INT);
        $stmt->execute();

        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sql = "
            SELECT pi.*
            FROM product_images AS pi
            JOIN products ON pi.ref = products.ref
            WHERE products.collection_id = :collection_id
            ORDER BY pi.ref, pi.display_order
        ";

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
}
