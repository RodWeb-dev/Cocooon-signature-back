<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\CartModel;
use App\Models\ProductModel;
use App\Core\Security\FilterInput;

/** HTTP handlers for cart endpoints. */
class CartController
{
    /**
     * Validates product existence and stock, then adds the item to the user's pending cart.
     *
     * Creates the cart if none exists. Returns a result map so callers can forward
     * the HTTP code and message without throwing.
     *
     * @param  string $userId   Authenticated user UUID
     * @param  string $ref      Product reference (products.ref)
     * @param  int    $quantity Number of units to add (must be >= 1)
     * @return array{code: int, data: null, message: array|null, error: array|null}
     */
    private function addSingleItem(
        string $userId,
        string $ref,
        int $quantity,
    ): array {
        $product = ProductModel::findByRef($ref);
        if (!$product) {
            return [
                "code" => 404,
                "data" => null,
                "message" => null,
                "error" => [
                    "key" => "api.no_product",
                    "params" => ["ref" => $ref],
                ],
            ];
        }

        $cart = CartModel::findPendingCart($userId);
        $cartId = $cart ? $cart["id"] : CartModel::create($userId);

        CartModel::addItem($cartId, $ref, $quantity, $product["price"]);

        return [
            "code" => 201,
            "data" => null,
            "message" => ["key" => "api.addtocart", "params" => (object) []],
            "error" => null,
        ];
    }

    /**
     * Returns the authenticated user's pending cart with its items and product images.
     *
     * Returns an empty array if no pending cart exists.
     *
     * @param object $request Request with user context
     */
    public function getCart(object $request): void
    {
        $userId = $request->user["sub"];
        $cart = CartModel::findPendingCart($userId);

        http_response_code(200);
        echo json_encode([
            "data" => $cart ?? [],
            "message" => null,
            "error" => null,
        ]);
    }

    /**
     * Adds an item to the cart after validating product existence, stock, and quantity.
     *
     * @param object $request Request with body: ref (string), quantity (int >= 1)
     */
    public function addItem(object $request): void
    {
        $userId = $request->user["sub"];
        $body = $request->body;
        $fields = ["ref", "quantity"];

        $missing = FilterInput::required($fields, $body);
        if (!empty($missing)) {
            http_response_code(400);
            echo json_encode([
                "data" => null,
                "message" => null,
                "error" => [
                    "key" => "api.fields",
                    "params" => ["fields" => implode(", ", $missing)],
                ],
            ]);
            exit();
        }

        $quantity = (int) $body["quantity"];

        if ($quantity < 1) {
            http_response_code(400);
            echo json_encode([
                "data" => null,
                "message" => null,
                "error" => ["key" => "api.quantity", "params" => (object) []],
            ]);
            exit();
        }

        $http = $this->addSingleItem($userId, $body["ref"], $quantity);

        http_response_code($http["code"]);
        echo json_encode([
            "data" => $http["data"],
            "message" => $http["message"],
            "error" => $http["error"],
        ]);
    }

    /**
     * Updates the quantity of a cart item; rejects values below 1.
     *
     * @param object $request Request with params['id'] (cart_items.id) and body: quantity (int >= 1)
     */
    public function updateItemQuantity(object $request): void
    {
        $userId = $request->user["sub"];
        $itemId = $request->params["id"];

        if (!isset($request->body["quantity"])) {
            http_response_code(400);
            echo json_encode([
                "data" => null,
                "message" => null,
                "error" => ["key" => "api.quantity", "params" => (object) []],
            ]);
            exit();
        }

        $quantity = (int) $request->body["quantity"];
        if ($quantity < 1) {
            http_response_code(400);
            echo json_encode([
                "data" => null,
                "message" => null,
                "error" => ["key" => "api.qty_mini", "params" => (object) []],
            ]);
            exit();
        }

        CartModel::updateItemQuantity($itemId, $quantity, $userId);

        http_response_code(200);
        echo json_encode([
            "data" => null,
            "message" => ["key" => "api.qty_update", "params" => (object) []],
            "error" => null,
        ]);
    }

    /**
     * Removes an item from the cart.
     *
     * @param object $request Request with params['id'] (cart_items.id)
     */
    public function deleteItem(object $request): void
    {
        $userId = $request->user["sub"];
        $itemId = $request->params["id"];

        CartModel::deleteItem($itemId, $userId);

        http_response_code(200);
        echo json_encode([
            "data" => null,
            "message" => ["key" => "api.item_del", "params" => (object) []],
            "error" => null,
        ]);
    }

    /**
     * Removes all items from the authenticated user's pending cart without deleting the cart.
     *
     * @param object $request Request with user context
     */
    public function clearCart(object $request): void
    {
        $userId = $request->user["sub"];

        $cart = CartModel::findPendingCart($userId);

        if ($cart) {
            CartModel::clear($cart["id"]);
        }

        http_response_code(200);
        echo json_encode([
            "data" => null,
            "message" => ["key" => "api.cart_clear", "params" => (object) []],
            "error" => null,
        ]);
    }

    /**
     * Merges a list of items from localStorage into the user's cart after login.
     *
     * Items with invalid ref or zero quantity are skipped and reported in the errors list.
     * Partial success is allowed — failures do not block successful inserts.
     *
     * @param object $request Request with body: items (array of {ref: string, quantity: int})
     */
    public function mergeItems(object $request): void
    {
        $userId = $request->user["sub"];
        $items = $request->body["items"] ?? [];

        if (!is_array($items) || empty($items)) {
            http_response_code(400);
            echo json_encode([
                "data" => null,
                "message" => null,
                "error" => ["key" => "api.cart_merge", "params" => (object) []],
            ]);
            exit();
        }

        $errors = [];
        $successCount = 0;

        foreach ($items as $item) {
            if (
                !isset($item["ref"]) ||
                !isset($item["quantity"]) ||
                (int) $item["quantity"] < 1
            ) {
                $errors[] = "Article invalide ignoré.";
                continue;
            }
            $result = $this->addSingleItem(
                $userId,
                $item["ref"],
                (int) $item["quantity"],
            );
            if ($result["error"]) {
                $errors[] = $result["error"];
            } else {
                $successCount++;
            }
        }

        http_response_code(200);
        echo json_encode([
            "data" => [
                "merged" => $successCount,
                "errors" => $errors,
            ],
            "message" => null,
            "error" => null,
        ]);
    }

    /**
     * Soft-deletes a cart by setting its status to cancelled.
     *
     * @param object $request Request with params['id'] (carts.id)
     */
    public function deleteCart(object $request): void
    {
        $userId = $request->user["sub"];
        $cartId = (int) $request->params["id"];

        CartModel::deleteCart($cartId, $userId);

        http_response_code(200);
        echo json_encode([
            "data" => null,
            "message" => ["key" => "api.cart_del", "params" => (object) []],
            "error" => null,
        ]);
    }
}
