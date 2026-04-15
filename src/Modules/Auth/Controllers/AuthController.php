<?php

declare(strict_types=1);

namespace App\Modules\Auth\Controllers;

use App\Modules\Auth\Services\AuthService;
use App\Shared\Auth\JwtService;
use App\Shared\Http\Request;

final class AuthController
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly JwtService $jwtService,
    ) {
    }

    public function register(Request $request): array
    {
        $user = $this->authService->register($request->body);
        return [201, ['data' => $user]];
    }

    public function login(Request $request): array
    {
        $result = $this->authService->login($request->body);
        return [200, ['data' => $result]];
    }

    public function me(Request $request): array
    {
        $user = $this->authService->requireUser($request);
        return [200, ['data' => $user]];
    }
}
