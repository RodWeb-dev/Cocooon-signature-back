<?php

declare(strict_types=1);

namespace App\Core\Security;

use Firebase\JWT\JWT as FirebaseJWT;
use Firebase\JWT\Key;
use App\Core\Exceptions\UnauthorizedException;

class JWT
{
    private string $secret;
    private int $expiration;

    public function __construct(string $secret, int $expiration)
    {
        $this->secret     = $secret;
        $this->expiration = $expiration;
    }

    public function encode(string $userId, string $role): string
    {
        $payload = [
            'sub'  => $userId,
            'role' => $role,
            'iat'  => time(),
            'exp'  => time() + $this->expiration
        ];

        return FirebaseJWT::encode($payload, $this->secret, 'HS256');
    }

    public function decode(string $token): array
    {
        try {
            $decoded = FirebaseJWT::decode($token, new Key($this->secret, 'HS256'));
            return (array) $decoded;
        } catch (\Exception $e) {
            throw new UnauthorizedException();
        }
    }
}
