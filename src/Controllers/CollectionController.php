<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\CollectionModel;

/** HTTP handlers for collection endpoints. */
class CollectionController
{
    /**
     * Returns all collections with their images.
     *
     * @param object $request Incoming HTTP request (no body or params required)
     */
    public function getAll(object $request) : void
    {
        $lang = $request->lang();

        $collections = CollectionModel::findAll($lang);

        http_response_code(200);
        echo json_encode([
            'data'  => $collections,
            'message' => null,
            'error' => null
        ]);
    }

    /**
     * Returns a single collection by slug with its images and products (each with images), or 404.
     *
     * @param object $request Request with params['slug']
     */
    public function getOne(object $request): void
    {
        $slug = $request->params['slug'];
        $lang = $request->lang();

        $collection = CollectionModel::findBySlug($slug, $lang);

        if(!$collection) {
            http_response_code(404);
            echo json_encode([
                'data'    => null,
                'message' => null,
                'error'   => ['key' => 'api.no_collection', 'params' => (object)[]]
            ]);
            exit;
        }

        http_response_code(200);
        echo json_encode([
            'data'  => $collection,
            'message' => null,
            'error' => null
        ]);
    }
}
