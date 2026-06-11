<?php
declare(strict_types=1);

namespace App\Core;

use App\Core\Security\JWT;
use App\Core\Exceptions\NotFoundException;
use App\Core\Exceptions\MethodNotAllowedException;
use App\Core\Exceptions\UnauthorizedException;

/**
 * Matches incoming HTTP requests to controller actions and enforces JWT authentication.
 */
class Router
{
    /** @var array<int, array{method: string, path: string, handler: array, auth: bool}> */
    private array $routes = [];
    private JWT $jwt;

    public function __construct()
    {
        $this->jwt = new JWT(
            $_ENV['JWT_SECRET'],
            (int) $_ENV['JWT_EXPIRATION']
        );
    }

    /**
     * @param array $handler  [ControllerClass::class, 'actionMethod']
     * @param bool  $auth     Require a valid JWT before calling the handler
     */
    private function addRoute(string $method, string $path, array $handler, bool $auth): void
    {
        $this->routes[] = [
            'method'  => $method,
            'path'    => $path,
            'handler' => $handler,
            'auth'    => $auth
        ];
    }

    /** @param array $handler  [ControllerClass::class, 'actionMethod'] */
    public function get(string $path, array $handler, bool $auth = false): void
    {
        $this->addRoute('GET', $path, $handler, $auth);
    }

    /** @param array $handler  [ControllerClass::class, 'actionMethod'] */
    public function post(string $path, array $handler, bool $auth = false): void
    {
        $this->addRoute('POST', $path, $handler, $auth);
    }

    /** @param array $handler  [ControllerClass::class, 'actionMethod'] */
    public function patch(string $path, array $handler, bool $auth = false): void
    {
        $this->addRoute('PATCH', $path, $handler, $auth);
    }

    /** @param array $handler  [ControllerClass::class, 'actionMethod'] */
    public function delete(string $path, array $handler, bool $auth = false): void
    {
        $this->addRoute('DELETE', $path, $handler, $auth);
    }

    /**
     * @throws NotFoundException         if no route matches the URI
     * @throws MethodNotAllowedException if the URI matches but the HTTP method does not
     * @throws UnauthorizedException     if the route requires auth and the token is absent or invalid
     */
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

    /**
     * Converts {param} placeholders to a regex and extracts named values.
     *
     * @return array<string, string>|null Named URL parameters, or null if the pattern does not match
     */
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

    /**
     * @return array<string, mixed> Decoded JWT payload (sub, role, iat, exp)
     * @throws UnauthorizedException if the Authorization header is absent or the token is invalid
     */
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
