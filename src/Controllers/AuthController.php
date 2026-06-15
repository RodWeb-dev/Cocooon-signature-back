<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Security\FilterInput;
use App\Core\Security\JWT;
use App\Core\Security\RateLimit;
use App\Models\UserModel;
use tidy;

class AuthController
{
    private JWT $jwt;

    public function __construct()
    {
        $this->jwt = new JWT($_ENV['JWT_SECRET'], $_ENV['JWT_EXPIRATION']);
    }

    private function validate(array $body, array $fields, string $ip, string $action): void
    {
        $missing = FilterInput::required($fields, $body);
        if(!empty($missing)) {
            http_response_code(400);
            echo json_encode([
                'data'  => null,
                'error' => 'Les champs suivants sont absents : '. implode(', ', $missing)
            ]);
            exit;
        }

        if(!RateLimit::check($action, $ip)) {
            http_response_code(429);
            echo json_encode([
                'data'  => null,
                'error' => 'Veuillez réessayer plus tard'
            ]);
            exit;
        }

        if(in_array('email', $fields) && !FilterInput::email($body['email'])) {
            http_response_code(400);
            echo json_encode([
                'data'  => null,
                'error' => 'Le format de l\'adresse mail est incorrect'
            ]);
            exit;
        }
    }
    
    public function register(object $request): void
    {
        $body = $request->body;
        $ip = $request->ip;
        $action = 'register';
        $fields = ['firstname', 'lastname', 'email', 'password'];

        $this->validate($body, $fields, $ip, $action);

        if(!FilterInput::password($body['password'])) {
            http_response_code(400);
            echo json_encode([
                'data'  => null,
                'error' => 'Le format du mot de passe est incorrect'
            ]);
            exit;
        }

        if(UserModel::findByEmail($body['email'])) {
            http_response_code(409);
            echo json_encode([
                'data'  => null,
                'error' => 'L\'adresse mail est déjà utilisée'
            ]);
            exit;
        }

        $firstname = FilterInput::sanitize($body['firstname']);
        $lastname = FilterInput::sanitize($body['lastname']);
        $hashedPassword = password_hash($body['password'], PASSWORD_BCRYPT);

        UserModel::create($firstname, $lastname, $body['email'], $hashedPassword);

        RateLimit::hit('register', $ip);


        // TODO Captcha v3 ?
        // Email verification ?

        http_response_code(201);
        echo json_encode([
            'data'  => 'Inscription réussie. Un mail de confirmation vient de vous être envoyé.',
            'error' => null
        ]);
    }

    public function login(object $request): void
    {
        $body = $request->body;
        $ip = $request->ip;
        $action = 'login';
        $fields = ['email', 'password'];

        $this->validate($body, $fields, $ip, $action);

        $user = UserModel::findByEmail($body['email']);
        if(!$user) {
            http_response_code(401);
            echo json_encode([
                'data'  => null,
                'error' => 'Identifiants incorrects'
            ]);
            exit;
        }

        if(!password_verify($body['password'], $user['hash_pwd'])) {
            http_response_code(401);
            echo json_encode([
                'data'  => null,
                'error' => 'Identifiants incorrects'
            ]);
            exit;
        }

        $accessToken = $this->jwt->encode($user['id'], $user['role']);

        $refreshToken = bin2hex(random_bytes(32));
        UserModel::saveRefreshToken($user['id'], $refreshToken, time() + 604800);

        RateLimit::hit($action, $ip);

        http_response_code(200);
        echo json_encode([
            'data' => [
                'access_token'  => $accessToken,
                'refresh_token' => $refreshToken
            ],
            'error' => null
        ]);
    }

    public function logout(object $request): void
    {
        $body = $request->body;

        if(isset($body['refresh_token'])) {
            $refreshToken = $body['refresh_token'];
            UserModel::deleteRefreshToken($refreshToken);
        }

        http_response_code(200);
         echo json_encode([
            'data' => 'Déconnecté',
            'error' => null
        ]);
    }

    public function refresh(object $request): void
    {
        $body = $request->body;

        if(!isset($body['refresh_token'])) {
            http_response_code(400);
            echo json_encode([
                'data'  => null,
                'error' => 'Une erreur est survenue...'
            ]);
            exit;
        }

        $token = UserModel::findRefreshToken($body['refresh_token']);
        if(!$token || strtotime($token['expires_at']) < time()) {
            http_response_code(401);
            echo json_encode([
                'data'  => null,
                'error' => 'Une erreur est survenue...'
            ]);
            exit;
        }

        $user = UserModel::findById($token['owner']);
        $newAccessToken = $this->jwt->encode($user['id'], $user['role']);

        http_response_code(200);
        echo json_encode([
            'data' => [
                'access_token' => $newAccessToken
            ],
            'error' => null
        ]);
    }

    public function forgotPassword(object $request): void
    {
        $body = $request->body;
        $ip = $request->ip;
        $action = 'forgot_password';
        $fields = ['email'];

        $this->validate($body, $fields, $ip, $action);

        $user = UserModel::findByEmail($body['email']);
        
        if($user) {
            $resetToken = bin2hex(random_bytes(32));
            UserModel::saveResetToken($user['id'], $resetToken, time() + 3600);
            // TODO EmailService->sendResetPassword
        }

        RateLimit::hit($action, $ip);

        http_response_code(200);
        echo json_encode([
            'data' => 'Un email a été envoyé à l\'adresse indiquée.',
            'error' => null
        ]);
    }

    public function resetPassword(object $request): void
    {
        $body = $request->body;
        $ip = $request->ip;
        $action = 'reset_password';
        $fields = ['password', 'token'];

        $this->validate($body, $fields, $ip, $action);

        $token = UserModel::findResetToken($body['token']);

        if(!$token || strtotime($token['expires_at']) < time()) {
            http_response_code(401);
            echo json_encode([
                'data'  => null,
                'error' => 'Une erreur est survenue...'
            ]);
            exit;
        }

        $hash = password_hash($body['password'], PASSWORD_BCRYPT);
        UserModel::updatePassword($token['owner'], $hash);
        UserModel::deleteResetToken($token['value']);
        RateLimit::hit($action, $ip);

        http_response_code(200);
        echo json_encode([
            'data' => 'Le mot de passe a été mis à jour.',
            'error' => null
        ]);
    }
}
