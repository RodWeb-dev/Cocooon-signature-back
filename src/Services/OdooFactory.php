<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\OdooApiService;
use App\Services\OdooStubService;

/**
 * Instantiates the appropriate Odoo service based on APP_ENV.
 *
 * Returns OdooStubService in development/staging, OdooApiService in production.
 */
class OdooFactory
{
    /**
     * @return OdooServiceInterface OdooStubService (non-prod) or OdooApiService (prod)
     */
    public static function create(): OdooServiceInterface
    {
        $env = $_ENV['APP_ENV'] ?? 'development';

        if ($env === 'production') {
            return new OdooApiService();
        }

        return new OdooStubService();
    }
}
