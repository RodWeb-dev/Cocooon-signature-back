<?php
declare(strict_types=1);

namespace App\Core;

use App\Controllers\AuthController;
use App\Controllers\ContactController;
use App\Controllers\UserController;
use App\Controllers\ProductController;
use App\Controllers\CollectionController;
use App\Controllers\CartController;
use App\Controllers\CategoryController;
use App\Controllers\OrderController;
use App\Controllers\PaymentController;
use App\Controllers\NewsletterController;

/**
 * Declares all application routes — called once at bootstrap from index.php.
 */
class Routes
{
    private const AUTH = "/api/auth";
    private const USERS = "/api/users";
    private const PRODUCTS = "/api/products";
    private const COLLECTIONS = "/api/collections";
    private const CART = "/api/cart";
    private const ORDERS = "/api/orders";
    private const NL = "/api/newsletter";

    /** Register all API routes on the router. */
    public static function register(Router $router): void
    {
        // Auth
        $router->post(self::AUTH . "/register", [
            AuthController::class,
            "register",
        ]);
        $router->post(self::AUTH . "/login", [AuthController::class, "login"]);
        $router->post(
            self::AUTH . "/logout",
            [AuthController::class, "logout"],
            auth: true,
        );
        $router->post(self::AUTH . "/refresh", [
            AuthController::class,
            "refresh",
        ]);
        $router->post(self::AUTH . "/forgot-password", [
            AuthController::class,
            "forgotPassword",
        ]);
        $router->post(self::AUTH . "/reset-password", [
            AuthController::class,
            "resetPassword",
        ]);
        $router->get(self::AUTH . "/verify-email/{token}", [
            AuthController::class,
            "verifyEmail",
        ]);

        // Users
        $router->get(
            self::USERS . "/me",
            [UserController::class, "getMe"],
            auth: true,
        );
        $router->patch(
            self::USERS . "/me",
            [UserController::class, "updateMe"],
            auth: true,
        );
        $router->patch(
            self::USERS . "/me/password",
            [UserController::class, "updatePassword"],
            auth: true,
        );
        $router->delete(
            self::USERS . "/me",
            [UserController::class, "deleteMe"],
            auth: true,
        );
        $router->get(
            self::USERS . "/me/addresses",
            [UserController::class, "getAddresses"],
            auth: true,
        );
        $router->post(
            self::USERS . "/me/addresses",
            [UserController::class, "addAddress"],
            auth: true,
        );
        $router->patch(
            self::USERS . "/me/addresses/{id}",
            [UserController::class, "updateAddress"],
            auth: true,
        );
        $router->delete(
            self::USERS . "/me/addresses/{id}",
            [UserController::class, "deleteAddress"],
            auth: true,
        );

        // Products
        $router->get(self::PRODUCTS, [ProductController::class, "getAll"]);
        $router->get(self::PRODUCTS . "/{slug}", [
            ProductController::class,
            "getOne",
        ]);
        $router->get(self::PRODUCTS . "/{slug}/reviews", [
            ProductController::class,
            "getReviews",
        ]);
        $router->post(
            self::PRODUCTS . "/{slug}/reviews",
            [ProductController::class, "addReview"],
            auth: true,
        );

        // Collections
        $router->get(self::COLLECTIONS, [
            CollectionController::class,
            "getAll",
        ]);
        $router->get(self::COLLECTIONS . "/{slug}", [
            CollectionController::class,
            "getOne",
        ]);

        // Categories
        $router->get("/api/categories", [
            CategoryController::class,
            "getCategories",
        ]);
        $router->get("/api/subcategories", [
            CategoryController::class,
            "getSubcategories",
        ]);

        // Cart
        $router->get(
            self::CART,
            [CartController::class, "getCart"],
            auth: true,
        );
        $router->post(
            self::CART . "/items",
            [CartController::class, "addItem"],
            auth: true,
        );
        $router->patch(
            self::CART . "/items/{id}",
            [CartController::class, "updateItemQuantity"],
            auth: true,
        );
        $router->delete(
            self::CART . "/items/{id}",
            [CartController::class, "deleteItem"],
            auth: true,
        );
        $router->delete(
            self::CART,
            [CartController::class, "clearCart"],
            auth: true,
        );
        $router->post(
            self::CART . "/merge",
            [CartController::class, "mergeItems"],
            auth: true,
        );
        $router->delete(
            self::CART . "/{id}",
            [CartController::class, "deleteCart"],
            auth: true,
        );

        // Orders
        $router->post(
            self::ORDERS,
            [OrderController::class, "create"],
            auth: true,
        );
        $router->get(
            self::ORDERS,
            [OrderController::class, "list"],
            auth: true,
        );
        $router->get(
            self::ORDERS . "/{id}",
            [OrderController::class, "show"],
            auth: true,
        );

        // Payment
        $router->post("/api/payments" . "/payplug/webhook", [
            PaymentController::class,
            "payplugWebhook",
        ]);

        // Newletter
        $router->post(self::NL, [NewsletterController::class, "subscribe"]);
        $router->patch(
            self::NL,
            [NewsletterController::class, "toggleNewsletter"],
            auth: true,
        );
        $router->get(
            self::NL,
            [NewsletterController::class, "isSubscribed"],
            auth: true,
        );

        // Contact
        $router->post("/api/contact", [ContactController::class, "send"]);
    }
}
