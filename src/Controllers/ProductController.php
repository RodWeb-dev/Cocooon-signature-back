<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Security\FilterInput;
use App\Models\OrderModel;
use App\Models\ProductModel;

/** HTTP handlers for product endpoints. */
class ProductController
{
    /** Returns all products, optionally filtered by query parameters. */
    public function getAll(object $request): void
    {
        $filters = $request->query;

        $products = ProductModel::findAll($filters);

        http_response_code(200);
        echo json_encode([
            'data'   => $products,
            'error' => null
        ]);
    }

    /** Returns a single product by slug, or 404 if not found. */
    public function getOne(object $request): void
    {
        $slug = $request->params['slug'];

        $product = ProductModel::findBySlug($slug);

        if(!$product) {
            http_response_code(404);
            echo json_encode([
                'data'   => null,
                'error' => 'Le produit demandé n\'existe pas'
            ]);
            exit;
        }

        http_response_code(200);
        echo json_encode([
            'data'   => $product,
            'error' => null
        ]);
    }

    /** Returns the reviews for a product by slug. */
    public function getReviews(object $request): void
    {
        $slug = $request->params['slug'];

        $reviews = ProductModel::getReviews($slug);

        http_response_code(200);
        echo json_encode([
            'data'   => $reviews,
            'error' => null
        ]);
    }

    /** Adds a review for a product; requires a delivered order from the requesting user. */
    public function addReview(object $request): void
    {
        $userId = $request->user['sub'];
        $slug = $request->params['slug'];
        $body = $request->body;

        $missing = FilterInput::required(['rating'], $body);
        if (!empty($missing)) {
            http_response_code(400);
            echo json_encode([
                'data' => null,
                'error' => 'La note est obligatoire'
            ]);
            exit;
        }

        $rating = (int) $body['rating'];
        if ($rating < 1 || $rating > 5) {
            http_response_code(400);
            echo json_encode([
                'data'  => null,
                'error' => 'La note doit être comprise entre 1 et 5'
            ]);
            exit;
        }

        $product = ProductModel::findBySlug($slug);
        if (!$product) {
            http_response_code(404);
            echo json_encode([
                'data'   => null,
                'error' => 'Le produit demandé n\'existe pas'
            ]);
            exit;
        }

        if (!OrderModel::hasDeliveredOrder($userId, $product['ref'])) {
            http_response_code(403);
            echo json_encode([
                'data'   => null,
                'error' => 'Accès refusé'
            ]);
            exit;
        }

        $comment = null;
        if (isset($body['comment'])) {
            $comment = FilterInput::sanitize($body['comment']);
        }

        ProductModel::addReview($product['ref'], $userId, $rating, $comment);

        http_response_code(201);
        echo json_encode([
            'data' => 'Votre avis a été publié.',
            'error' => null
        ]);
    }
}
