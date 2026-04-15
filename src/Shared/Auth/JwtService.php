<?php

declare(strict_types=1);

namespace App\Shared\Auth;

use App\Shared\Support\Env;

final class JwtService
{
    public function issue(array $claims): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $now = time();
        $ttl = (int) Env::get('JWT_TTL_SECONDS', '86400');

        $payload = array_merge($claims, [
            'iss' => Env::get('JWT_ISSUER', 'fresh2u-api'),
            'iat' => $now,
            'exp' => $now + $ttl,
        ]);

        $encodedHeader = $this->base64UrlEncode(json_encode($header, JSON_UNESCAPED_SLASHES));
        $encodedPayload = $this->base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES));
        $signature = hash_hmac('sha256', $encodedHeader . '.' . $encodedPayload, $this->secret(), true);

        return $encodedHeader . '.' . $encodedPayload . '.' . $this->base64UrlEncode($signature);
    }

    public function parse(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$h, $p, $s] = $parts;
        $expected = $this->base64UrlEncode(hash_hmac('sha256', $h . '.' . $p, $this->secret(), true));
        if (!hash_equals($expected, $s)) {
            return null;
        }

        $payload = json_decode($this->base64UrlDecode($p), true);
        if (!is_array($payload)) {
            return null;
        }

        if (($payload['exp'] ?? 0) < time()) {
            return null;
        }

        return $payload;
    }

    private function secret(): string
    {
        return Env::get('APP_KEY', 'unsafe-local-key');
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        return base64_decode(strtr($value, '-_', '+/')) ?: '';
    }
}
