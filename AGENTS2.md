# AGENTS.md

## Project
Leader-Driven Group Buying SaaS with Unified Wallet System

## Mission
Build a leader-driven group buying SaaS platform with:
- front-end leader-led selling
- merchant + supply partner fulfillment
- pickup hub handover
- driver-based final delivery
- unified wallet system
- ledger and settlement system

This system is not a generic public marketplace.
It is a leader-first collaborative group buying platform.

---

## Core product principles

### 1. Protect leader interests first
- Every customer must be bound to a leader.
- Every customer order must be attributed to the bound leader.
- The platform must not allow anonymous/public purchases without leader attribution.

### 2. Front-end is leader-driven
Customers see leader-curated products and place orders under that leader.
Customers may:
- place orders themselves
- or let the leader place orders on their behalf

### 3. Platform participates in payment and wallet flows
This version includes:
- customer wallet
- recharge
- wallet payment
- coupon discount
- cash recharge with audit trail
- withdrawal flow for settlement roles

### 4. Responsibilities are split
- Merchant responsibility ends when goods reach the pickup hub.
- Leader responsibility ends when goods reach the customer.
- Pickup hub is a fulfillment handover node.
- Driver handles last-mile delivery.

---


## Architecture Principles

### API First
All business capabilities must be designed as APIs before UI implementation.
No business-critical operation should depend on a server-rendered page.

### Headless
The backend must be frontend-agnostic.
Web, mobile, app, mini-program, and AI agents must all consume the same backend capabilities through APIs.

### AI-Ready Structured Design
All core entities, actions, statuses, and logs must be structured and machine-readable.
Critical workflows must use explicit state machines, atomic actions, and auditable records.

### Multi-Tenant SaaS
The system must be built as a multi-tenant SaaS platform.
Merchant is the core tenant boundary.
Data, permissions, wallets, ledgers, settlements, and operational records must be isolated by tenant.

## Roles

### customer
- bound to a leader
- can self-order
- can be ordered on behalf of by a leader
- has wallet
- can recharge
- can pay with wallet balance
- can use coupons
- does not have public withdrawal in phase 1

### leader
- core front-end seller
- selects merchant group-buy products
- advertises products
- manages customers
- records off-platform collection status if needed
- places consolidated leader orders to merchants
- selects pickup hub
- assigns drivers
- has wallet
- can recharge
- can pay with wallet
- earns commission and team reward
- can withdraw

### merchant
- receives leader orders
- collects leader payment
- routes fulfillment to supply partner
- has wallet
- has receivables and settlement logic
- can withdraw

### supply_partner
- fulfills merchant-routed orders
- delivers goods to pickup hub
- has wallet
- receives supply settlement
- can withdraw / request settlement

### pickup_hub
- independently operated fulfillment node
- receives goods
- confirms goods receipt
- hands goods to leader or assigned driver
- has wallet
- earns service fees
- can withdraw

### driver
- can be leader_self or independent_driver
- receives delivery tasks
- confirms pickup
- picks up at pickup hub
- delivers to customer
- has wallet
- earns delivery fee
- can withdraw

### admin
- manages all roles
- manages wallets
- manages ledger entries
- manages settlements
- reviews withdrawals
- manages risk and exceptions

---

## Wallet architecture

Use:
- unified account model
- multiple balance buckets
- transaction ledger

### Wallet owner model
- owner_type
- owner_id

Supported owner types:
- customer
- leader
- merchant
- pickup_hub
- driver
- supply_partner

### Balance buckets
- withdrawable_balance
- non_withdrawable_balance
- frozen_balance

### Definitions
#### withdrawable_balance
Real money that can be withdrawn.
Examples:
- settled leader commissions
- settled pickup hub fees
- settled driver fees
- settled merchant income
- returned customer cash balance

#### non_withdrawable_balance
Platform-consumable only.
Examples:
- recharge bonuses
- subsidies
- incentive credits

#### frozen_balance
Temporarily unavailable.
Examples:
- pending withdrawal
- risk hold
- dispute hold

---

## Wallet behavior

### Customer wallet
Must support:
- online recharge
- cash recharge
- wallet payment
- coupon + wallet combination
- refunds back to wallet

### Leader wallet
Must support:
- recharge
- wallet payment for leader orders
- commission income
- team reward income
- withdrawal

### Merchant wallet
Must support:
- receiving leader order funds
- net settlement income
- withdrawal

### Pickup hub wallet
Must support:
- pickup hub service fee income
- withdrawal

### Driver wallet
Must support:
- delivery fee income
- withdrawal

### Supply partner wallet
Must support:
- supply settlement income
- withdrawal / settlement request

---

## Recharge design

### Online recharge flow
customer chooses recharge amount
→ create recharge_order
→ initiate payment
→ payment callback succeeds
→ recharge_order becomes paid
→ wallet credited
→ wallet transactions written

### Recharge split
Example: recharge 100, bonus 10
- 100 goes to withdrawable_balance
- 10 goes to non_withdrawable_balance

### Spend priority
For customer order payment:
1. non_withdrawable_balance
2. withdrawable_balance
3. external payment for remaining amount

---

## Cash recharge design

### Principle
Cash recharge is not manual wallet adjustment.
It is an offline-collected recharge order with audit trail.

### Applicable scenarios
- customer hands cash to leader
- customer hands cash to pickup hub
- customer hands cash to merchant
- staff recharges on behalf of customer

### Flow
create cash recharge order
→ waiting for offline payment
→ collector confirms cash received
→ merchant/admin review if configured
→ confirmed
→ wallet credited
→ wallet transactions written

### Rules
- never directly edit wallet balance to simulate recharge
- must record who initiated
- must record who collected cash
- must record collection time
- may upload proof
- leader-collected cash should usually require merchant review
- pickup hub cash collection may be direct-confirm or reviewed by config

---

## Orders

### member_orders
Customer-facing orders under a leader.
Can be created by:
- customer self-service
- leader-assisted entry

Must include:
- leader attribution
- payment status
- collection status
- coupon usage
- wallet deduction info
- later link to leader_order

### leader_orders
Leader-to-merchant consolidated purchase orders.
This is the main platform trade order.

### delivery_tasks
Last-mile tasks from pickup hub to customer.
Executed by:
- leader_self
- independent_driver

---

## Responsibilities

### Merchant-side fulfillment complete
Merchant responsibility is complete once the goods are delivered to the assigned pickup hub.

Recommended status:
- merchant_fulfilled

### Final business completion
Leader side is complete only when the goods are delivered to the customer.

Recommended member order statuses:
- delivered
- completed

---

## Collections vs platform payment

The platform supports customer wallet payment.
However, the leader may still collect money off-platform in some workflows.

Therefore member orders should still support leader collection tracking fields:
- collection_status
- collection_method
- collected_amount
- collected_at
- collection_note

This is not the same as platform payment status.

---

## Revenue and settlement rules

Do NOT write pending earnings directly into wallet balances.

Instead:
- leader commission → commissions
- pickup hub fees → pickup_hub_earnings
- driver fees → delivery_fee_earnings
- merchant net settlements → merchant_settlements
- supply partner receivables → supply_partner_receivables

Only when status becomes settled:
- create ledger entries
- update wallet balances

---

## Coupons

Phase 1 must support:
- threshold discount coupon
- no-threshold coupon
- percentage discount coupon
- new user coupon

Rules:
- one coupon per order
- scope by merchant or product when needed
- cancellation may release coupon if valid
- usage must be auditable

Coupons should be applied before wallet deduction and before external payment.

---

## Withdrawals

Use frozen-balance workflow.

### Flow
user requests withdrawal
→ validate withdrawable_balance
→ reduce withdrawable_balance
→ increase frozen_balance
→ create withdrawal_request

approved
→ reduce frozen_balance
→ mark withdrawal complete

rejected
→ reduce frozen_balance
→ restore withdrawable_balance

### Withdrawal-enabled roles in phase 1
- leader
- merchant
- pickup_hub
- driver
- supply_partner

Customer withdrawal is not enabled in phase 1 UI.

---

## Core tables to implement

### Identity and roles
- users
- leader_profiles
- customer_profiles
- merchants
- supply_partners
- pickup_hubs
- drivers

### Products
- products
- product_specs
- leader_product_campaigns

### Orders
- member_orders
- member_order_items
- leader_orders
- leader_order_items
- order_payment_allocations

### Fulfillment
- merchant_fulfillment_orders
- pickup_hub_receipts
- pickup_hub_handovers
- delivery_tasks

### Wallet
- wallets
- wallet_transactions
- recharge_orders
- withdrawal_requests

### Coupons
- coupons
- coupon_user_claims
- coupon_usages

### Earnings and accounting
- commissions
- pickup_hub_earnings
- delivery_fee_earnings
- merchant_settlements
- supply_partner_receivables
- ledger_entries
- settlements
- settlement_items

---

## Required wallet tables

### wallets
Fields:
- id
- owner_type
- owner_id
- currency
- withdrawable_balance
- non_withdrawable_balance
- frozen_balance
- status
- created_at
- updated_at

### wallet_transactions
Fields:
- id
- wallet_id
- owner_type
- owner_id
- transaction_type
- direction
- amount
- balance_bucket
- related_type
- related_id
- reference_no
- before_balance
- after_balance
- status
- remark
- created_at

### recharge_orders
Fields:
- id
- order_no
- customer_user_id
- wallet_id
- currency
- recharge_amount
- bonus_amount
- payable_amount
- payment_method
- payment_status
- status
- initiated_by_type
- initiated_by_id
- received_by_type
- received_by_id
- received_at
- proof_note
- proof_image
- reviewed_by
- reviewed_at
- paid_at
- expired_at
- created_at
- updated_at

### withdrawal_requests
Fields:
- id
- wallet_id
- owner_type
- owner_id
- amount
- fee_amount
- net_amount
- status
- account_name
- account_no
- account_type
- reviewed_by
- reviewed_at
- remark
- created_at
- updated_at

---

## Coding and business rules

1. Use unified wallet logic for all supported roles.
2. Never directly mutate balances without wallet_transactions.
3. Never credit earnings to wallet before business settlement status becomes settled.
4. Keep controllers thin.
5. Keep wallet math inside explicit services.
6. Keep auditability for every recharge, deduction, refund, and withdrawal.
7. Do not allow manual “invisible” balance edits.
8. Phase 1 should not support direct cash payment on orders.
9. Customers may self-order or be ordered on behalf of by leaders.
10. All member orders remain attributed to the bound leader.

---

## Delivery priorities

Build in this order:
1. roles and auth
2. product and campaign system
3. member orders
4. leader orders
5. wallet core
6. recharge and cash recharge
7. wallet payment and allocations
8. coupon system
9. fulfillment chain
10. earnings and settlement pipeline
11. withdrawal workflow
12. responsive UI

---

## One-sentence source of truth

This platform is a leader-first collaborative group-buying SaaS with unified wallets, recharge, balance payment, coupon support, fulfillment handoffs, and settlement-based accounting across customers, leaders, merchants, supply partners, pickup hubs, and drivers.
