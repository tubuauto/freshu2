<?php

declare(strict_types=1);

namespace App\Shared\Tenancy;

final class TenantContext
{
    public function resolve(array $user, ?string $headerTenant): ?string
    {
        $role = $user['role'] ?? '';
        if ($role === 'admin') {
            return $headerTenant ?: ($user['tenant_merchant_id'] ?? null);
        }

        return $user['tenant_merchant_id'] ?? $headerTenant;
    }
}
