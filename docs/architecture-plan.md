# Fresh2U Architecture, Schema, and Implementation Plan

## 1) Project Folder Structure

```text
fresh2u/
  AGENTS.md
  README.md
  composer.json
  .env.example
  public/
    index.php
    assets/
      app.css
      app.js
  bin/
    migrate.php
    seed.php
  config/
    app.php
    database.php
    routes.php
  database/
    migrations/
      001_extensions.sql
      002_enums.sql
      003_core_tables.sql
      004_indexes_constraints.sql
    seeds/
      001_seed_core.sql
  src/
    Shared/
      Http/
        Request.php
        Response.php
        Router.php
      Auth/
        JwtService.php
        PasswordService.php
      Database/
        Connection.php
        MigrationRunner.php
      Support/
        Uuid.php
        Clock.php
        Exceptions/
      Tenancy/
        TenantContext.php
    Modules/
      Auth/
        Controllers/
        Services/
        Repositories/
      Products/
        Controllers/
        Services/
        Repositories/
      Orders/
        Controllers/
        Services/
        Repositories/
      Wallet/
        Controllers/
        Services/
        Repositories/
      Coupons/
        Controllers/
        Services/
        Repositories/
      Fulfillment/
        Controllers/
        Services/
        Repositories/
      Accounting/
        Controllers/
        Services/
        Repositories/
      Settlements/
        Controllers/
        Services/
        Repositories/
      Withdrawals/
        Controllers/
        Services/
        Repositories/
  frontend/
    pages/
      admin.html
      leader.html
      customer.html
      merchant.html
      pickup_hub.html
      driver.html
      supply_partner.html
```

## 2) Exact Enums (Source of Truth)

### member_orders.status
- `draft`
- `awaiting_payment`
- `paid`
- `assigned_to_leader_order`
- `merchant_fulfilled`
- `at_pickup_hub`
- `out_for_delivery`
- `delivered`
- `completed`
- `cancelled`
- `refunded`

### member_orders.payment_status
- `unpaid`
- `partially_paid`
- `paid`
- `failed`
- `partially_refunded`
- `refunded`

### member_orders.collection_status
- `not_collected`
- `pending_offline_collection`
- `collected_offline`
- `waived`
- `disputed`

### leader_orders.status
- `draft`
- `submitted`
- `confirmed`
- `paid`
- `routed_to_supply_partner`
- `in_fulfillment`
- `merchant_fulfilled`
- `at_pickup_hub`
- `handed_over`
- `completed`
- `cancelled`

### delivery_tasks.status
- `pending_assignment`
- `assigned`
- `accepted`
- `picked_up`
- `out_for_delivery`
- `delivered`
- `failed`
- `cancelled`

### recharge_orders.status
- `initiated`
- `awaiting_payment`
- `awaiting_offline_confirmation`
- `awaiting_review`
- `confirmed`
- `paid`
- `failed`
- `expired`
- `cancelled`

### withdrawal_requests.status
- `pending_review`
- `approved`
- `rejected`
- `processing`
- `completed`
- `failed`
- `cancelled`

### wallet_transactions.transaction_type
- `recharge_credit`
- `recharge_bonus_credit`
- `cash_recharge_credit`
- `wallet_payment_debit`
- `wallet_refund_credit`
- `withdrawal_freeze`
- `withdrawal_release`
- `withdrawal_complete_debit`
- `withdrawal_reject_return`
- `settlement_credit`
- `settlement_debit`
- `adjustment_freeze`
- `adjustment_unfreeze`

### ledger_entries.entry_type
- `recharge_inflow`
- `order_payment_inflow`
- `coupon_discount_expense`
- `commission_expense`
- `pickup_hub_fee_expense`
- `delivery_fee_expense`
- `merchant_settlement_payable`
- `supply_partner_settlement_payable`
- `withdrawal_payable`
- `refund_outflow`
- `platform_adjustment`

### settlements.state
- `pending`
- `calculating`
- `ready_for_review`
- `approved`
- `rejected`
- `posted`
- `paid`
- `partially_paid`
- `closed`
- `cancelled`

## 3) Database Schema / Migration Plan

### Migration 001
- PostgreSQL extensions: `pgcrypto`.

### Migration 002
- Create Postgres enums for:
  - `role_type_enum`
  - `owner_type_enum`
  - all status enums above
  - wallet bucket enum and direction enum

### Migration 003 Core Tables
- Identity and roles: `users`, `leader_profiles`, `customer_profiles`, `merchants`, `supply_partners`, `pickup_hubs`, `drivers`.
- Products: `products`, `product_specs`, `leader_product_campaigns`.
- Orders: `member_orders`, `member_order_items`, `leader_orders`, `leader_order_items`, `order_payment_allocations`.
- Fulfillment: `merchant_fulfillment_orders`, `pickup_hub_receipts`, `pickup_hub_handovers`, `delivery_tasks`.
- Wallet: `wallets`, `wallet_transactions`, `recharge_orders`, `withdrawal_requests`.
- Coupons: `coupons`, `coupon_user_claims`, `coupon_usages`.
- Earnings & accounting: `commissions`, `pickup_hub_earnings`, `delivery_fee_earnings`, `merchant_settlements`, `supply_partner_receivables`, `ledger_entries`, `settlements`, `settlement_items`.

### Migration 004 Indexes/Constraints
- Composite tenancy and lookup indexes (`tenant_merchant_id`, status/time).
- Unique wallet owner (`owner_type`,`owner_id`,`currency`).
- FK integrity.
- CHECK constraints for positive amounts.

## 4) API Route Map (Headless, API-first)

### Auth & Tenant
- `POST /api/v1/auth/register`
- `POST /api/v1/auth/login`
- `GET /api/v1/auth/me`

### Products/Campaigns
- `POST /api/v1/products`
- `GET /api/v1/products`
- `POST /api/v1/products/{id}/specs`
- `POST /api/v1/campaigns`
- `GET /api/v1/leaders/{leaderId}/campaigns`

### Member Orders
- `POST /api/v1/member-orders` (self-order or leader-on-behalf)
- `GET /api/v1/member-orders/{id}`
- `POST /api/v1/member-orders/{id}/apply-coupon`
- `POST /api/v1/member-orders/{id}/pay`
- `POST /api/v1/member-orders/{id}/collection`

### Leader Orders
- `POST /api/v1/leader-orders/consolidate`
- `GET /api/v1/leader-orders/{id}`
- `POST /api/v1/leader-orders/{id}/submit`
- `POST /api/v1/leader-orders/{id}/status`

### Wallet
- `GET /api/v1/wallets/{ownerType}/{ownerId}`
- `GET /api/v1/wallets/{ownerType}/{ownerId}/transactions`

### Recharge
- `POST /api/v1/recharges/online`
- `POST /api/v1/recharges/{id}/payment-callback`
- `POST /api/v1/recharges/cash`
- `POST /api/v1/recharges/{id}/confirm-cash`
- `POST /api/v1/recharges/{id}/review`

### Coupons
- `POST /api/v1/coupons`
- `POST /api/v1/coupons/{id}/claim`
- `GET /api/v1/coupons/available`

### Fulfillment & Delivery
- `POST /api/v1/leader-orders/{id}/route-fulfillment`
- `POST /api/v1/fulfillment/{id}/mark-merchant-fulfilled`
- `POST /api/v1/pickup-hubs/{id}/receipts`
- `POST /api/v1/pickup-hubs/{id}/handovers`
- `POST /api/v1/delivery-tasks`
- `POST /api/v1/delivery-tasks/{id}/status`

### Earnings / Ledger / Settlement
- `POST /api/v1/earnings/calculate`
- `POST /api/v1/settlements`
- `POST /api/v1/settlements/{id}/review`
- `POST /api/v1/settlements/{id}/post`
- `GET /api/v1/ledger-entries`

### Withdrawals
- `POST /api/v1/withdrawals`
- `POST /api/v1/withdrawals/{id}/review`
- `GET /api/v1/withdrawals/{id}`

## 5) Role/Page Map

- `customer`: customer dashboard, product list under bound leader, create order, wallet/recharge.
- `leader`: campaign management, customer management, create order on behalf, consolidate leader orders, assign driver.
- `merchant`: product management, receive/confirm leader orders, route to supply partner, settlement panel.
- `supply_partner`: fulfillment queue, deliver to pickup hub, receivables.
- `pickup_hub`: receipt confirmation, handover confirmation, wallet and fee settlements.
- `driver`: assigned delivery tasks, pickup confirmation, delivery confirmation, wallet.
- `admin`: user/role ops, recharge reviews, withdrawal reviews, settlements/ledger oversight.

## 6) Order Flow

1. Customer is bound to a leader.
2. Customer self-orders or leader places order on behalf.
3. Coupon applies first.
4. Wallet payment allocation executes (non-withdrawable first, then withdrawable, then external remainder).
5. Order moves to `paid` and enters leader consolidation.
6. Leader consolidates member orders into main trade `leader_orders`.
7. Merchant routes fulfillment to supply partner.
8. Merchant responsibility completes at pickup hub receipt (`merchant_fulfilled`).
9. Delivery task executes from pickup hub to customer.
10. Final business completion at customer delivery (`delivered`/`completed`).

## 7) Wallet Flow

- Unified wallet per owner (`owner_type + owner_id + currency`).
- Every mutation requires a `wallet_transactions` record.
- Recharge and cash recharge create auditable recharge orders first.
- Withdrawal uses freeze flow:
  - request: withdrawable -> frozen
  - approve: frozen -> debited/complete
  - reject: frozen -> withdrawable rollback

## 8) Ledger + Settlement Flow

1. Earnings tables receive pending values only.
2. Settlement calculation creates `settlements` + `settlement_items`.
3. Review and approval gate posting.
4. Posting creates immutable `ledger_entries`.
5. Only posted/settled earnings trigger wallet settlement credits.

## 9) Implementation Milestones (Required Sequence)

1. Auth and roles
2. Products and leader campaigns
3. Member orders
4. Leader orders
5. Unified wallet core
6. Recharge and cash recharge
7. Wallet payment and allocations
8. Coupons
9. Merchant fulfillment and pickup hub flow
10. Delivery tasks
11. Earnings records
12. Ledger entries and settlements
13. Withdrawal workflow
14. Responsive frontend pages
15. Seed data
16. README

## 10) Assumptions / Ambiguities Resolved

- Tenant boundary: `merchant_id` is required on business tables and validated via middleware context.
- External payment gateway is mocked by callback endpoint in phase 1.
- Team reward exists logically but uses `commissions` table with subtype `team_reward` in phase 1.
- Customer withdrawal API exists for data model parity but is blocked by policy in UI and service validation.
- Cash recharge review rule is config-driven (`leader_collected_requires_review = true` default).