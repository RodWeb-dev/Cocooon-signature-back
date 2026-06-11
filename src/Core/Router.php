<?php
declare(strict_types=1);

namespace App\Core;

use App\Core\Security\JWT;
use App\Core\Exceptions\NotFoundException;
use App\Core\Exceptions\MethodNotAllowedException;
use App\Core\Exceptions\UnauthorizedException;

class Router
{
    private array $routes = [];
    private JWT $jwt;

    public function __construct()
    {
        $this->jwt = new JWT(
            $_ENV['JWT_SECRET'],
            (int) $_ENV['JWT_EXPIRATION']
        );
    }

    private function addRoute(string $method, string $path, array $handler, bool $auth): void
    {
        $this->routes[] = [
            'method'  => $method,
            'path'    => $path,
            'handler' => $handler,
            'auth'    => $auth
        ];
    }

    public function get(string $path, array $handler, bool $auth = false): void
    {
        $this->addRoute('GET', $path, $handler, $auth);
    }

    public function post(string $path, array $handler, bool $auth = false): void
    {
        $this->addRoute('POST', $path, $handler, $auth);
    }

    public function patch(string $path, array $handler, bool $auth = false): void
    {
        $this->addRoute('PATCH', $path, $handler, $auth);
    }

    public function delete(string $path, array $handler, bool $auth = false): void
    {
        $this->addRoute('DELETE', $path, $handler, $auth);
    }

    public function dispatch(string $method, string $uri): void
    {
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = rtrim($uri, '/') ?: '/';

        $matchedPaths = [];

        foreach ($this->routes as $route) {
            $params = $this->match($route['path'], $uri);

            if ($params === null) {
                continue;
            }

            $matchedPaths[] = $route['method'];

            if ($route['method'] !== $method) {
                continue;
            }

            $user = null;
            if ($route['auth']) {
                $user = $this->checkAuth();
            }

            $request = Request::fromGlobals($params, $user);

            [$controllerClass, $action] = $route['handler'];
            $controller = new $controllerClass();
            $controller->$action($request);
            return;
        }

        if (!empty($matchedPaths)) {
            throw new MethodNotAllowedException();
        }

        throw new NotFoundException();
    }

    private function match(string $path, string $uri): ?array
    {
        preg_match_all('/\{(\w+)\}/', $path, $paramNames);

        $pattern = preg_replace('/\{(\w+)\}/', '([^/]+)', $path);
        $pattern = '#^' . $pattern . '$#';

        if (!preg_match($pattern, $uri, $matches)) {
            return null;
        }

        array_shift($matches);
        return array_combine($paramNames[1], $matches) ?: [];
    }

    private function checkAuth(): array
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (!str_starts_with($header, 'Bearer ')) {
            throw new UnauthorizedException();
        }

        $token = substr($header, 7);
        return $this->jwt->decode($token);
    }
}
