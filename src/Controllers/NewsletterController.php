<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\NewsletterModel;
use App\Models\UserModel;

/** Handles newsletter subscription endpoints. */
class NewsletterController
{
    /**
     * Subscribes an email address to the newsletter.
     *
     * The user ID is optional — anonymous subscriptions are allowed.
     *
     * @param object $request Request with body: email. user context optional.
     */
    public function subscribe(object $request): void
    {
        $email = $request->body['email'];
        $userId = null;
        if (isset($request->user)) {
            $userId = $request->user['sub'];
        }
    
        NewsletterModel::subscribe($email, $userId);

        http_response_code(201);
        echo json_encode([
            'data'    => null,
            'message' => ['key' => 'api.nl_subscribe', 'params' => (object)[]],
            'error'   => null
        ]);
    }

    /**
     * Enables or disables the newsletter subscription for the authenticated user.
     *
     * @param object $request Request with body: subscribed (bool) and user context
     */
    public function toggleNewsletter(object $request): void
    {
        $userId = $request->user['sub'];
        $subscribed = $request->body['subscribed'];

        if($subscribed) {
            $user = UserModel::findById($userId);
            NewsletterModel::subscribe($user['email'], $userId);
        } else {
            NewsletterModel::unsubscribe($userId);
        }

        http_response_code(201);
        echo json_encode([
            'data'    => null,
            'message' => ['key' => 'api.nl_toggle', 'params' => (object)[]],
            'error'   => null
        ]);
    }
}
