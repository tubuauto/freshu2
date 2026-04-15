<?php

declare(strict_types=1);

namespace App\Modules\Coupons\Services;

use App\Modules\Coupons\Repositories\CouponRepository;
use App\Shared\Support\Exceptions\HttpException;
use PDO;

final class CouponService
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly CouponRepository $couponRepository,
    ) {
    }

    public function createCoupon(array $payload, string $tenantMerchantId): array
    {
        $title = trim((string) ($payload['title'] ?? ''));
        if ($title === '') {
            throw new HttpException(422, 'Coupon title is required');
        }

        $couponType = (string) ($payload['coupon_type'] ?? 'threshold_discount');
        $allowedTypes = ['threshold_discount', 'no_threshold', 'percentage_discount', 'new_user'];
        if (!in_array($couponType, $allowedTypes, true)) {
            throw new HttpException(422, 'Invalid coupon_type');
        }

        return $this->couponRepository->createCoupon([
            'tenant_merchant_id' => $tenantMerchantId,
            'merchant_id' => $payload['merchant_id'] ?? $tenantMerchantId,
            'product_id' => $payload['product_id'] ?? null,
            'coupon_type' => $couponType,
            'scope' => $payload['scope'] ?? 'merchant',
            'title' => $title,
            'min_order_amount' => number_format(round((float) ($payload['min_order_amount'] ?? 0), 2), 2, '.', ''),
            'discount_amount' => number_format(round((float) ($payload['discount_amount'] ?? 0), 2), 2, '.', ''),
            'discount_rate' => number_format(round((float) ($payload['discount_rate'] ?? 0), 4), 4, '.', ''),
            'total_qty' => (int) ($payload['total_qty'] ?? 0),
            'starts_at' => $payload['starts_at'] ?? null,
            'ends_at' => $payload['ends_at'] ?? null,
            'status' => $payload['status'] ?? 'active',
        ]);
    }

    public function claimCoupon(string $tenantMerchantId, string $couponId, string $userId): array
    {
        $coupon = $this->couponRepository->findCoupon($couponId);
        if ($coupon === null) {
            throw new HttpException(404, 'Coupon not found');
        }

        if ((string) $coupon['tenant_merchant_id'] !== $tenantMerchantId) {
            throw new HttpException(403, 'Coupon not in tenant scope');
        }

        return $this->couponRepository->claimCoupon($tenantMerchantId, $couponId, $userId);
    }

    public function availableCoupons(string $tenantMerchantId, string $userId): array
    {
        return $this->couponRepository->listAvailable($tenantMerchantId, $userId);
    }

    public function applyCouponToOrder(
        string $tenantMerchantId,
        string $memberOrderId,
        string $userId,
        string $couponId,
        string $claimId,
        float $orderSubtotal
    ): array {
        $coupon = $this->couponRepository->findCoupon($couponId);
        if ($coupon === null) {
            throw new HttpException(404, 'Coupon not found');
        }

        if ((string) $coupon['tenant_merchant_id'] !== $tenantMerchantId) {
            throw new HttpException(403, 'Coupon not in tenant scope');
        }

        $claim = $this->couponRepository->findClaim($claimId);
        if ($claim === null || (string) $claim['coupon_id'] !== $couponId || (string) $claim['user_id'] !== $userId) {
            throw new HttpException(422, 'Invalid coupon claim');
        }

        if ((string) $claim['status'] !== 'claimed') {
            throw new HttpException(422, 'Coupon claim is not available');
        }

        $minOrder = (float) $coupon['min_order_amount'];
        if ($orderSubtotal < $minOrder) {
            throw new HttpException(422, 'Order subtotal does not meet coupon threshold');
        }

        $discount = $this->calculateDiscount($coupon, $orderSubtotal);
        $discount = min($discount, $orderSubtotal);

        $usage = $this->couponRepository->createUsage([
            'tenant_merchant_id' => $tenantMerchantId,
            'coupon_id' => $couponId,
            'claim_id' => $claimId,
            'user_id' => $userId,
            'member_order_id' => $memberOrderId,
            'discount_amount' => number_format(round($discount, 2), 2, '.', ''),
        ]);

        $this->couponRepository->markClaimUsed($claimId);

        return [
            'coupon' => $coupon,
            'usage' => $usage,
            'discount_amount' => round($discount, 2),
        ];
    }

    private function calculateDiscount(array $coupon, float $subtotal): float
    {
        $type = (string) $coupon['coupon_type'];
        if ($type === 'percentage_discount') {
            return round($subtotal * (float) $coupon['discount_rate'], 2);
        }

        return round((float) $coupon['discount_amount'], 2);
    }
}
