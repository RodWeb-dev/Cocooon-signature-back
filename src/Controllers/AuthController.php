<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Security\FilterInput;
use App\Core\Security\JWT;
use App\Core\Security\RateLimit;
use App\Models\UserModel;
use App\Models\TokenModel;
use App\Models\NewsletterModel;
use App\Services\EmailService;

/**
 * Handles authentication endpoints: registration, login, logout, token refresh,
 * and password recovery.
 */
class AuthController
{
    private JWT $jwt;
    private EmailService $emailService;

    public function __construct()
    {
        $this->jwt = new JWT($_ENV['JWT_SECRET'], (int) $_ENV['JWT_EXPIRATION']);
        $this->emailService = new EmailService();
    }

    /**
     * Validates required fields, rate limit, and email format.
     * Terminates with an HTTP error response if any check fails.
     *
     * @param array  $body   Request body data
     * @param array  $fields Required field names
     * @param string $ip     Client IP address
     * @param string $action Rate limit action key
     */
    private function validate(array $body, array $fields, string $ip, string $action): void
    {
        $missing = FilterInput::required($fields, $body);
        if(!empty($missing)) {
            http_response_code(400);
            echo json_encode([
                'data'    => null,
                'message' => null,
                'error'   => [
                    'key'    => 'api.fields',
                    'params' => ['fields' => implode(', ', $missing)]
                ]
            ]);
            exit;
        }

        if(!RateLimit::check($action, $ip)) {
            http_response_code(429);
            echo json_encode([
                'data'    => null,
                'message' => null,
                'error'   => [
                    'key'    => 'api.rate_limit',
                    'params' => (object)[]
                ]
            ]);
            exit;
        }

        if(in_array('email', $fields) && !FilterInput::email($body['email'])) {
            http_response_code(400);
            echo json_encode([
                'data'    => null,
                'message' => null,
                'error'   => [
                    'key'    => 'api.valid_mail',
                    'params' => (object)[]
                ]
            ]);
            exit;
        }
    }
    
    /**
     * Registers a new user account.
     *
     * Validates required fields, password strength, and email uniqueness.
     * Responds 201 on success, 400/409 on validation or conflict errors.
     *
     * @param object $request Request with body: firstname, lastname, email, password
     */
    public function register(object $request): void
    {
        $body = $request->body;
        $ip = $request->ip;
        $action = 'register';
        $fields = ['firstname', 'lastname', 'email', 'password'];

        $this->validate($body, $fields, $ip, $action);

        RateLimit::hit('register', $ip);
        // TODO Captcha v3 ?

        if(!FilterInput::password($body['password'])) {
            http_response_code(400);
            echo json_encode([
                'data'    => null,
                'message' => null,
                'error'   => [
                    'key'    => 'api.valid_pwd',
                    'params' => (object)[]
                ]
            ]);
            exit;
        }

        if(UserModel::findByEmail($body['email'])) {
            http_response_code(409);
            echo json_encode([
                'data'    => null,
                'message' => null,
                'error'   => [
                    'key'    => 'api.used_mail',
                    'params' => (object)[]
                ]
            ]);
            exit;
        }

        $firstname = FilterInput::sanitize($body['firstname']);
        $lastname = FilterInput::sanitize($body['lastname']);
        $subscribed = $body['newsletter'] ?? false;
        $hashedPassword = password_hash($body['password'], PASSWORD_BCRYPT);

        $userId = UserModel::create($firstname, $lastname, $body['email'], $hashedPassword);

        if($subscribed) {
            NewsletterModel::subscribe($body['email'], $userId);
        }

        $token = bin2hex(random_bytes(32));
        $url = $_ENV['FRONTEND_URL'] . '/verification-email/' . $token;
        $to = [
            'email' => $body['email'],
            'name'  => "$firstname $lastname"
        ];

        TokenModel::saveVerifyToken($userId, $token, time() + 86400);
        $this->emailService->sendWelcome($to, $firstname, $url);

        http_response_code(201);
        echo json_encode([
            'data'    => null,
            'message' => ['key' => 'api.send_welcome', 'params' => (object)[]],
            'error'   => null
        ]);
    }

    /**
     * Authenticates a user and returns access and refresh tokens.
     *
     * Responds 200 with tokens on success, 401 on invalid credentials.
     *
     * @param object $request Request with body: email, password
     */
    public function login(object $request): void
    {
        $body = $request->body;
        $ip = $request->ip;
        $action = 'login';
        $fields = ['email', 'password'];

        $this->validate($body, $fields, $ip, $action);
        if(!RateLimit::check($action, $body['email'])) {
            http_response_code(429);
            echo json_encode([
                'data'    => null,
                'message' => null,
                'error'   => ['key' => 'api.rate_limit', 'params' => (object)[]],
            ]);
            exit;
        }

        $user = UserModel::findByEmail($body['email']);
        if(!$user) {
            http_response_code(401);
            echo json_encode([
                'data'    => null,
                'message' => null,
                'error'   => ['key' => 'api.credentials', 'params' => (object)[]],
            ]);

            RateLimit::hit($action, $ip);
            exit;
        }

        if(!password_verify($body['password'], $user['hash_pwd'])) {
            http_response_code(401);
            echo json_encode([
                'data'    => null,
                'message' => null,
                'error'   => ['key' => 'api.credentials', 'params' => (object)[]],
            ]);

            RateLimit::hit($action, $ip);
            RateLimit::hit($action, $body['email']);
            exit;
        }

        RateLimit::reset($action, $ip);
        RateLimit::reset($action, $body['email']);

        $accessToken = $this->jwt->encode($user['id'], $user['role']);

        $refreshToken = bin2hex(random_bytes(32));
        TokenModel::saveRefreshToken($user['id'], $refreshToken, time() + 604800);

        setcookie('refresh_token', $refreshToken, [
            'expires'  => time() + 604800,
            'path'     => '/api/auth/refresh',
            'domain'   => 'cocoon-signature.fr',
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);

        http_response_code(200);
        echo json_encode([
            'data'    => ['access_token' => $accessToken],
            'message' => ['key' => 'api.login', 'params' => (object)[]],
            'error'   => null,
        ]);
    }

    /**
     * Invalidates a refresh token.
     *
     * @param object $request Request with optional body: refresh_token
     */
    public function logout(object $request): void
    {
        if (isset($_COOKIE['refresh_token'])) {
            TokenModel::deleteRefreshToken($_COOKIE['refresh_token']);
    
            setcookie('refresh_token', '', [
                'expires'  => time() - 3600,
                'path'     => '/api/auth/refresh',
                'domain'   => 'cocoon-signature.fr',
                'secure'   => true,
                'httponly' => true,
                'samesite' => 'Strict',
            ]);
        }
    
        http_response_code(200);
        echo json_encode([
            'data'    => null,
            'message' => ['key' => 'api.logout', 'params' => (object)[]],
            'error'   => null,
        ]);
    }

    /**
     * Issues a new access token from a valid, non-expired refresh token.
     *
     * Responds 200 with a new access_token on success, 401 if the token is invalid or expired.
     *
     * @param object $request Request with body: refresh_token
     */
    public function refresh(object $request): void
    {
        if(!isset($_COOKIE['refresh_token'])) {
            http_response_code(400);
            echo json_encode([
                'data'    => null,
                'message' => null,
                'error'   => ['key' => 'api.error', 'params' => (object)[]],
            ]);
            exit;
        }

        $token = TokenModel::findRefreshToken($_COOKIE['refresh_token']);
        if(!$token || strtotime($token['expires_at']) < time()) {
            if($token) {TokenModel::deleteRefreshToken($_COOKIE['refresh_token']);}
            http_response_code(401);
            echo json_encode([
                'data'    => null,
                'message' => null,
                'error'   => ['key' => 'api.error', 'params' => (object)[]],
            ]);
            exit;
        }

        $user = UserModel::findById($token['owner']);
        
        $newAccessToken = $this->jwt->encode($user['id'], $user['role']);
        $newRefreshToken = bin2hex(random_bytes(32));

        TokenModel::deleteRefreshToken($_COOKIE['refresh_token']);
        TokenModel::saveRefreshToken($user['id'], $newRefreshToken, time() + 604800);

        setcookie('refresh_token', $newRefreshToken, [
            'expires'  => time() + 604800,
            'path'     => '/api/auth/refresh',
            'domain'   => 'cocoon-signature.fr',
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);

        http_response_code(200);
        echo json_encode([
            'data' => [
                'access_token' => $newAccessToken
            ],
            'message' => null,
            'error' => null
        ]);
    }

    /**
     * Triggers a password reset email for the given address.
     *
     * Always responds 200 to avoid email enumeration.
     *
     * @param object $request Request with body: email
     */
    public function forgotPassword(object $request): void
    {
        $body = $request->body;
        $ip = $request->ip;
        $action = 'forgot-password';
        $fields = ['email'];

        $this->validate($body, $fields, $ip, $action);

        $user = UserModel::findByEmail($body['email']);
        
        if($user) {
            $resetToken = bin2hex(random_bytes(32));
            TokenModel::saveResetToken($user['id'], $resetToken, time() + 3600);
            $to = [
                'email' => $user['email'],
                'name'  => "{$user['firstname']} {$user['lastname']}"
            ];
            $url = $_ENV['FRONTEND_URL'] . '/reinitialiser-mot-de-passe/' . $resetToken;
            
            $this->emailService->sendResetPassword($to, $user['firstname'], $url);
        }

        RateLimit::hit($action, $ip);

        http_response_code(200);
        echo json_encode([
            'data'    => null,
            'message' => ['key' => 'api.forgot', 'params' => (object)[]],
            'error'   => null
        ]);
    }

    /**
     * Resets the user password using a valid reset token.
     *
     * Responds 200 on success, 401 if the token is invalid or expired.
     *
     * @param object $request Request with body: password, token
     */
    public function resetPassword(object $request): void
    {
        $body = $request->body;
        $fields = ['password', 'token'];

        $missing = FilterInput::required($fields, $body);
        if(!empty($missing)) {
            http_response_code(400);
            echo json_encode([
                'data'    => null,
                'message' => null,
                'error'   => ['key' => 'api.fields', 'params' => ['fields' => implode(', ', $missing)]],
            ]);
            exit;
        }

        $token = TokenModel::findResetToken($body['token']);

        if(!$token || strtotime($token['expires_at']) < time()) {
            http_response_code(401);
            echo json_encode([
                'data'    => null,
                'message' => null,
                'error'   => ['key' => 'api.error', 'params' => (object)[]],
            ]);
            exit;
        }

        $hash = password_hash($body['password'], PASSWORD_BCRYPT);
        UserModel::updatePassword($token['owner'], $hash);
        TokenModel::deleteResetToken($token['value']);

        http_response_code(200);
        echo json_encode([
            'data'    => null,
            'message' => ['key' => 'api.reset', 'params' => (object)[]],
            'error'   => null
        ]);
    }

    /**
     * Marks the user's email as verified using a one-time token sent by email.
     *
     * Responds 200 on success, 401 if the token is invalid or expired.
     *
     * @param object $request Request with params['token']
     */
    public function verifyEmail(object $request): void
    {
        $token = $request->params['token'];

        $dbtoken = TokenModel::findVerifyToken($token);
        if(!$dbtoken || strtotime($dbtoken['expires_at']) < time()) {
            http_response_code(401);
            echo json_encode([
                'data'    => null,
                'message' => null,
                'error'   => ['key' => 'api.error', 'params' => (object)[]],
            ]);
            exit;
        }

        UserModel::markEmailVerified($dbtoken['owner']);
        TokenModel::deleteVerifyToken($token);

        http_response_code(200);
        echo json_encode([
            'data'    => null,
            'message' => ['key' => 'api.verify', 'params' => (object)[]],
            'error'   => null
        ]);
    }
}
