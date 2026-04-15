<?php

declare(strict_types=1);

namespace App\Modules\Products\Controllers;

use App\Modules\Auth\Services\AuthService;
use App\Modules\Products\Services\ProductService;
use App\Shared\Http\Request;

final class ProductController
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly AuthService $authService,
    ) {
    }

    public function createProduct(Request $request): array
    {
        $user = $this->authService->requireUser($request, ['merchant', 'admin']);
        $tenantMerchantId = (string) ($request->header('x-tenant-merchant-id') ?? $user['tenant_merchant_id'] ?? '');
        $product = $this->productService->createProduct($request->body, $tenantMerchantId);
        return [201, ['data' => $product]];
    }

    public function listProducts(Request $request): array
    {
        $user = $this->authService->requireUser($request);
        $tenantMerchantId = (string) ($request->header('x-tenant-merchant-id') ?? $user['tenant_merchant_id'] ?? '');
        $products = $this->productService->listProducts($tenantMerchantId);
        return [200, ['data' => $products]];
    }

    public function addSpec(Request $request, array $params): array
    {
        $user = $this->authService->requireUser($request, ['merchant', 'admin']);
        $tenantMerchantId = (string) ($request->header('x-tenant-merchant-id') ?? $user['tenant_merchant_id'] ?? '');
        $spec = $this->productService->addSpec($params['id'], $request->body, $tenantMerchantId);
        return [201, ['data' => $spec]];
    }

    public function createCampaign(Request $request): array
    {
        $user = $this->authService->requireUser($request, ['leader', 'admin']);
        $tenantMerchantId = (string) ($request->header('x-tenant-merchant-id') ?? $user['tenant_merchant_id'] ?? '');
        $leaderUserId = (string) ($request->body['leader_user_id'] ?? $user['id']);
        $campaign = $this->productService->createCampaign($request->body, $tenantMerchantId, $leaderUserId);
        return [201, ['data' => $campaign]];
    }

    public function listLeaderCampaigns(Request $request, array $params): array
    {
        $user = $this->authService->requireUser($request);
        $tenantMerchantId = (string) ($request->header('x-tenant-merchant-id') ?? $user['tenant_merchant_id'] ?? '');
        $campaigns = $this->productService->listLeaderCampaigns($tenantMerchantId, $params['leaderId']);
        return [200, ['data' => $campaigns]];
    }
}
