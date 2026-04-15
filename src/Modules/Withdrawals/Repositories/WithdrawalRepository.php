<?php

declare(strict_types=1);

namespace App\Modules\Withdrawals\Repositories;

use PDO;

final class WithdrawalRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function create(array $data): array
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO withdrawal_requests (
                id, tenant_merchant_id, wallet_id, owner_type, owner_id,
                amount, fee_amount, net_amount, status,
                account_name, account_no, account_type,
                reviewed_by, reviewed_at, remark,
                created_at, updated_at
            ) VALUES (
                gen_random_uuid(), :tenant_merchant_id, :wallet_id, :owner_type, :owner_id,
                :amount, :fee_amount, :net_amount, :status,
                :account_name, :account_no, :account_type,
                :reviewed_by, :reviewed_at, :remark,
                NOW(), NOW()
            ) RETURNING *'
        );

        $stmt->execute($data);
        return $stmt->fetch() ?: [];
    }

    public function findById(string $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM withdrawal_requests WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updateStatus(string $id, string $status, ?string $reviewedBy = null, ?string $remark = null): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE withdrawal_requests
            SET status = :status,
                reviewed_by = COALESCE(:reviewed_by, reviewed_by),
                reviewed_at = CASE WHEN :reviewed_by IS NOT NULL THEN NOW() ELSE reviewed_at END,
                remark = COALESCE(:remark, remark),
                updated_at = NOW()
            WHERE id = :id'
        );

        $stmt->execute([
            'id' => $id,
            'status' => $status,
            'reviewed_by' => $reviewedBy,
            'remark' => $remark,
        ]);
    }
}
