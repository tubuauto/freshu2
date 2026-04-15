<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Controllers;

use App\Modules\Accounting\Services\AccountingService;
use App\Modules\Auth\Services\AuthService;
use App\Shared\Http\Request;

final class AccountingController
{
    public function __construct(
        private readonly AccountingService $service,
        private readonly AuthService $authService,
    ) {
    }

    public function calculateEarnings(Request $request): array
    {
        $user = $this->authService->requireUser($request, ['admin', 'merchant']);
        $tenant = (string) ($request->header('x-tenant-merchant-id') ?? $user['tenant_merchant_id'] ?? '');
        $result = $this->service->calculateEarnings($request->body, $tenant);
        return [201, ['data' => $result]];
    }
}
