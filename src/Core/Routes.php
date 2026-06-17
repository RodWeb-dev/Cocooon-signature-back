<?php
declare(strict_types=1);

namespace App\Core;

use App\Controllers\AuthController;
use App\Controllers\UserController;
use App\Controllers\ProductController;
use App\Controllers\CollectionController;
use App\Controllers\CartController;
use App\Controllers\OrderController;
use App\Controllers\PaymentController;
use App\Core\Controllers\AuthController as ControllersAuthController;

/**
 * Declares all application routes — called once at bootstrap from index.php.
 */
class Routes
{
    private const AUTH = '/api/auth';
    private const USERS = '/api/users';
    private const PRODUCTS = '/api/products';
    private const COLLECTIONS = '/api/collections';
    private const CART = '/api/cart';
    private const ORDERS = '/api/orders';
    private const PAYMENTS = '/api/payments';

    /** Register all API routes on the router. */
    public static function register(Router $router): void
    {
        // Auth
        $router->post(self::AUTH . '/register', [AuthController::class, 'register']);
        $router->post(self::AUTH . '/login', [AuthController::class, 'login']);
        $router->post(self::AUTH . '/logout', [AuthController::class, 'logout'], auth: true);
        $router->post(self::AUTH . '/refresh', [AuthController::class, 'refresh']);
        $router->post(self::AUTH . '/forgot-password', [AuthController::class, 'forgotPassword']);
        $router->post(self::AUTH . '/reset-password', [AuthController::class, 'resetPassword']);
        $router->get(self::AUTH . '/verify-email/{token}', [AuthController::class, 'verifyEmail']);

        // Users
        $router->get(self::USERS . '/me', [UserController::class, 'show'], auth: true);
        $router->patch(self::USERS . '/me', [UserController::class, 'updateProfile'], auth: true);
        $router->patch(self::USERS . '/me/newsletter/{subscribed}', [UserController::class, 'updateProfile'], auth: true);
        $router->patch(self::USERS . '/me/password', [UserController::class, 'updatePassword'], auth: true);
        $router->delete(self::USERS . '/me', [UserController::class, 'delete'], auth: true);
        $router->get(self::USERS . '/me/addresses', [UserController::class, 'listAddresses'], auth: true);
        $router->post(self::USERS . '/me/addresses', [UserController::class, 'addAddress'], auth: true);
        $router->patch(self::USERS . '/me/addresses/{id}', [UserController::class, 'updateAddress'], auth: true);
        $router->delete(self::USERS . '/me/addresses/{id}', [UserController::class, 'deleteAddress'], auth: true);

        // Products
        $router->get(self::PRODUCTS, [ProductController::class, 'list']);
        $router->get(self::PRODUCTS . '/{slug}', [ProductController::class, 'show']);
        $router->get(self::PRODUCTS . '/{slug}/reviews', [ProductController::class, 'reviews']);
        $router->post(self::PRODUCTS . '/{slug}/reviews', [ProductController::class, 'addReview'], auth: true);

        // Collections
        $router->get(self::COLLECTIONS, [CollectionController::class, 'list']);
        $router->get(self::COLLECTIONS . '/{slug}', [CollectionController::class, 'show']);

        // Cart
        $router->get(self::CART, [CartController::class, 'show'], auth: true);
        $router->post(self::CART . '/items', [CartController::class, 'addItem'], auth: true);
        $router->patch(self::CART . '/items/{id}', [CartController::class, 'updateItem'], auth: true);
        $router->delete(self::CART . '/items/{id}', [CartController::class, 'removeItem'], auth: true);
        $router->delete(self::CART, [CartController::class, 'clear'], auth: true);
        $router->post(self::CART . '/merge', [CartController::class, 'merge'], auth: true);
        
        // Orders
        $router->post(self::ORDERS, [OrderController::class, 'create'], auth: true);
        $router->get(self::ORDERS, [OrderController::class, 'list'], auth: true);
        $router->get(self::ORDERS . '/{id}', [OrderController::class, 'show'], auth: true);

        // Payment
        $router->post(self::PAYMENTS . '/payplug/webhook', [PaymentController::class, 'payplugWebhook']);

        // Newletter
        $router->post('/api/newsletter', [NewsletterController::class, 'subscribe']);
    }
}
