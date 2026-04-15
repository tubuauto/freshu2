<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Repositories;

use PDO;

final class WalletRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findByOwner(string $ownerType, string $ownerId, string $currency): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM wallets WHERE owner_type = :owner_type AND owner_id = :owner_id AND currency = :currency LIMIT 1'
        );
        $stmt->execute([
            'owner_type' => $ownerType,
            'owner_id' => $ownerId,
            'currency' => $currency,
        ]);

        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function lockByOwner(string $ownerType, string $ownerId, string $currency): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM wallets WHERE owner_type = :owner_type AND owner_id = :owner_id AND currency = :currency FOR UPDATE'
        );
        $stmt->execute([
            'owner_type' => $ownerType,
            'owner_id' => $ownerId,
            'currency' => $currency,
        ]);

        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(string $tenantMerchantId, string $ownerType, string $ownerId, string $currency): array
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO wallets (
                id,
                tenant_merchant_id,
                owner_type,
                owner_id,
                currency,
                withdrawable_balance,
                non_withdrawable_balance,
                frozen_balance,
                status
            ) VALUES (
                gen_random_uuid(),
                :tenant_merchant_id,
                :owner_type,
                :owner_id,
                :currency,
                0,
                0,
                0,
                :status
            )
            RETURNING *'
        );

        $stmt->execute([
            'tenant_merchant_id' => $tenantMerchantId,
            'owner_type' => $ownerType,
            'owner_id' => $ownerId,
            'currency' => $currency,
            'status' => 'active',
        ]);

        return $stmt->fetch() ?: [];
    }

    public function updateBalances(string $walletId, string $withdrawable, string $nonWithdrawable, string $frozen): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE wallets
            SET withdrawable_balance = :withdrawable,
                non_withdrawable_balance = :non_withdrawable,
                frozen_balance = :frozen,
                updated_at = NOW()
            WHERE id = :id'
        );

        $stmt->execute([
            'id' => $walletId,
            'withdrawable' => $withdrawable,
            'non_withdrawable' => $nonWithdrawable,
            'frozen' => $frozen,
        ]);
    }

    public function addTransaction(array $tx): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO wallet_transactions (
                id,
                wallet_id,
                owner_type,
                owner_id,
                transaction_type,
                direction,
                amount,
                balance_bucket,
                related_type,
                related_id,
                reference_no,
                before_balance,
                after_balance,
                status,
                remark,
                created_at
            ) VALUES (
                gen_random_uuid(),
                :wallet_id,
                :owner_type,
                :owner_id,
                :transaction_type,
                :direction,
                :amount,
                :balance_bucket,
                :related_type,
                :related_id,
                :reference_no,
                :before_balance,
                :after_balance,
                :status,
                :remark,
                NOW()
            )'
        );

        $stmt->execute([
            'wallet_id' => $tx['wallet_id'],
            'owner_type' => $tx['owner_type'],
            'owner_id' => $tx['owner_id'],
            'transaction_type' => $tx['transaction_type'],
            'direction' => $tx['direction'],
            'amount' => $tx['amount'],
            'balance_bucket' => $tx['balance_bucket'],
            'related_type' => $tx['related_type'],
            'related_id' => $tx['related_id'],
            'reference_no' => $tx['reference_no'],
            'before_balance' => $tx['before_balance'],
            'after_balance' => $tx['after_balance'],
            'status' => $tx['status'] ?? 'posted',
            'remark' => $tx['remark'] ?? null,
        ]);
    }

    public function listTransactions(string $ownerType, string $ownerId, int $limit): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT wt.*
            FROM wallet_transactions wt
            WHERE wt.owner_type = :owner_type AND wt.owner_id = :owner_id
            ORDER BY wt.created_at DESC
            LIMIT :limit'
        );
        $stmt->bindValue(':owner_type', $ownerType);
        $stmt->bindValue(':owner_id', $ownerId);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
