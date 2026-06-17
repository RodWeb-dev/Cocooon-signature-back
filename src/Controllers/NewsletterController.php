<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\NewsletterModel;
use App\Models\UserModel;

class NewsletterController
{
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
            'data'  => 'Inscription enregistrée.',
            'error' => null
        ]);
    }

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
            'data'  => 'Vos préférences ont bien été prises en compte.',
            'error' => null
        ]);
    }
}
