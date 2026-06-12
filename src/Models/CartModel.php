<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DBConnection;
use PDO;

class CartModel
{
    private static function getDb(): PDO
    {
        return DBConnection::getInstance();
    }

    public static function getAllCarts(string $userId): array
    {
        $sql ="SELECT * FROM carts WHERE owner = :owner AND status != 'cancelled'";

        $stmt = self::getDb()->prepare($sql);
        $stmt->bindValue(':owner', $userId, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC)?: [];
    }

    public static function getCart(int $cartId): ?array
    {
        $sql = "SELECT * FROM carts WHERE id = :cart_id";

        $stmt = self::getDb()->prepare($sql);
        $stmt->bindValue(':cart_id', $cartId, PDO::PARAM_INT);
        $stmt->execute();

        $cart = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cart) {return null;}
        
        $sql ="
            SELECT ci.*, p.name
            FROM cart_items AS ci
            JOIN products AS p ON p.ref = ci.ref
            WHERE ci.cart_id = :cart_id
        ";

        $stmt = self::getDb()->prepare($sql);
        $stmt->bindValue(':cart_id', $cartId, PDO::PARAM_INT);
        $stmt->execute();

        $cart['items'] =$stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($cart['items'])) {
            return $cart;
        }

        $list = [];
        foreach($cart['items'] as $item) {
            $list[] = $item['ref'];
        }

        $lenght = str_repeat('?, ', count($list) - 1);
        $lenght .= '?';
            
        $sql="
            SELECT pi.ref, pi.url
            FROM product_images AS pi
            INNER JOIN (
                SELECT ref, MIN(display_order) AS min_order
                FROM product_images
                GROUP BY ref
            ) AS first ON first.ref = pi.ref AND first.min_order = pi.display_order
            WHERE pi.ref IN (". $lenght . ")
        ";

        $stmt = self::getDb()->prepare($sql);
        $stmt->execute($list);
        $cartImages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($cartImages) {
            foreach($cart['items'] as &$item) {
                foreach($cartImages as $image) {
                    if ($image['ref'] === $item['ref']) {
                        $item['image'] = $image['url'];
                        break;
                    }
                }
            }
        }
        return $cart;
    }

    public static function create(string $userId): int
    {
        $sql ="INSERT INTO carts (owner) VALUES (:owner)";

        $stmt = self::getDb()->prepare($sql);
        $stmt->bindValue(':owner', $userId, PDO::PARAM_STR);
        $stmt->execute();

        return (int) self::getDb()->lastInsertId();
    }

    public static function deleteCart(int $cartId): void
    {
        $sql ="UPDATE carts SET status = 'cancelled' WHERE id = :id";

        $stmt = self::getDb()->prepare($sql);
        $stmt->bindValue(':id', $cartId, PDO::PARAM_INT);
        $stmt->execute();
    }

    public static function addItem(int $cartId, string $ref, int $quantity, float $price): void
    {
        $sql = "INSERT INTO cart_items (cart_id, ref, quantity, price) 
                VALUES (:cart_id, :ref, :quantity, :price)
                ON DUPLICATE KEY UPDATE quantity = quantity + :quantity2";
        $stmt = self::getDb()->prepare($sql);
        $stmt->bindValue(':cart_id', $cartId, PDO::PARAM_INT);
        $stmt->bindValue(':ref', $ref, PDO::PARAM_STR);
        $stmt->bindValue(':quantity', $quantity, PDO::PARAM_INT);
        $stmt->bindValue(':quantity2', $quantity, PDO::PARAM_INT);
        $stmt->bindValue(':price', $price, PDO::PARAM_STR);
        $stmt->execute();
    }

    public static function updateItemQuantity(int $itemId, int $cartId, int $quantity): void
    {
        $sql = "UPDATE cart_items SET quantity = :quantity WHERE cart_id = :cart_id AND id = :id";

        $stmt = self::getDb()->prepare($sql);
        $stmt->bindValue(':quantity', $quantity, PDO::PARAM_INT);
        $stmt->bindValue(':cart_id', $cartId, PDO::PARAM_INT);
        $stmt->bindValue(':id', $itemId, PDO::PARAM_INT);
        $stmt->execute();
    }

    public static function deleteItem(int $itemId, int $cartId): void
    {
        $sql = "DELETE FROM cart_items WHERE cart_id = :cart_id AND id = :id";

        $stmt = self::getDb()->prepare($sql);
        $stmt->bindValue(':cart_id', $cartId, PDO::PARAM_INT);
        $stmt->bindValue(':id', $itemId, PDO::PARAM_INT);
        $stmt->execute();
    }

    public static function clear(int $cartId): void
    {
        $sql = "DELETE FROM cart_items WHERE cart_id = :cart_id";

        $stmt = self::getDb()->prepare($sql);
        $stmt->bindValue(':cart_id', $cartId, PDO::PARAM_INT);
        $stmt->execute();
    }
}
