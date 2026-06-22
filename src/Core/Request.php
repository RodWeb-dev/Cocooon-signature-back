<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Immutable value object representing an incoming HTTP request.
 */
class Request
{
    public readonly string $method;
    public readonly string $uri;
    public readonly string $ip;
    /** @var array<string, string> URL path parameters extracted by the router */
    public readonly array $params;
    /** @var array <string, string> GET query string */
    public readonly array $query;
    /** @var array<string, mixed> JSON-decoded request body */
    public readonly array $body;
    /** @var array<string, mixed>|null JWT payload; null on public routes */
    public readonly ?array $user;

    /**
     * @param array<string, string>     $params
     * @param array<string, mixed>      $body
     * @param array<string, mixed>|null $user
     */
    public function __construct(
        string $method,
        string $uri,
        array $params = [],
        array $query = [],
        array $body = [],
        ?array $user = null
    ) {
        $this->method = $method;
        $this->uri    = $uri;
        $this->ip     = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $this->params = $params;
        $this->query  = $query;
        $this->body   = $body;
        $this->user   = $user;
    }

    /**
     * Build from PHP superglobals and php://input.
     *
     * @param array<string, string>     $params
     * @param array<string, mixed>|null $user
     */
    public static function fromGlobals(array $params = [], ?array $user = null): self
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $body   = json_decode(file_get_contents('php://input'), true) ?? [];

        return new self($method, $uri, $params, $_GET, $body, $user);
    }

    public function lang(): string
    {
        $lang = $this->query['lang'] ?? 'fr';
        return in_array($lang, ['fr', 'en'], true) ? $lang : 'fr';
    }
}
