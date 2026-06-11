<?php
declare(strict_types=1);

namespace App\Core;

class Request
{
    public readonly string $method;
    public readonly string $uri;
    public readonly array $params;
    public readonly array $body;
    public readonly ?array $user;

    public function __construct(
        string $method,
        string $uri,
        array $params = [],
        array $body = [],
        ?array $user = null
    ) {
        $this->method = $method;
        $this->uri    = $uri;
        $this->params = $params;
        $this->body   = $body;
        $this->user   = $user;
    }

    public static function fromGlobals(array $params = [], ?array $user = null): self
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $body   = json_decode(file_get_contents('php://input'), true) ?? [];

        return new self($method, $uri, $params, $body, $user);
    }
}
