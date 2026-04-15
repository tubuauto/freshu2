<?php

declare(strict_types=1);

use App\Shared\Support\Env;

return [
    'name' => Env::get('APP_NAME', 'Fresh2U'),
    'env' => Env::get('APP_ENV', 'local'),
    'debug' => Env::bool('APP_DEBUG', true),
    'url' => Env::get('APP_URL', 'http://127.0.0.1:8080'),
    'currency' => Env::get('DEFAULT_CURRENCY', 'CNY'),
    'leader_cash_collection_requires_review' => Env::bool('LEADER_CASH_COLLECTION_REQUIRES_REVIEW', true),
];
