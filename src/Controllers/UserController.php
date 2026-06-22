<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Security\FilterInput;
use App\Models\UserModel;

/**
 * Handles authenticated user profile and address management.
 */
class UserController
{
    /**
     * Returns the authenticated user's profile, excluding the password hash.
     *
     * @param object $request Request with user context
     */
    public function getMe(object $request): void
    {
        $user = UserModel::findById($request->user['id']);

        unset($user['hash_pwd']);
        
        http_response_code(200);
        echo json_encode([
            'data'  => $user,
            'message' => null,
            'error' => null
        ]);
    }

    /**
     * Updates allowed profile fields for the authenticated user.
     *
     * Sanitizes all string inputs before persisting.
     *
     * @param object $request Request with body containing fields to update
     */
    public function updateMe(object $request): void
    {
        $userId = $request->user['sub'];
        $body = $request->body;

        if(empty($body)) {
            http_response_code(400);
            echo json_encode([
                'data'    => null,
                'message' => null,
                'error'   => ['key' => 'api.no_data', 'params' => (object)[]]
            ]);
            exit;
        }

        foreach($body as &$b) {
            if(is_string($b)) {
                $b = FilterInput::sanitize($b);
            }
        }

        if(isset($body['email'])) {
            $mail = FilterInput::email($body['email']);
            if(!$mail) {
                http_response_code(400);
                echo json_encode([
                    'data'    => null,
                    'message' => null,
                    'error'   => ['key' => 'api.valid_mail', 'params' => (object)[]]
                ]);
                exit;
            }
        }

        UserModel::update($userId, $body);

        http_response_code(200);
        echo json_encode([
            'data'    => null,
            'message' => ['key' => 'api.user_update', 'params' => (object)[]],
            'error'   => null
        ]);
    }

    /**
     * Changes the authenticated user's password after verifying the current one.
     *
     * @param object $request Request with body: current_password, new_password
     */
    public function updatePassword(object $request): void
    {
        $userId = $request->user['sub'];
        $body = $request->body;
        $fields = ['new_password', 'current_password'];

        $missing = FilterInput::required($fields, $body);
        if(!empty($missing)) {
            http_response_code(400);
            echo json_encode([
                'data'    => null,
                'message' => null,
                'error'   => ['key' => 'api.fields', 'params' => ['fields' => implode(', ', $missing)]]
            ]);
            exit;
        }

        $user = UserModel::findById($userId);
        if(!password_verify($body['current_password'], $user['hash_pwd'])) {
            http_response_code(401);
            echo json_encode([
                'data'    => null,
                'message' => null,
                'error'   => ['key' => 'api.same_pwd', 'params' => (object)[]]
            ]);
            exit;
        }

        if(!FilterInput::password($body['new_password'])) {
            http_response_code(400);
            echo json_encode([
                'data'    => null,
                'message' => null,
                'error'   => ['key' => 'api.valid_pwd', 'params' => (object)[]]
            ]);
            exit;
        }

        $hash = password_hash($body['new_password'], PASSWORD_BCRYPT);

        UserModel::updatePassword($userId, $hash);

        http_response_code(200);
        echo json_encode([
            'data'    => null,
            'message' => ['key' => 'api.update_pwd', 'params' => (object)[]],
            'error'   => null
        ]);
    }

    /**
     * Deletes the authenticated user's account after password confirmation.
     *
     * @param object $request Request with body: password
     */
    public function deleteMe(object $request): void
    {
        $userId = $request->user['sub'];

        if(!isset($request->body['password'])) {
            http_response_code(400);
            echo json_encode([
                'data'    => null,
                'message' => null,
                'error'   => ['key' => 'api.required_pwd', 'params' => (object)[]]
            ]);
            exit;
        }
        $password = $request->body['password'];

        $user = UserModel::findById($userId);
        if(!password_verify($password, $user['hash_pwd'])) {
            http_response_code(401);
            echo json_encode([
                'data'    => null,
                'message' => null,
                'error'   => ['key' => 'api.same_pwd', 'params' => (object)[]]
            ]);
            exit;
        }

        UserModel::delete($userId);

        http_response_code(200);
        echo json_encode([
            'data'    => null,
            'message' => ['key' => 'api.user_del', 'params' => (object)[]],
            'error'   => null
        ]);
    }

    /**
     * Returns all saved addresses for the authenticated user.
     *
     * @param object $request Request with user context
     */
    public function getAddresses(object $request): void
    {
        $userId = $request->user['sub'];
        $addresses = UserModel::getAddresses($userId);

        http_response_code(200);
        echo json_encode([
            'data'  => $addresses,
            'message' => null,
            'error' => null
        ]);
    }

    /**
     * Adds a new address for the authenticated user.
     *
     * @param object $request Request with body: name, address, postal_code, city
     */
    public function addAddress(object $request): void
    {
        $body = $request->body;
        $userId = $request->user['sub'];
        $fields = ['name', 'address', 'postal_code', 'city'];

        $missing = FilterInput::required($fields, $body);
        if(!empty($missing)) {
            http_response_code(400);
            echo json_encode([
                'data'    => null,
                'message' => null,
                'error'   => ['key' => 'api.fields', 'params' => ['fields' => implode(', ', $missing)]]
            ]);
            exit;
        }

        foreach($body as &$b) {
            if(is_string($b)) {
                $b = FilterInput::sanitize($b);
            }
        }

        UserModel::addAddress($userId, $body);

        http_response_code(201);
        echo json_encode([
            'data'    => null,
            'message' => ['key' => 'api.address_add', 'params' => (object)[]],
            'error'   => null
        ]);
    }

    /**
     * Updates an existing address belonging to the authenticated user.
     *
     * @param object $request Request with body fields to update and params['id']
     */
    public function updateAddress(object $request): void
    {
        $body = $request->body;
        $userId = $request->user['sub'];
        $addressId = $request->params['id'];

        if(empty($body)) {
            http_response_code(400);
            echo json_encode([
                'data'    => null,
                'message' => null,
                'error'   => ['key' => 'api.no_data', 'params' => (object)[]]
            ]);
            exit;
        }

        foreach($body as &$b) {
            if(is_string($b)) {
                $b = FilterInput::sanitize($b);
            }
        }

        UserModel::updateAddress($addressId, $userId, $body);

        http_response_code(200);
        echo json_encode([
            'data'    => null,
            'message' => ['key' => 'api.address_update', 'params' => (object)[]],
            'error'   => null
        ]);
    }

    /**
     * Deletes an address belonging to the authenticated user.
     *
     * @param object $request Request with params['id']
     */
    public function deleteAddress(object $request): void
    {
        $userId = $request->user['sub'];
        $addressId = $request->params['id'];

        UserModel::deleteAddress($addressId, $userId);

        http_response_code(200);
        echo json_encode([
            'data'    => null,
            'message' => ['key' => 'api.address_del', 'params' => (object)[]],
            'error'   => null
        ]);
    }
}
