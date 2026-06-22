<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DBConnection;
use PDO;

/** PDO queries for the categories and subcategories tables. */
class CategoryModel
{
    /** Returns the shared PDO connection. */
    private static function getDb(): PDO
    {
        return DBConnection::getInstance();
    }

    /** Returns all categories (id, name), translated if lang is not 'fr'. */
    public static function findAllCategories(string $lang = 'fr'): array
    {
        $sql = "SELECT id, name FROM categories";
        if($lang !== 'fr') {
            $sql = "SELECT id, COALESCE(name_en, name) AS name
                    FROM categories";
        }

        $stmt = self::getDb()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Returns all subcategories (id, name), translated if lang is not 'fr'. */
    public static function findAllSubcategories(string $lang = 'fr'): array
    {
        $sql = "SELECT id, name FROM subcategories";
        if($lang !== 'fr') {
            $sql = "SELECT id, COALESCE(name_en, name) AS name
                    FROM subcategories";
        }

        $stmt = self::getDb()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
