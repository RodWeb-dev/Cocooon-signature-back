<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Router;
use App\Core\Routes;
use App\Core\Exceptions\HttpException;

// Get env variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
$dotenv->required([
    'APP_ENV',
    'DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASSWORD',
    'JWT_SECRET', 'JWT_EXPIRATION',
    'ODOO_URL', 'ODOO_DB', 'ODOO_USER', 'ODOO_PASSWORD',
    'BREVO_API_KEY',
    'PAYPAL_CLIENT_ID', 'PAYPAL_CLIENT_SECRET',
    'PAYPAL_MODE', 'PAYPAL_WEBHOOK_ID',
    'PAYPLUG_SECRET_KEY', 'PAYPLUG_WEBHOOK_SECRET'
]);

// Error reporting based on environment
if ($_ENV['APP_ENV'] === 'development') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(0);
}

// Headers CORS et Content-Type
header('Content-Type: application/json');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Origin: https://cocoon-signature.com');
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Routing
$router = new Router();
Routes::register($router);

try {
    $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
} catch (HttpException $e) {
    http_response_code($e->getCode());
    echo json_encode(['data' => null, 'error' => $e->getMessage()]);
} catch (\Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['data' => null, 'error' => 'Erreur interne du serveur']);
}
