<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\CartModel;
use App\Models\ProductModel;
use App\Core\Security\FilterInput;
use Brevo\Types\Cart;

/** HTTP handlers for cart endpoints. */
class CartController
{
    /** Adds a single item to the user's cart; creates the cart if none exists. */
    private function addSingleItem(string $userId, string $ref, int $quantity): array
    {
        $product = ProductModel::findByRef($ref);
        if (!$product) {
            return [
                'code'  => 404,
                'data'  => null,
                'error' => 'Produit inexistant : ' . $ref
            ];
        }
        if ($product['availability'] === 'in_stock' && $product['stock'] < $quantity) {
            return [
                'code'  => 409,
                'data'  => null,
                'error' => 'Stock insuffisant : ' . $ref
            ];
        }
        $cart = CartModel::findPendingCart($userId);
        $cartId = $cart ? $cart['id'] : CartModel::create($userId);

        CartModel::addItem($cartId, $ref, $quantity, $product['price']);

        return [
                'code'  => 201,
                'data'  => 'Article ajouté au panier',
                'error' => null
            ];
    }

    /** Returns the authenticated user's pending cart. */
    public function getCart(object $request): void
    {
        $userId = $request->user['sub'];
        $cart = CartModel::findPendingCart($userId);

        http_response_code(200);
        echo json_encode([
            'data'  => $cart ?? [],
            'error' => null
        ]);
    }

    /** Adds an item to the cart; validates ref and quantity. */
    public function addItem(object $request): void
    {
        $userId = $request->user['sub'];
        $body = $request->body;
        $fields = ['ref','quantity'];

        $missing = FilterInput::required($fields, $body);
        if (!empty($missing)) {
            http_response_code(400);
            echo json_encode([
                'data'  => null,
                'error' => 'Les champs suivants sont absents : '. implode(', ', $missing)
            ]);
            exit;
        }

        $quantity = (int) $body['quantity'];

        if ($quantity < 1) {
            http_response_code(400);
            echo json_encode([
                'data'  => null,
                'error' => 'La quantité doit être superieur à zéro.'
            ]);
            exit;
        }

        $http = $this->addSingleItem($userId, $body['ref'], $quantity);

        http_response_code($http['code']);
        echo json_encode([
            'data'  => $http['data'],
            'error' => $http['error']
        ]);
    }

    /** Updates the quantity of an item in the cart. */
    public function updateItemQuantity(object $request): void
    {
        $userId = $request->user['sub'];
        $itemId = $request->params['id'];

        if (!isset($request->body['quantity'])) {
            http_response_code(400);
            echo json_encode([
                'data'  => null,
                'error' => 'La quantité est obligatoire.'
            ]);
            exit;
        }
        
        $quantity = (int) $request->body['quantity'];
        if ($quantity < 1) {
            http_response_code(400);
            echo json_encode([
                'data'  => null,
                'error' => 'La quantité doit être supérieure à zéro.'
            ]);
            exit;
        }

        CartModel::updateItemQuantity($itemId, $quantity, $userId);

        http_response_code(200);
        echo json_encode([
            'data'  => 'La quantité a été mise à jour.',
            'error' => null
        ]);
    }

    /** Removes an item from the cart. */
    public function deleteItem(object $request): void
    {
        $userId = $request->user['sub'];
        $itemId = $request->params['id'];

        CartModel::deleteItem($itemId, $userId);

        http_response_code(200);
        echo json_encode([
            'data'  => 'L\'article a été retiré du panier.',
            'error' => null
        ]);
    }

    /** Empties all items from the authenticated user's pending cart. */
    public function clearCart(object $request): void
    {
        $userId = $request->user['sub'];

        $cart = CartModel::findPendingCart($userId);

        if ($cart) {
            CartModel::clear($cart['id']);
        }

        http_response_code(200);
        echo json_encode([
            'data'  => 'Le panier a été vidé.',
            'error' => null
        ]);
    }

    /** Merges a list of items from localStorage into the user's cart after login. */
    public function mergeItems(object $request): void
    {
        $userId = $request->user['sub'];
        $items = $request->body['items'] ?? [];

        if (!is_array($items) || empty($items)) {
            http_response_code(400);
            echo json_encode(['data' => null, 'error' => 'Aucun article à fusionner.']);
            exit;
        }

        $errors = [];
        $successCount = 0;

        foreach ($items as $item) {
            if (!isset($item['ref']) || !isset($item['quantity']) || (int)$item['quantity'] < 1) {
                $errors[] = 'Article invalide ignoré.';
                continue;
            }
            $result = $this->addSingleItem($userId, $item['ref'], (int) $item['quantity']);
            if ($result['error']) {
                $errors[] = $result['error'];
            } else {
                $successCount++;
            }
        }

        http_response_code(200);
        echo json_encode([
            'data' => [
                'merged' => $successCount,
                'errors' => $errors
            ],
            'error' => null
        ]);
    }

    /** Cancels (soft-deletes) a cart by id. */
    public function deleteCart(object $request): void
    {
        $userId = $request->user['sub'];
        $cartId = (int) $request->params['id'];

        CartModel::deleteCart($cartId, $userId);

        http_response_code(200);
        echo json_encode([
            'data'  => 'Le panier a été supprimé.',
            'error' => null
        ]);
    }
}
