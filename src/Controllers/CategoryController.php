<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\CategoryModel;

/** HTTP handlers for category endpoints. */
class CategoryController
{
    /**
     * Returns all categories.
     *
     * @param object $request Incoming HTTP request (no body or params required)
     */
    public function getCategories(object $request) : void
    {
        $lang = $request->lang();

        $categories = CategoryModel::findAllCategories($lang);

        http_response_code(200);
        echo json_encode([
            'data'  => $categories,
            'message' => null,
            'error' => null
        ]);
    }

    /**
     * Returns all subcategories.
     *
     * @param object $request Incoming HTTP request (no body or params required)
     */
    public function getSubcategories(object $request) : void
    {
        $lang = $request->lang();

        $subcategories = CategoryModel::findAllSubcategories($lang);

        http_response_code(200);
        echo json_encode([
            'data'  => $subcategories,
            'message' => null,
            'error' => null
        ]);
    }
}
