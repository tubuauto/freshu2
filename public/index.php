<?php

declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'bootstrap.php';

use App\Modules\Accounting\Controllers\AccountingController;
use App\Modules\Accounting\Repositories\AccountingRepository;
use App\Modules\Accounting\Services\AccountingService;
use App\Modules\Auth\Controllers\AuthController;
use App\Modules\Auth\Repositories\AuthRepository;
use App\Modules\Auth\Services\AuthService;
use App\Modules\Coupons\Controllers\CouponController;
use App\Modules\Coupons\Repositories\CouponRepository;
use App\Modules\Coupons\Services\CouponService;
use App\Modules\Fulfillment\Controllers\FulfillmentController;
use App\Modules\Fulfillment\Repositories\FulfillmentRepository;
use App\Modules\Fulfillment\Services\FulfillmentService;
use App\Modules\Orders\Controllers\OrderController;
use App\Modules\Orders\Repositories\OrderRepository;
use App\Modules\Orders\Services\OrderService;
use App\Modules\Products\Controllers\ProductController;
use App\Modules\Products\Repositories\ProductRepository;
use App\Modules\Products\Services\ProductService;
use App\Modules\Settlements\Controllers\SettlementController;
use App\Modules\Settlements\Repositories\SettlementRepository;
use App\Modules\Settlements\Services\SettlementService;
use App\Modules\Wallet\Controllers\WalletController;
use App\Modules\Wallet\Repositories\WalletRepository;
use App\Modules\Wallet\Repositories\RechargeRepository;
use App\Modules\Wallet\Services\RechargeService;
use App\Modules\Wallet\Services\WalletService;
use App\Modules\Withdrawals\Controllers\WithdrawalController;
use App\Modules\Withdrawals\Repositories\WithdrawalRepository;
use App\Modules\Withdrawals\Services\WithdrawalService;
use App\Shared\Auth\JwtService;
use App\Shared\Auth\PasswordService;
use App\Shared\Database\Connection;
use App\Shared\Http\Request;
use App\Shared\Http\Response;
use App\Shared\Http\Router;
use App\Shared\Support\Exceptions\HttpException;

$appConfig = require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'app.php';
$pdo = Connection::get();

$jwtService = new JwtService();
$authRepository = new AuthRepository($pdo);
$authService = new AuthService($authRepository, new PasswordService(), $jwtService);

$productRepository = new ProductRepository($pdo);
$productService = new ProductService($productRepository);

$walletRepository = new WalletRepository($pdo);
$walletService = new WalletService($pdo, $walletRepository, $appConfig['currency']);
$rechargeRepository = new RechargeRepository($pdo);
$rechargeService = new RechargeService($pdo, $rechargeRepository, $walletService, $appConfig);

$couponRepository = new CouponRepository($pdo);
$couponService = new CouponService($pdo, $couponRepository);

$orderRepository = new OrderRepository($pdo);
$orderService = new OrderService($pdo, $orderRepository, $walletService, $couponService, $appConfig['currency']);

$fulfillmentRepository = new FulfillmentRepository($pdo);
$fulfillmentService = new FulfillmentService($pdo, $fulfillmentRepository);

$accountingRepository = new AccountingRepository($pdo);
$accountingService = new AccountingService($pdo, $accountingRepository);

$settlementRepository = new SettlementRepository($pdo);
$settlementService = new SettlementService($pdo, $settlementRepository, $walletService);

$withdrawalRepository = new WithdrawalRepository($pdo);
$withdrawalService = new WithdrawalService($pdo, $withdrawalRepository, $walletService);

$controllers = [
    'auth' => new AuthController($authService, $jwtService),
    'products' => new ProductController($productService, $authService),
    'orders' => new OrderController($orderService, $authService),
    'wallet' => new WalletController($walletService, $rechargeService, $authService),
    'coupons' => new CouponController($couponService, $authService),
    'fulfillment' => new FulfillmentController($fulfillmentService, $authService),
    'accounting' => new AccountingController($accountingService, $authService),
    'settlements' => new SettlementController($settlementService, $authService),
    'withdrawals' => new WithdrawalController($withdrawalService, $authService),
];

$router = new Router();
$routeRegistrar = require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'routes.php';
$routeRegistrar($router, $controllers);

$request = Request::fromGlobals();

try {
    [$status, $payload] = $router->dispatch($request);
    Response::json($payload, $status);
} catch (HttpException $e) {
    Response::json(['error' => $e->getMessage()], $e->statusCode());
} catch (Throwable $e) {
    $message = $appConfig['debug'] ? $e->getMessage() : 'Internal server error';
    Response::json(['error' => $message], 500);
}
