<?php

declare(strict_types=1);

namespace App\Modules\Coupons\Controllers;

use App\Modules\Auth\Services\AuthService;
use App\Modules\Coupons\Services\CouponService;
use App\Shared\Http\Request;

final class CouponController
{
    public function __construct(
        private readonly CouponService $couponService,
        private readonly AuthService $authService,
    ) {
    }

    public function createCoupon(Request $request): array
    {
        $user = $this->authService->requireUser($request, ['merchant', 'admin']);
        $tenantMerchantId = (string) ($request->header('x-tenant-merchant-id') ?? $user['tenant_merchant_id'] ?? '');
        $coupon = $this->couponService->createCoupon($request->body, $tenantMerchantId);
        return [201, ['data' => $coupon]];
    }

    public function claimCoupon(Request $request, array $params): array
    {
        $user = $this->authService->requireUser($request, ['customer', 'leader', 'admin']);
        $tenantMerchantId = (string) ($request->header('x-tenant-merchant-id') ?? $user['tenant_merchant_id'] ?? '');
        $claim = $this->couponService->claimCoupon($tenantMerchantId, $params['id'], (string) $user['id']);
        return [201, ['data' => $claim]];
    }

    public function availableCoupons(Request $request): array
    {
        $user = $this->authService->requireUser($request, ['customer', 'leader', 'admin']);
        $tenantMerchantId = (string) ($request->header('x-tenant-merchant-id') ?? $user['tenant_merchant_id'] ?? '');
        $coupons = $this->couponService->availableCoupons($tenantMerchantId, (string) $user['id']);
        return [200, ['data' => $coupons]];
    }
}
