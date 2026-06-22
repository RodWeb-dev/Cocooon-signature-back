<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Security\FilterInput;
use App\Models\OrderModel;
use App\Models\ProductModel;

/** HTTP handlers for product endpoints. */
class ProductController
{
    /**
     * Returns all products, optionally filtered by query parameters.
     *
     * Accepted GET filters: collection_id, category_id, subcategory_id.
     *
     * @param object $request Request with optional query filters
     */
    public function getAll(object $request): void
    {
        $filters = $request->query;
        $lang = $request->lang();

        $products = ProductModel::findAll($filters, $lang);

        http_response_code(200);
        echo json_encode([
            'data'   => $products,
            'message' => null,
            'error' => null
        ]);
    }

    /**
     * Returns a single product by slug, or 404 if not found.
     *
     * @param object $request Request with params['slug']
     */
    public function getOne(object $request): void
    {
        $slug = $request->params['slug'];
        $lang = $request->lang();

        $product = ProductModel::findBySlug($slug, $lang)
;
        if(!$product) {
            http_response_code(404);
            echo json_encode([
                'data'    => null,
                'message' => null,
                'error'   => ['key' => 'api.no_product', 'params' => (object)[]]
            ]);
            exit;
        }

        http_response_code(200);
        echo json_encode([
            'data'    => $product,
            'message' => null,
            'error'   => null
        ]);
    }

    /**
     * Returns all reviews for a product by slug, joined with reviewer name.
     *
     * @param object $request Request with params['slug']
     */
    public function getReviews(object $request): void
    {
        $slug = $request->params['slug'];
        $lang = $request->lang();

        $product = ProductModel::findBySlug($slug, $lang);
        if (!$product) {
            http_response_code(404);
            echo json_encode([
                'data'    => null,
                'message' => null,
                'error'   => ['key' => 'api.no_product', 'params' => (object)[]]
            ]);
            exit;
        }

        $reviews = ProductModel::getReviews($slug, $lang);

        http_response_code(200);
        echo json_encode([
            'data'   => $reviews,
            'message' => null,
            'error' => null
        ]);
    }

    /**
     * Adds a review for a product.
     *
     * Requires the requesting user to have at least one delivered order containing the product.
     * Responds 403 if the eligibility check fails.
     *
     * @param object $request Request with params['slug'], body: rating (int 1–5), comment (string, optional)
     */
    public function addReview(object $request): void
    {
        $userId = $request->user['sub'];
        $slug = $request->params['slug'];
        $lang = $request->lang();
        $body = $request->body;

        $missing = FilterInput::required(['rating'], $body);
        if (!empty($missing)) {
            http_response_code(400);
            echo json_encode([
                'data'    => null,
                'message' => null,
                'error'   => ['key' => 'api.rev_rate', 'params' => (object)[]]
            ]);
            exit;
        }

        $rating = (int) $body['rating'];
        if ($rating < 1 || $rating > 5) {
            http_response_code(400);
            echo json_encode([
                'data'    => null,
                'message' => null,
                'error'   => ['key' => 'api.rev_value', 'params' => (object)[]]
            ]);
            exit;
        }

        $product = ProductModel::findBySlug($slug, $lang);
        if (!$product) {
            http_response_code(404);
            echo json_encode([
                'data'    => null,
                'message' => null,
                'error'   => ['key' => 'api.no_product', 'params' => (object)[]]
            ]);
            exit;
        }

        if (!OrderModel::hasDeliveredOrder($userId, $product['ref'])) {
            http_response_code(403);
            echo json_encode([
                'data'    => null,
                'message' => null,
                'error'   => ['key' => 'api.refused', 'params' => (object)[]]
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
            'data'    => null,
            'message' => ['key' => 'api.rev_ok', 'params' => (object)[]],
            'error'   => null
        ]);
    }
}
