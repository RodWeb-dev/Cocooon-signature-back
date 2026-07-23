<?php

declare(strict_types=1);

namespace App\Core;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpClient\HttpOptions;
use App\Core\Exceptions\OdooException;

class OdooConnection
{
    private static ?self $instance = null;
    private HttpClientInterface $http;

    private readonly string $url;
    private readonly string $db;
    private readonly int $uid;
    private readonly string $apiKey;

    private function __construct()
    {
        $this->url = $_ENV["ODOO_URL"];
        $this->db = $_ENV["ODOO_DB"];
        $this->uid = (int) $_ENV["ODOO_UID"];
        $this->apiKey = $_ENV["ODOO_API_KEY"];

        $this->http = HttpClient::create();
        $headers = [
            "Content-type" => "application/json",
        ];
        $this->http = $this->http->withOptions(
            new HttpOptions()
                ->setBaseUri($this->url)
                ->setHeaders($headers)
                ->toArray(),
        );
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function call(string $service, string $method, array $args): mixed
    {
        $payload = [
            "jsonrpc" => "2.0",
            "method" => "call",
            "params" => [
                "service" => $service,
                "method" => $method,
                "args" => $args,
            ],
            "id" => random_int(1, 99999),
        ];

        try {
            $response = $this->http->request("POST", "/jsonrpc", [
                "json" => $payload,
            ]);

            $decoded = $response->toArray();
        } catch (\Throwable $e) {
            throw new OdooException(
                "Odoo connection failed: " . $e->getMessage(),
            );
        }
        if (isset($decoded["error"])) {
            throw new OdooException(
                $decoded["error"]["message"] ?? "Unknown Odoo error",
            );
        }
        return $decoded["result"];
    }

    public function executeKw(
        string $model,
        string $method,
        array $args = [],
        array $kwargs = [],
    ): mixed {
        $result = $this->call("object", "execute_kw", [
            $this->db,
            $this->uid,
            $this->apiKey,
            $model,
            $method,
            $args,
            $kwargs,
        ]);

        return $result;
    }
}
