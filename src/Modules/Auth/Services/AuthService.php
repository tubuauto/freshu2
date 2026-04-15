<?php

declare(strict_types=1);

namespace App\Modules\Auth\Services;

use App\Modules\Auth\Repositories\AuthRepository;
use App\Shared\Auth\JwtService;
use App\Shared\Auth\PasswordService;
use App\Shared\Http\Request;
use App\Shared\Support\Exceptions\HttpException;

final class AuthService
{
    public function __construct(
        private readonly AuthRepository $authRepository,
        private readonly PasswordService $passwordService,
        private readonly JwtService $jwtService,
    ) {
    }

    public function register(array $payload): array
    {
        $email = strtolower(trim((string) ($payload['email'] ?? '')));
        $password = (string) ($payload['password'] ?? '');
        $role = (string) ($payload['role'] ?? 'customer');

        if ($email === '' || $password === '') {
            throw new HttpException(422, 'Email and password are required');
        }

        if ($this->authRepository->findByEmail($email) !== null) {
            throw new HttpException(409, 'Email already exists');
        }

        $allowedRoles = ['admin', 'customer', 'leader', 'merchant', 'supply_partner', 'pickup_hub', 'driver'];
        if (!in_array($role, $allowedRoles, true)) {
            throw new HttpException(422, 'Invalid role');
        }

        return $this->authRepository->createUser([
            'tenant_merchant_id' => $payload['tenant_merchant_id'] ?? null,
            'role' => $role,
            'email' => $email,
            'password_hash' => $this->passwordService->hash($password),
            'display_name' => $payload['display_name'] ?? null,
            'bound_leader_user_id' => $payload['bound_leader_user_id'] ?? null,
        ]);
    }

    public function login(array $payload): array
    {
        $email = strtolower(trim((string) ($payload['email'] ?? '')));
        $password = (string) ($payload['password'] ?? '');

        $user = $this->authRepository->findByEmail($email);
        if ($user === null || !$this->passwordService->verify($password, (string) $user['password_hash'])) {
            throw new HttpException(401, 'Invalid credentials');
        }

        if (($user['status'] ?? '') !== 'active') {
            throw new HttpException(403, 'User is not active');
        }

        $token = $this->jwtService->issue([
            'sub' => $user['id'],
            'role' => $user['role'],
            'tenant_merchant_id' => $user['tenant_merchant_id'],
        ]);

        unset($user['password_hash']);

        return ['token' => $token, 'user' => $user];
    }

    public function requireUser(Request $request, array $allowedRoles = []): array
    {
        $authorization = $request->header('authorization');
        if ($authorization === null || !str_starts_with($authorization, 'Bearer ')) {
            throw new HttpException(401, 'Missing Bearer token');
        }

        $token = trim(substr($authorization, 7));
        $claims = $this->jwtService->parse($token);
        if ($claims === null) {
            throw new HttpException(401, 'Invalid token');
        }

        $user = $this->authRepository->findById((string) ($claims['sub'] ?? ''));
        if ($user === null) {
            throw new HttpException(401, 'User not found');
        }

        if (!empty($allowedRoles) && !in_array($user['role'], $allowedRoles, true)) {
            throw new HttpException(403, 'Role is not allowed for this endpoint');
        }

        return $user;
    }
}
