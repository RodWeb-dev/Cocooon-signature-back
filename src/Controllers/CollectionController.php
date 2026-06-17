<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\CollectionModel;

/** HTTP handlers for collection endpoints. */
class CollectionController
{
    /** Returns all collections with their images. */
    public function getAll(object $request) : void
    {
        $collections = CollectionModel::findAll();

        http_response_code(200);
        echo json_encode([
            'data'  => $collections,
            'error' => null
        ]);
    }

    /** Returns a single collection by slug with images and products, or 404 if not found. */
    public function getOne(object $request): void
    {
        $slug = $request->params['slug'];
        $collection = CollectionModel::findBySlug($slug);

        if(!$collection) {
            http_response_code(404);
            echo json_encode([
                'data'  => null,
                'error' => 'La collection demandée n\'existe pas'
            ]);
            exit;
        }

        http_response_code(200);
        echo json_encode([
            'data'  => $collection,
            'error' => null
        ]);
    }
}
