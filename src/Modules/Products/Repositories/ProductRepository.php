<?php

declare(strict_types=1);

namespace App\Modules\Products\Repositories;

use PDO;

final class ProductRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function createProduct(array $data): array
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO products (
                id, tenant_merchant_id, merchant_id, name, description, base_price, status, created_at, updated_at
            ) VALUES (
                gen_random_uuid(), :tenant_merchant_id, :merchant_id, :name, :description, :base_price, :status, NOW(), NOW()
            )
            RETURNING *'
        );

        $stmt->execute([
            'tenant_merchant_id' => $data['tenant_merchant_id'],
            'merchant_id' => $data['merchant_id'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'base_price' => $data['base_price'],
            'status' => $data['status'] ?? 'active',
        ]);

        return $stmt->fetch() ?: [];
    }

    public function listProducts(string $tenantMerchantId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM products WHERE tenant_merchant_id = :tenant_merchant_id ORDER BY created_at DESC');
        $stmt->execute(['tenant_merchant_id' => $tenantMerchantId]);
        return $stmt->fetchAll();
    }

    public function addSpec(array $data): array
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO product_specs (
                id, tenant_merchant_id, product_id, spec_name, spec_value, price_delta, stock_qty, created_at, updated_at
            ) VALUES (
                gen_random_uuid(), :tenant_merchant_id, :product_id, :spec_name, :spec_value, :price_delta, :stock_qty, NOW(), NOW()
            )
            RETURNING *'
        );

        $stmt->execute([
            'tenant_merchant_id' => $data['tenant_merchant_id'],
            'product_id' => $data['product_id'],
            'spec_name' => $data['spec_name'],
            'spec_value' => $data['spec_value'] ?? null,
            'price_delta' => $data['price_delta'] ?? 0,
            'stock_qty' => $data['stock_qty'] ?? 0,
        ]);

        return $stmt->fetch() ?: [];
    }

    public function createCampaign(array $data): array
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO leader_product_campaigns (
                id, tenant_merchant_id, leader_user_id, product_id, campaign_title, campaign_price, starts_at, ends_at, status, created_at, updated_at
            ) VALUES (
                gen_random_uuid(), :tenant_merchant_id, :leader_user_id, :product_id, :campaign_title, :campaign_price, :starts_at, :ends_at, :status, NOW(), NOW()
            )
            RETURNING *'
        );

        $stmt->execute([
            'tenant_merchant_id' => $data['tenant_merchant_id'],
            'leader_user_id' => $data['leader_user_id'],
            'product_id' => $data['product_id'],
            'campaign_title' => $data['campaign_title'],
            'campaign_price' => $data['campaign_price'],
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'status' => $data['status'] ?? 'active',
        ]);

        return $stmt->fetch() ?: [];
    }

    public function listLeaderCampaigns(string $tenantMerchantId, string $leaderId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT c.*, p.name AS product_name
            FROM leader_product_campaigns c
            JOIN products p ON p.id = c.product_id
            WHERE c.tenant_merchant_id = :tenant_merchant_id
              AND c.leader_user_id = :leader_user_id
            ORDER BY c.created_at DESC'
        );

        $stmt->execute([
            'tenant_merchant_id' => $tenantMerchantId,
            'leader_user_id' => $leaderId,
        ]);

        return $stmt->fetchAll();
    }
}
