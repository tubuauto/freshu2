<?php

declare(strict_types=1);

namespace App\Shared\Http;

final class Request
{
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        public readonly array $headers,
        public readonly array $body,
        public readonly ?array $user = null,
        public readonly ?string $tenantMerchantId = null,
    ) {
    }

    public static function fromGlobals(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = strtok($uri, '?') ?: '/';

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (!str_starts_with($key, 'HTTP_')) {
                continue;
            }
            $headerName = str_replace('_', '-', substr($key, 5));
            $headers[strtolower($headerName)] = (string) $value;
        }

        $raw = file_get_contents('php://input') ?: '';
        $body = [];
        if ($raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $body = $decoded;
            }
        }

        return new self($method, $path, $_GET, $headers, $body);
    }

    public function withUserAndTenant(array $user, ?string $tenantMerchantId): self
    {
        return new self(
            $this->method,
            $this->path,
            $this->query,
            $this->headers,
            $this->body,
            $user,
            $tenantMerchantId
        );
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }
}
