<?php

declare(strict_types=1);

namespace App\Modules\Coupons\Repositories;

use PDO;

final class CouponRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function createCoupon(array $data): array
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO coupons (
                id, tenant_merchant_id, merchant_id, product_id, coupon_type, scope, title,
                min_order_amount, discount_amount, discount_rate, total_qty, claimed_qty,
                starts_at, ends_at, status, created_at, updated_at
            ) VALUES (
                gen_random_uuid(), :tenant_merchant_id, :merchant_id, :product_id, :coupon_type, :scope, :title,
                :min_order_amount, :discount_amount, :discount_rate, :total_qty, 0,
                :starts_at, :ends_at, :status, NOW(), NOW()
            )
            RETURNING *'
        );

        $stmt->execute([
            'tenant_merchant_id' => $data['tenant_merchant_id'],
            'merchant_id' => $data['merchant_id'],
            'product_id' => $data['product_id'],
            'coupon_type' => $data['coupon_type'],
            'scope' => $data['scope'],
            'title' => $data['title'],
            'min_order_amount' => $data['min_order_amount'],
            'discount_amount' => $data['discount_amount'],
            'discount_rate' => $data['discount_rate'],
            'total_qty' => $data['total_qty'],
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'status' => $data['status'],
        ]);

        return $stmt->fetch() ?: [];
    }

    public function findCoupon(string $couponId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM coupons WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $couponId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function claimCoupon(string $tenantMerchantId, string $couponId, string $userId): array
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO coupon_user_claims (
                id, tenant_merchant_id, coupon_id, user_id, status, claimed_at
            ) VALUES (
                gen_random_uuid(), :tenant_merchant_id, :coupon_id, :user_id, :status, NOW()
            )
            ON CONFLICT (coupon_id, user_id) DO UPDATE SET status = EXCLUDED.status
            RETURNING *'
        );

        $stmt->execute([
            'tenant_merchant_id' => $tenantMerchantId,
            'coupon_id' => $couponId,
            'user_id' => $userId,
            'status' => 'claimed',
        ]);

        $this->pdo->prepare('UPDATE coupons SET claimed_qty = claimed_qty + 1, updated_at = NOW() WHERE id = :id')
            ->execute(['id' => $couponId]);

        return $stmt->fetch() ?: [];
    }

    public function listAvailable(string $tenantMerchantId, string $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.*, cuc.id AS claim_id, cuc.status AS claim_status
            FROM coupons c
            LEFT JOIN coupon_user_claims cuc ON cuc.coupon_id = c.id AND cuc.user_id = :user_id
            WHERE c.tenant_merchant_id = :tenant_merchant_id
              AND c.status = :status
              AND (c.starts_at IS NULL OR c.starts_at <= NOW())
              AND (c.ends_at IS NULL OR c.ends_at >= NOW())
            ORDER BY c.created_at DESC'
        );

        $stmt->execute([
            'tenant_merchant_id' => $tenantMerchantId,
            'user_id' => $userId,
            'status' => 'active',
        ]);

        return $stmt->fetchAll();
    }

    public function findClaim(string $claimId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM coupon_user_claims WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $claimId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function markClaimUsed(string $claimId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE coupon_user_claims
            SET status = :status,
                used_at = NOW()
            WHERE id = :id'
        );
        $stmt->execute([
            'id' => $claimId,
            'status' => 'used',
        ]);
    }

    public function createUsage(array $data): array
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO coupon_usages (
                id, tenant_merchant_id, coupon_id, claim_id, user_id, member_order_id,
                discount_amount, status, used_at
            ) VALUES (
                gen_random_uuid(), :tenant_merchant_id, :coupon_id, :claim_id, :user_id, :member_order_id,
                :discount_amount, :status, NOW()
            )
            RETURNING *'
        );

        $stmt->execute([
            'tenant_merchant_id' => $data['tenant_merchant_id'],
            'coupon_id' => $data['coupon_id'],
            'claim_id' => $data['claim_id'],
            'user_id' => $data['user_id'],
            'member_order_id' => $data['member_order_id'],
            'discount_amount' => $data['discount_amount'],
            'status' => 'used',
        ]);

        return $stmt->fetch() ?: [];
    }

    public function releaseUsage(string $usageId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE coupon_usages
            SET status = :status,
                released_at = NOW()
            WHERE id = :id'
        );
        $stmt->execute(['id' => $usageId, 'status' => 'released']);
    }
}
