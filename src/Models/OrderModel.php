<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DBConnection;
use PDO;

class OrderModel
{
    private static function getDb(): PDO
    {
        return DBConnection::getInstance();
    }

    public static function create(string $userId, int $addressId): int
    {
        $sql = "INSERT INTO orders (owner, address_id) VALUES (:owner, :address_id)";

        $stmt = self::getDb()->prepare($sql);
        $stmt->bindValue(':owner', $userId, PDO::PARAM_STR);
        $stmt->bindValue(':address_id', $addressId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) self::getDb()->lastInsertId();
    }
    
    public static function addItem(int $orderId, string $ref, int $quantity, float $price): void
    {
        $sql = "INSERT INTO order_items (order_id, ref, quantity, price) VALUES (:order_id, :ref, :quantity, :price)";

        $stmt = self::getDb()->prepare($sql);
        $stmt->bindValue(':order_id', $orderId, PDO::PARAM_INT);
        $stmt->bindValue(':ref', $ref, PDO::PARAM_STR);
        $stmt->bindValue(':quantity', $quantity, PDO::PARAM_INT);
        $stmt->bindValue(':price', $price, PDO::PARAM_STR);
        $stmt->execute();
    }
    
    public static function getAllByUser(string $userId): array
    {
        $sql = "SELECT * FROM orders WHERE owner = :owner";

        $stmt = self::getDb()->prepare($sql);
        $stmt->bindValue(':owner', $userId, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
    
    public static function getOrder(int $orderId, string $userId): ?array
    {
        $sql = "SELECT * FROM orders WHERE owner = :owner AND id = :id";

        $stmt = self::getDb()->prepare($sql);
        $stmt->bindValue(':owner', $userId, PDO::PARAM_STR);
        $stmt->bindValue(':id', $orderId, PDO::PARAM_INT);
        $stmt->execute();

        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if(!$order) {return null;}

        $sql = "
            SELECT oi.*, p.name
            FROM order_items AS oi
            JOIN products AS p ON p.ref = oi.ref
            WHERE oi.order_id = :order_id
        ";

        $stmt = self::getDb()->prepare($sql);
        $stmt->bindValue(':order_id', $orderId, PDO::PARAM_INT);
        $stmt->execute();

        $order['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($order['items'])) {
            return $order;
        }

        $list = [];
        foreach($order['items'] as $item) {
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
        $orderImages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($orderImages) {
            foreach($order['items'] as &$item) {
                foreach($orderImages as $image) {
                    if ($image['ref'] === $item['ref']) {
                        $item['image'] = $image['url'];
                        break;
                    }
                }
            }
        }
        return $order;
    }
    
    public static function updateStatus(int $orderId, string $status): void
    {
        $sql = "UPDATE orders SET status = :status WHERE id = :id";

        $stmt = self::getDb()->prepare($sql);
        $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        $stmt->bindValue(':id', $orderId, PDO::PARAM_INT);
        $stmt->execute();
    }

}
