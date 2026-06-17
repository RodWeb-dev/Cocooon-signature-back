<?php

declare(strict_types=1);

namespace App\Core\Security;

use Firebase\JWT\JWT as FirebaseJWT;
use Firebase\JWT\Key;
use App\Core\Exceptions\UnauthorizedException;

/**
 * HS256 JWT wrapper around firebase/php-jwt.
 */
class JWT
{
    private string $secret;
    private int $expiration; // seconds

    /**
     * @param string $secret     Signing key (JWT_SECRET env var)
     * @param int    $expiration Token lifetime in seconds (JWT_EXPIRATION env var)
     */
    public function __construct(string $secret, int $expiration)
    {
        $this->secret     = $secret;
        $this->expiration = $expiration;
    }

    /** @return string Signed JWT containing sub, role, iat, exp */
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

    /**
     * @return array<string, mixed> Decoded payload (sub, role, iat, exp)
     * @throws UnauthorizedException if the token is invalid or expired
     */
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
