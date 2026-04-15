<?php

declare(strict_types=1);

namespace App\Modules\Auth\Repositories;

use PDO;

final class AuthRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function createUser(array $data): array
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (
                id,
                tenant_merchant_id,
                role,
                email,
                password_hash,
                display_name,
                bound_leader_user_id,
                status
            ) VALUES (
                gen_random_uuid(),
                :tenant_merchant_id,
                :role,
                :email,
                :password_hash,
                :display_name,
                :bound_leader_user_id,
                :status
            )
            RETURNING id, tenant_merchant_id, role, email, display_name, bound_leader_user_id, status, created_at'
        );

        $stmt->execute([
            'tenant_merchant_id' => $data['tenant_merchant_id'],
            'role' => $data['role'],
            'email' => $data['email'],
            'password_hash' => $data['password_hash'],
            'display_name' => $data['display_name'],
            'bound_leader_user_id' => $data['bound_leader_user_id'],
            'status' => 'active',
        ]);

        return $stmt->fetch() ?: [];
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, tenant_merchant_id, role, email, password_hash, display_name, bound_leader_user_id, status
            FROM users
            WHERE email = :email
            LIMIT 1'
        );
        $stmt->execute(['email' => $email]);

        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findById(string $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, tenant_merchant_id, role, email, display_name, bound_leader_user_id, status
            FROM users
            WHERE id = :id
            LIMIT 1'
        );
        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch();
        return $row ?: null;
    }
}
