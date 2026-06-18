<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\OdooApiService;
use App\Services\OdooStubService;

class OdooFactory
{
    public static function create(): OdooServiceInterface
    {
        $env = $_ENV['APP_ENV'] ?? 'development';

        if ($env === 'production') {
            return new OdooApiService();
        }

        return new OdooStubService();
    }
}
