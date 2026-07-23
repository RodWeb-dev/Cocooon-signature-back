<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\NewsletterModel;
use App\Models\UserModel;
use App\Services\OdooFactory;
use App\Services\OdooServiceInterface;

/** Handles newsletter subscription endpoints. */
class NewsletterController
{
    private OdooServiceInterface $odoo;

    public function __construct()
    {
        $this->odoo = OdooFactory::create();
    }
    /**
     * Subscribes an email address to the newsletter.
     *
     * The user ID is optional — anonymous subscriptions are allowed.
     *
     * @param object $request Request with body: email. user context optional.
     */
    public function subscribe(object $request): void
    {
        $email = $request->body["email"];
        $userId = null;
        if (isset($request->user)) {
            $userId = $request->user["sub"];
        }

        NewsletterModel::subscribe($email, $userId);

        try {
            $this->odoo->addToNewsletter($email);
        } catch (\Exception $e) {
            error_log("Odoo API error: " . $e->getMessage());
        }

        http_response_code(201);
        echo json_encode([
            "data" => null,
            "message" => ["key" => "api.nl_subscribe", "params" => (object) []],
            "error" => null,
        ]);
    }

    /**
     * Enables or disables the newsletter subscription for the authenticated user.
     *
     * @param object $request Request with body: subscribed (bool) and user context
     */
    public function toggleNewsletter(object $request): void
    {
        $userId = $request->user["sub"];
        $subscribed = $request->body["subscribed"];

        $user = UserModel::findById($userId);
        if ($subscribed) {
            NewsletterModel::subscribe($user["email"], $userId);

            try {
                $this->odoo->addToNewsletter($user["email"]);
            } catch (\Exception $e) {
                error_log("Odoo API error: " . $e->getMessage());
            }
        } else {
            NewsletterModel::unsubscribe($userId);

            try {
                $this->odoo->removeFromNewsletter($user["email"]);
            } catch (\Exception $e) {
                error_log("Odoo API error: " . $e->getMessage());
            }
        }

        http_response_code(201);
        echo json_encode([
            "data" => null,
            "message" => ["key" => "api.nl_toggle", "params" => (object) []],
            "error" => null,
        ]);
    }

    /**
     * Returns whether the authenticated user is subscribed to the newsletter.
     *
     * Auto-subscribes the user if no subscription row exists yet (e.g. accounts
     * created before the newsletter feature).
     *
     * @param object $request Request with user context
     */
    public function isSubscribed(object $request): void
    {
        $userId = $request->user["sub"];
        $user = UserModel::findById($userId);
        $subscribed = NewsletterModel::find($userId, $user["email"]);

        if (!isset($subscribed["owner"])) {
            NewsletterModel::subscribe($user["email"], $userId);
        }

        http_response_code(200);
        echo json_encode([
            "data" => !empty($subscribed),
            "message" => null,
            "error" => null,
        ]);
    }
}
