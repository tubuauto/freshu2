<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Services;

use App\Modules\Accounting\Repositories\AccountingRepository;
use App\Shared\Support\Exceptions\HttpException;
use PDO;

final class AccountingService
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly AccountingRepository $repository,
    ) {
    }

    public function calculateEarnings(array $payload, string $tenantMerchantId): array
    {
        $leaderOrderId = (string) ($payload['leader_order_id'] ?? '');
        $leaderOrder = $leaderOrderId !== '' ? $this->repository->findLeaderOrder($leaderOrderId) : null;

        if ($leaderOrder === null) {
            throw new HttpException(422, 'Valid leader_order_id is required');
        }

        $baseAmount = (float) $leaderOrder['payable_amount'];
        $commissionAmount = round((float) ($payload['commission_amount'] ?? $baseAmount * 0.05), 2);
        $pickupHubAmount = round((float) ($payload['pickup_hub_fee_amount'] ?? $baseAmount * 0.02), 2);
        $deliveryFeeAmount = round((float) ($payload['delivery_fee_amount'] ?? $baseAmount * 0.03), 2);
        $merchantNetAmount = round((float) ($payload['merchant_net_amount'] ?? max(0, $baseAmount - $commissionAmount - $pickupHubAmount - $deliveryFeeAmount)), 2);
        $supplyAmount = round((float) ($payload['supply_partner_amount'] ?? max(0, $merchantNetAmount * 0.75)), 2);

        $this->pdo->beginTransaction();
        try {
            $records = [
                'commission' => $this->repository->createCommission([
                    'tenant_merchant_id' => $tenantMerchantId,
                    'member_order_id' => $payload['member_order_id'] ?? null,
                    'leader_user_id' => $payload['leader_user_id'] ?? $leaderOrder['leader_user_id'],
                    'commission_type' => $payload['commission_type'] ?? 'commission',
                    'amount' => number_format($commissionAmount, 2, '.', ''),
                    'state' => 'pending',
                ]),
                'pickup_hub_earning' => $this->repository->createPickupHubEarning([
                    'tenant_merchant_id' => $tenantMerchantId,
                    'leader_order_id' => $leaderOrderId,
                    'pickup_hub_id' => $payload['pickup_hub_id'],
                    'amount' => number_format($pickupHubAmount, 2, '.', ''),
                    'state' => 'pending',
                ]),
                'delivery_fee_earning' => $this->repository->createDeliveryFeeEarning([
                    'tenant_merchant_id' => $tenantMerchantId,
                    'delivery_task_id' => $payload['delivery_task_id'],
                    'driver_user_id' => $payload['driver_user_id'],
                    'amount' => number_format($deliveryFeeAmount, 2, '.', ''),
                    'state' => 'pending',
                ]),
                'merchant_settlement' => $this->repository->createMerchantSettlement([
                    'tenant_merchant_id' => $tenantMerchantId,
                    'leader_order_id' => $leaderOrderId,
                    'merchant_id' => $leaderOrder['merchant_id'],
                    'amount' => number_format($merchantNetAmount, 2, '.', ''),
                    'state' => 'pending',
                ]),
                'supply_partner_receivable' => $this->repository->createSupplyPartnerReceivable([
                    'tenant_merchant_id' => $tenantMerchantId,
                    'fulfillment_order_id' => $payload['fulfillment_order_id'],
                    'supply_partner_id' => $payload['supply_partner_id'],
                    'amount' => number_format($supplyAmount, 2, '.', ''),
                    'state' => 'pending',
                ]),
            ];
            $this->pdo->commit();

            return $records;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
