<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Repositories;

use PDO;

final class AccountingRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findLeaderOrder(string $leaderOrderId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM leader_orders WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $leaderOrderId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function createCommission(array $data): array
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO commissions (
                id, tenant_merchant_id, member_order_id, leader_user_id, commission_type, amount, state, created_at
            ) VALUES (
                gen_random_uuid(), :tenant_merchant_id, :member_order_id, :leader_user_id, :commission_type, :amount, :state, NOW()
            ) RETURNING *'
        );
        $stmt->execute($data);
        return $stmt->fetch() ?: [];
    }

    public function createPickupHubEarning(array $data): array
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO pickup_hub_earnings (
                id, tenant_merchant_id, leader_order_id, pickup_hub_id, amount, state, created_at
            ) VALUES (
                gen_random_uuid(), :tenant_merchant_id, :leader_order_id, :pickup_hub_id, :amount, :state, NOW()
            ) RETURNING *'
        );
        $stmt->execute($data);
        return $stmt->fetch() ?: [];
    }

    public function createDeliveryFeeEarning(array $data): array
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO delivery_fee_earnings (
                id, tenant_merchant_id, delivery_task_id, driver_user_id, amount, state, created_at
            ) VALUES (
                gen_random_uuid(), :tenant_merchant_id, :delivery_task_id, :driver_user_id, :amount, :state, NOW()
            ) RETURNING *'
        );
        $stmt->execute($data);
        return $stmt->fetch() ?: [];
    }

    public function createMerchantSettlement(array $data): array
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO merchant_settlements (
                id, tenant_merchant_id, leader_order_id, merchant_id, amount, state, created_at
            ) VALUES (
                gen_random_uuid(), :tenant_merchant_id, :leader_order_id, :merchant_id, :amount, :state, NOW()
            ) RETURNING *'
        );
        $stmt->execute($data);
        return $stmt->fetch() ?: [];
    }

    public function createSupplyPartnerReceivable(array $data): array
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO supply_partner_receivables (
                id, tenant_merchant_id, fulfillment_order_id, supply_partner_id, amount, state, created_at
            ) VALUES (
                gen_random_uuid(), :tenant_merchant_id, :fulfillment_order_id, :supply_partner_id, :amount, :state, NOW()
            ) RETURNING *'
        );
        $stmt->execute($data);
        return $stmt->fetch() ?: [];
    }
}
