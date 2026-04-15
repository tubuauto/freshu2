<?php

declare(strict_types=1);

namespace App\Modules\Products\Services;

use App\Modules\Products\Repositories\ProductRepository;
use App\Shared\Support\Exceptions\HttpException;

final class ProductService
{
    public function __construct(private readonly ProductRepository $productRepository)
    {
    }

    public function createProduct(array $payload, string $tenantMerchantId): array
    {
        $name = trim((string) ($payload['name'] ?? ''));
        $basePrice = (float) ($payload['base_price'] ?? 0);

        if ($name === '' || $basePrice < 0) {
            throw new HttpException(422, 'name and valid base_price are required');
        }

        return $this->productRepository->createProduct([
            'tenant_merchant_id' => $tenantMerchantId,
            'merchant_id' => $payload['merchant_id'] ?? $tenantMerchantId,
            'name' => $name,
            'description' => $payload['description'] ?? null,
            'base_price' => number_format(round($basePrice, 2), 2, '.', ''),
            'status' => $payload['status'] ?? 'active',
        ]);
    }

    public function listProducts(string $tenantMerchantId): array
    {
        return $this->productRepository->listProducts($tenantMerchantId);
    }

    public function addSpec(string $productId, array $payload, string $tenantMerchantId): array
    {
        $specName = trim((string) ($payload['spec_name'] ?? ''));
        if ($specName === '') {
            throw new HttpException(422, 'spec_name is required');
        }

        return $this->productRepository->addSpec([
            'tenant_merchant_id' => $tenantMerchantId,
            'product_id' => $productId,
            'spec_name' => $specName,
            'spec_value' => $payload['spec_value'] ?? null,
            'price_delta' => number_format(round((float) ($payload['price_delta'] ?? 0), 2), 2, '.', ''),
            'stock_qty' => (int) ($payload['stock_qty'] ?? 0),
        ]);
    }

    public function createCampaign(array $payload, string $tenantMerchantId, string $leaderUserId): array
    {
        $title = trim((string) ($payload['campaign_title'] ?? ''));
        $productId = (string) ($payload['product_id'] ?? '');
        $campaignPrice = (float) ($payload['campaign_price'] ?? 0);

        if ($title === '' || $productId === '' || $campaignPrice < 0) {
            throw new HttpException(422, 'campaign_title, product_id and valid campaign_price are required');
        }

        return $this->productRepository->createCampaign([
            'tenant_merchant_id' => $tenantMerchantId,
            'leader_user_id' => $leaderUserId,
            'product_id' => $productId,
            'campaign_title' => $title,
            'campaign_price' => number_format(round($campaignPrice, 2), 2, '.', ''),
            'starts_at' => $payload['starts_at'] ?? null,
            'ends_at' => $payload['ends_at'] ?? null,
            'status' => $payload['status'] ?? 'active',
        ]);
    }

    public function listLeaderCampaigns(string $tenantMerchantId, string $leaderId): array
    {
        return $this->productRepository->listLeaderCampaigns($tenantMerchantId, $leaderId);
    }
}
