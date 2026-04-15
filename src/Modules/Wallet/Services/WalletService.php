<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Services;

use App\Modules\Wallet\Repositories\WalletRepository;
use App\Shared\Support\Exceptions\HttpException;
use PDO;

final class WalletService
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly WalletRepository $walletRepository,
        private readonly string $defaultCurrency,
    ) {
    }

    public function getWallet(string $tenantMerchantId, string $ownerType, string $ownerId, ?string $currency = null): array
    {
        $this->validateOwnerType($ownerType);
        $currency = $currency ?: $this->defaultCurrency;

        $wallet = $this->walletRepository->findByOwner($ownerType, $ownerId, $currency);
        if ($wallet !== null) {
            return $wallet;
        }

        return $this->walletRepository->create($tenantMerchantId, $ownerType, $ownerId, $currency);
    }

    public function listTransactions(string $ownerType, string $ownerId, int $limit = 50): array
    {
        $this->validateOwnerType($ownerType);
        return $this->walletRepository->listTransactions($ownerType, $ownerId, max(1, min($limit, 200)));
    }

    public function credit(
        string $tenantMerchantId,
        string $ownerType,
        string $ownerId,
        string $bucket,
        float $amount,
        string $transactionType,
        string $relatedType,
        ?string $relatedId,
        string $referenceNo,
        ?string $remark = null
    ): array {
        $amount = $this->normalizeAmount($amount);
        if ($amount <= 0.0) {
            throw new HttpException(422, 'Credit amount must be greater than zero');
        }

        $this->pdo->beginTransaction();
        try {
            $wallet = $this->lockWallet($tenantMerchantId, $ownerType, $ownerId);
            $before = (float) $wallet[$bucket];
            $after = $before + $amount;

            $this->setWalletBucket($wallet, $bucket, $after);
            $this->walletRepository->updateBalances(
                $wallet['id'],
                $this->asDecimal((float) $wallet['withdrawable_balance']),
                $this->asDecimal((float) $wallet['non_withdrawable_balance']),
                $this->asDecimal((float) $wallet['frozen_balance'])
            );

            $this->walletRepository->addTransaction([
                'wallet_id' => $wallet['id'],
                'owner_type' => $ownerType,
                'owner_id' => $ownerId,
                'transaction_type' => $transactionType,
                'direction' => 'credit',
                'amount' => $this->asDecimal($amount),
                'balance_bucket' => $bucket,
                'related_type' => $relatedType,
                'related_id' => $relatedId,
                'reference_no' => $referenceNo,
                'before_balance' => $this->asDecimal($before),
                'after_balance' => $this->asDecimal($after),
                'remark' => $remark,
            ]);

            $this->pdo->commit();
            return $this->walletRepository->findByOwner($ownerType, $ownerId, (string) $wallet['currency']) ?? $wallet;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function consumeByPriority(
        string $tenantMerchantId,
        string $ownerType,
        string $ownerId,
        float $requiredAmount,
        string $relatedType,
        string $relatedId,
        string $referenceNo
    ): array {
        $requiredAmount = $this->normalizeAmount($requiredAmount);
        if ($requiredAmount <= 0.0) {
            return ['wallet_paid' => 0.00, 'external_remaining' => 0.00, 'allocations' => []];
        }

        $this->pdo->beginTransaction();
        try {
            $wallet = $this->lockWallet($tenantMerchantId, $ownerType, $ownerId);
            $allocations = [];
            $remaining = $requiredAmount;

            foreach (['non_withdrawable_balance', 'withdrawable_balance'] as $bucket) {
                if ($remaining <= 0.0) {
                    break;
                }

                $balance = (float) $wallet[$bucket];
                if ($balance <= 0.0) {
                    continue;
                }

                $debit = min($balance, $remaining);
                $before = $balance;
                $after = $balance - $debit;
                $this->setWalletBucket($wallet, $bucket, $after);

                $this->walletRepository->addTransaction([
                    'wallet_id' => $wallet['id'],
                    'owner_type' => $ownerType,
                    'owner_id' => $ownerId,
                    'transaction_type' => 'wallet_payment_debit',
                    'direction' => 'debit',
                    'amount' => $this->asDecimal($debit),
                    'balance_bucket' => $bucket,
                    'related_type' => $relatedType,
                    'related_id' => $relatedId,
                    'reference_no' => $referenceNo,
                    'before_balance' => $this->asDecimal($before),
                    'after_balance' => $this->asDecimal($after),
                    'remark' => 'Order wallet deduction',
                ]);

                $allocations[] = ['source' => $bucket, 'amount' => $this->normalizeAmount($debit)];
                $remaining -= $debit;
                $remaining = $this->normalizeAmount($remaining);
            }

            $this->walletRepository->updateBalances(
                $wallet['id'],
                $this->asDecimal((float) $wallet['withdrawable_balance']),
                $this->asDecimal((float) $wallet['non_withdrawable_balance']),
                $this->asDecimal((float) $wallet['frozen_balance'])
            );

            $this->pdo->commit();

            return [
                'wallet_paid' => $this->normalizeAmount($requiredAmount - $remaining),
                'external_remaining' => $remaining,
                'allocations' => $allocations,
            ];
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function freezeForWithdrawal(
        string $tenantMerchantId,
        string $ownerType,
        string $ownerId,
        float $amount,
        string $relatedType,
        string $relatedId,
        string $referenceNo
    ): array {
        $amount = $this->normalizeAmount($amount);
        if ($amount <= 0.0) {
            throw new HttpException(422, 'Withdrawal amount must be positive');
        }

        $this->pdo->beginTransaction();
        try {
            $wallet = $this->lockWallet($tenantMerchantId, $ownerType, $ownerId);
            $withdrawable = (float) $wallet['withdrawable_balance'];
            if ($withdrawable < $amount) {
                throw new HttpException(422, 'Insufficient withdrawable balance');
            }

            $beforeWithdrawable = $withdrawable;
            $afterWithdrawable = $withdrawable - $amount;
            $beforeFrozen = (float) $wallet['frozen_balance'];
            $afterFrozen = $beforeFrozen + $amount;

            $wallet['withdrawable_balance'] = $afterWithdrawable;
            $wallet['frozen_balance'] = $afterFrozen;

            $this->walletRepository->updateBalances(
                $wallet['id'],
                $this->asDecimal($afterWithdrawable),
                $this->asDecimal((float) $wallet['non_withdrawable_balance']),
                $this->asDecimal($afterFrozen)
            );

            $this->walletRepository->addTransaction([
                'wallet_id' => $wallet['id'],
                'owner_type' => $ownerType,
                'owner_id' => $ownerId,
                'transaction_type' => 'withdrawal_freeze',
                'direction' => 'debit',
                'amount' => $this->asDecimal($amount),
                'balance_bucket' => 'withdrawable_balance',
                'related_type' => $relatedType,
                'related_id' => $relatedId,
                'reference_no' => $referenceNo,
                'before_balance' => $this->asDecimal($beforeWithdrawable),
                'after_balance' => $this->asDecimal($afterWithdrawable),
                'remark' => 'Withdrawal requested: freeze from withdrawable',
            ]);

            $this->walletRepository->addTransaction([
                'wallet_id' => $wallet['id'],
                'owner_type' => $ownerType,
                'owner_id' => $ownerId,
                'transaction_type' => 'withdrawal_freeze',
                'direction' => 'credit',
                'amount' => $this->asDecimal($amount),
                'balance_bucket' => 'frozen_balance',
                'related_type' => $relatedType,
                'related_id' => $relatedId,
                'reference_no' => $referenceNo,
                'before_balance' => $this->asDecimal($beforeFrozen),
                'after_balance' => $this->asDecimal($afterFrozen),
                'remark' => 'Withdrawal requested: freeze to frozen bucket',
            ]);

            $this->pdo->commit();
            return $this->walletRepository->findByOwner($ownerType, $ownerId, (string) $wallet['currency']) ?? $wallet;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function finalizeWithdrawal(
        string $tenantMerchantId,
        string $ownerType,
        string $ownerId,
        float $amount,
        string $relatedType,
        string $relatedId,
        string $referenceNo
    ): array {
        $amount = $this->normalizeAmount($amount);
        $this->pdo->beginTransaction();
        try {
            $wallet = $this->lockWallet($tenantMerchantId, $ownerType, $ownerId);
            $beforeFrozen = (float) $wallet['frozen_balance'];
            if ($beforeFrozen < $amount) {
                throw new HttpException(422, 'Insufficient frozen balance');
            }
            $afterFrozen = $beforeFrozen - $amount;
            $wallet['frozen_balance'] = $afterFrozen;

            $this->walletRepository->updateBalances(
                $wallet['id'],
                $this->asDecimal((float) $wallet['withdrawable_balance']),
                $this->asDecimal((float) $wallet['non_withdrawable_balance']),
                $this->asDecimal($afterFrozen)
            );

            $this->walletRepository->addTransaction([
                'wallet_id' => $wallet['id'],
                'owner_type' => $ownerType,
                'owner_id' => $ownerId,
                'transaction_type' => 'withdrawal_complete_debit',
                'direction' => 'debit',
                'amount' => $this->asDecimal($amount),
                'balance_bucket' => 'frozen_balance',
                'related_type' => $relatedType,
                'related_id' => $relatedId,
                'reference_no' => $referenceNo,
                'before_balance' => $this->asDecimal($beforeFrozen),
                'after_balance' => $this->asDecimal($afterFrozen),
                'remark' => 'Withdrawal approved and paid out',
            ]);

            $this->pdo->commit();
            return $wallet;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function rejectWithdrawal(
        string $tenantMerchantId,
        string $ownerType,
        string $ownerId,
        float $amount,
        string $relatedType,
        string $relatedId,
        string $referenceNo
    ): array {
        $amount = $this->normalizeAmount($amount);

        $this->pdo->beginTransaction();
        try {
            $wallet = $this->lockWallet($tenantMerchantId, $ownerType, $ownerId);
            $beforeFrozen = (float) $wallet['frozen_balance'];
            if ($beforeFrozen < $amount) {
                throw new HttpException(422, 'Insufficient frozen balance');
            }
            $afterFrozen = $beforeFrozen - $amount;
            $beforeWithdrawable = (float) $wallet['withdrawable_balance'];
            $afterWithdrawable = $beforeWithdrawable + $amount;

            $wallet['frozen_balance'] = $afterFrozen;
            $wallet['withdrawable_balance'] = $afterWithdrawable;

            $this->walletRepository->updateBalances(
                $wallet['id'],
                $this->asDecimal($afterWithdrawable),
                $this->asDecimal((float) $wallet['non_withdrawable_balance']),
                $this->asDecimal($afterFrozen)
            );

            $this->walletRepository->addTransaction([
                'wallet_id' => $wallet['id'],
                'owner_type' => $ownerType,
                'owner_id' => $ownerId,
                'transaction_type' => 'withdrawal_release',
                'direction' => 'debit',
                'amount' => $this->asDecimal($amount),
                'balance_bucket' => 'frozen_balance',
                'related_type' => $relatedType,
                'related_id' => $relatedId,
                'reference_no' => $referenceNo,
                'before_balance' => $this->asDecimal($beforeFrozen),
                'after_balance' => $this->asDecimal($afterFrozen),
                'remark' => 'Withdrawal rejected: release from frozen',
            ]);

            $this->walletRepository->addTransaction([
                'wallet_id' => $wallet['id'],
                'owner_type' => $ownerType,
                'owner_id' => $ownerId,
                'transaction_type' => 'withdrawal_reject_return',
                'direction' => 'credit',
                'amount' => $this->asDecimal($amount),
                'balance_bucket' => 'withdrawable_balance',
                'related_type' => $relatedType,
                'related_id' => $relatedId,
                'reference_no' => $referenceNo,
                'before_balance' => $this->asDecimal($beforeWithdrawable),
                'after_balance' => $this->asDecimal($afterWithdrawable),
                'remark' => 'Withdrawal rejected: amount returned to withdrawable',
            ]);

            $this->pdo->commit();
            return $wallet;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    private function lockWallet(string $tenantMerchantId, string $ownerType, string $ownerId): array
    {
        $this->validateOwnerType($ownerType);
        $wallet = $this->walletRepository->lockByOwner($ownerType, $ownerId, $this->defaultCurrency);
        if ($wallet === null) {
            $this->walletRepository->create($tenantMerchantId, $ownerType, $ownerId, $this->defaultCurrency);
            $wallet = $this->walletRepository->lockByOwner($ownerType, $ownerId, $this->defaultCurrency);
        }

        if ($wallet === null) {
            throw new HttpException(500, 'Unable to create wallet');
        }

        return $wallet;
    }

    private function validateOwnerType(string $ownerType): void
    {
        $allowed = ['customer', 'leader', 'merchant', 'pickup_hub', 'driver', 'supply_partner'];
        if (!in_array($ownerType, $allowed, true)) {
            throw new HttpException(422, 'Invalid wallet owner type');
        }
    }

    private function normalizeAmount(float $amount): float
    {
        return round($amount, 2);
    }

    private function asDecimal(float $amount): string
    {
        return number_format($this->normalizeAmount($amount), 2, '.', '');
    }

    private function setWalletBucket(array &$wallet, string $bucket, float $value): void
    {
        if (!in_array($bucket, ['withdrawable_balance', 'non_withdrawable_balance', 'frozen_balance'], true)) {
            throw new HttpException(422, 'Invalid wallet bucket');
        }
        if ($value < 0) {
            throw new HttpException(422, 'Wallet bucket cannot go below zero');
        }

        $wallet[$bucket] = $this->normalizeAmount($value);
    }
}
