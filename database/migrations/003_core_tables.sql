CREATE TABLE IF NOT EXISTS merchants (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name TEXT NOT NULL,
    contact_name TEXT,
    contact_phone TEXT,
    status TEXT NOT NULL DEFAULT 'active',
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS users (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_merchant_id UUID REFERENCES merchants(id),
    role role_type_enum NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    display_name TEXT,
    bound_leader_user_id UUID,
    status TEXT NOT NULL DEFAULT 'active',
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS leader_profiles (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_merchant_id UUID NOT NULL REFERENCES merchants(id),
    user_id UUID NOT NULL UNIQUE REFERENCES users(id),
    team_name TEXT,
    region TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS customer_profiles (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_merchant_id UUID NOT NULL REFERENCES merchants(id),
    user_id UUID NOT NULL UNIQUE REFERENCES users(id),
    bound_leader_user_id UUID NOT NULL REFERENCES users(id),
    address_line TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS supply_partners (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_merchant_id UUID NOT NULL REFERENCES merchants(id),
    user_id UUID UNIQUE REFERENCES users(id),
    partner_name TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'active',
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS pickup_hubs (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_merchant_id UUID NOT NULL REFERENCES merchants(id),
    user_id UUID UNIQUE REFERENCES users(id),
    hub_name TEXT NOT NULL,
    address_line TEXT,
    status TEXT NOT NULL DEFAULT 'active',
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS drivers (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_merchant_id UUID NOT NULL REFERENCES merchants(id),
    user_id UUID UNIQUE REFERENCES users(id),
    driver_type delivery_driver_type_enum NOT NULL DEFAULT 'independent_driver',
    status TEXT NOT NULL DEFAULT 'active',
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS products (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_merchant_id UUID NOT NULL REFERENCES merchants(id),
    merchant_id UUID NOT NULL REFERENCES merchants(id),
    name TEXT NOT NULL,
    description TEXT,
    base_price NUMERIC(18,2) NOT NULL CHECK (base_price >= 0),
    status TEXT NOT NULL DEFAULT 'active',
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS product_specs (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_merchant_id UUID NOT NULL REFERENCES merchants(id),
    product_id UUID NOT NULL REFERENCES products(id),
    spec_name TEXT NOT NULL,
    spec_value TEXT,
    price_delta NUMERIC(18,2) NOT NULL DEFAULT 0,
    stock_qty INTEGER NOT NULL DEFAULT 0,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS leader_product_campaigns (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_merchant_id UUID NOT NULL REFERENCES merchants(id),
    leader_user_id UUID NOT NULL REFERENCES users(id),
    product_id UUID NOT NULL REFERENCES products(id),
    campaign_title TEXT NOT NULL,
    campaign_price NUMERIC(18,2) NOT NULL CHECK (campaign_price >= 0),
    starts_at TIMESTAMPTZ,
    ends_at TIMESTAMPTZ,
    status TEXT NOT NULL DEFAULT 'active',
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS leader_orders (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    order_no TEXT NOT NULL UNIQUE,
    tenant_merchant_id UUID NOT NULL REFERENCES merchants(id),
    merchant_id UUID NOT NULL REFERENCES merchants(id),
    leader_user_id UUID NOT NULL REFERENCES users(id),
    pickup_hub_id UUID REFERENCES pickup_hubs(id),
    status leader_order_status_enum NOT NULL DEFAULT 'draft',
    subtotal_amount NUMERIC(18,2) NOT NULL DEFAULT 0,
    coupon_discount_amount NUMERIC(18,2) NOT NULL DEFAULT 0,
    payable_amount NUMERIC(18,2) NOT NULL DEFAULT 0,
    currency TEXT NOT NULL DEFAULT 'CNY',
    note TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS member_orders (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    order_no TEXT NOT NULL UNIQUE,
    tenant_merchant_id UUID NOT NULL REFERENCES merchants(id),
    leader_user_id UUID NOT NULL REFERENCES users(id),
    customer_user_id UUID NOT NULL REFERENCES users(id),
    placed_by_type role_type_enum NOT NULL,
    placed_by_user_id UUID NOT NULL REFERENCES users(id),
    leader_order_id UUID REFERENCES leader_orders(id),
    status member_order_status_enum NOT NULL DEFAULT 'draft',
    payment_status member_order_payment_status_enum NOT NULL DEFAULT 'unpaid',
    collection_status member_order_collection_status_enum NOT NULL DEFAULT 'not_collected',
    collection_method TEXT,
    collected_amount NUMERIC(18,2) NOT NULL DEFAULT 0,
    collected_at TIMESTAMPTZ,
    collection_note TEXT,
    subtotal_amount NUMERIC(18,2) NOT NULL DEFAULT 0,
    coupon_discount_amount NUMERIC(18,2) NOT NULL DEFAULT 0,
    payable_amount NUMERIC(18,2) NOT NULL DEFAULT 0,
    wallet_deducted_amount NUMERIC(18,2) NOT NULL DEFAULT 0,
    external_paid_amount NUMERIC(18,2) NOT NULL DEFAULT 0,
    coupon_id UUID,
    coupon_claim_id UUID,
    currency TEXT NOT NULL DEFAULT 'CNY',
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS member_order_items (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_merchant_id UUID NOT NULL REFERENCES merchants(id),
    member_order_id UUID NOT NULL REFERENCES member_orders(id) ON DELETE CASCADE,
    product_id UUID NOT NULL REFERENCES products(id),
    product_spec_id UUID REFERENCES product_specs(id),
    product_name TEXT NOT NULL,
    qty INTEGER NOT NULL CHECK (qty > 0),
    unit_price NUMERIC(18,2) NOT NULL CHECK (unit_price >= 0),
    line_amount NUMERIC(18,2) NOT NULL CHECK (line_amount >= 0),
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS leader_order_items (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_merchant_id UUID NOT NULL REFERENCES merchants(id),
    leader_order_id UUID NOT NULL REFERENCES leader_orders(id) ON DELETE CASCADE,
    member_order_id UUID REFERENCES member_orders(id),
    product_id UUID NOT NULL REFERENCES products(id),
    product_spec_id UUID REFERENCES product_specs(id),
    product_name TEXT NOT NULL,
    qty INTEGER NOT NULL CHECK (qty > 0),
    unit_price NUMERIC(18,2) NOT NULL CHECK (unit_price >= 0),
    line_amount NUMERIC(18,2) NOT NULL CHECK (line_amount >= 0),
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS order_payment_allocations (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_merchant_id UUID NOT NULL REFERENCES merchants(id),
    member_order_id UUID NOT NULL REFERENCES member_orders(id) ON DELETE CASCADE,
    payment_source TEXT NOT NULL,
    amount NUMERIC(18,2) NOT NULL CHECK (amount >= 0),
    currency TEXT NOT NULL DEFAULT 'CNY',
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS merchant_fulfillment_orders (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_merchant_id UUID NOT NULL REFERENCES merchants(id),
    leader_order_id UUID NOT NULL REFERENCES leader_orders(id),
    merchant_id UUID NOT NULL REFERENCES merchants(id),
    supply_partner_id UUID REFERENCES supply_partners(id),
    pickup_hub_id UUID NOT NULL REFERENCES pickup_hubs(id),
    status fulfillment_status_enum NOT NULL DEFAULT 'routed',
    routed_at TIMESTAMPTZ,
    fulfilled_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS pickup_hub_receipts (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_merchant_id UUID NOT NULL REFERENCES merchants(id),
    fulfillment_order_id UUID NOT NULL REFERENCES merchant_fulfillment_orders(id),
    leader_order_id UUID NOT NULL REFERENCES leader_orders(id),
    pickup_hub_id UUID NOT NULL REFERENCES pickup_hubs(id),
    received_by_user_id UUID NOT NULL REFERENCES users(id),
    received_at TIMESTAMPTZ NOT NULL,
    note TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS pickup_hub_handovers (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_merchant_id UUID NOT NULL REFERENCES merchants(id),
    receipt_id UUID NOT NULL REFERENCES pickup_hub_receipts(id),
    pickup_hub_id UUID NOT NULL REFERENCES pickup_hubs(id),
    handover_to_type TEXT NOT NULL,
    handover_to_id UUID NOT NULL,
    handover_at TIMESTAMPTZ NOT NULL,
    note TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS delivery_tasks (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_merchant_id UUID NOT NULL REFERENCES merchants(id),
    member_order_id UUID NOT NULL REFERENCES member_orders(id),
    leader_order_id UUID NOT NULL REFERENCES leader_orders(id),
    pickup_hub_id UUID NOT NULL REFERENCES pickup_hubs(id),
    driver_user_id UUID REFERENCES users(id),
    driver_type delivery_driver_type_enum NOT NULL,
    assigned_by_user_id UUID NOT NULL REFERENCES users(id),
    status delivery_task_status_enum NOT NULL DEFAULT 'pending_assignment',
    pickup_at TIMESTAMPTZ,
    delivered_at TIMESTAMPTZ,
    fail_reason TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS wallets (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_merchant_id UUID REFERENCES merchants(id),
    owner_type owner_type_enum NOT NULL,
    owner_id UUID NOT NULL,
    currency TEXT NOT NULL,
    withdrawable_balance NUMERIC(18,2) NOT NULL DEFAULT 0,
    non_withdrawable_balance NUMERIC(18,2) NOT NULL DEFAULT 0,
    frozen_balance NUMERIC(18,2) NOT NULL DEFAULT 0,
    status TEXT NOT NULL DEFAULT 'active',
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE(owner_type, owner_id, currency)
);

CREATE TABLE IF NOT EXISTS wallet_transactions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    wallet_id UUID NOT NULL REFERENCES wallets(id),
    owner_type owner_type_enum NOT NULL,
    owner_id UUID NOT NULL,
    transaction_type wallet_tx_type_enum NOT NULL,
    direction wallet_direction_enum NOT NULL,
    amount NUMERIC(18,2) NOT NULL CHECK (amount > 0),
    balance_bucket wallet_bucket_enum NOT NULL,
    related_type TEXT,
    related_id UUID,
    reference_no TEXT,
    before_balance NUMERIC(18,2) NOT NULL,
    after_balance NUMERIC(18,2) NOT NULL,
    status TEXT NOT NULL DEFAULT 'posted',
    remark TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS recharge_orders (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    order_no TEXT NOT NULL UNIQUE,
    tenant_merchant_id UUID REFERENCES merchants(id),
    customer_user_id UUID NOT NULL REFERENCES users(id),
    wallet_id UUID NOT NULL REFERENCES wallets(id),
    currency TEXT NOT NULL,
    recharge_amount NUMERIC(18,2) NOT NULL CHECK (recharge_amount >= 0),
    bonus_amount NUMERIC(18,2) NOT NULL DEFAULT 0,
    payable_amount NUMERIC(18,2) NOT NULL CHECK (payable_amount >= 0),
    payment_method payment_method_enum NOT NULL,
    payment_status member_order_payment_status_enum NOT NULL DEFAULT 'unpaid',
    status recharge_order_status_enum NOT NULL DEFAULT 'initiated',
    initiated_by_type role_type_enum NOT NULL,
    initiated_by_id UUID NOT NULL REFERENCES users(id),
    received_by_type role_type_enum,
    received_by_id UUID,
    received_at TIMESTAMPTZ,
    proof_note TEXT,
    proof_image TEXT,
    reviewed_by UUID REFERENCES users(id),
    reviewed_at TIMESTAMPTZ,
    paid_at TIMESTAMPTZ,
    expired_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS withdrawal_requests (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_merchant_id UUID REFERENCES merchants(id),
    wallet_id UUID NOT NULL REFERENCES wallets(id),
    owner_type owner_type_enum NOT NULL,
    owner_id UUID NOT NULL,
    amount NUMERIC(18,2) NOT NULL CHECK (amount > 0),
    fee_amount NUMERIC(18,2) NOT NULL DEFAULT 0,
    net_amount NUMERIC(18,2) NOT NULL CHECK (net_amount >= 0),
    status withdrawal_request_status_enum NOT NULL DEFAULT 'pending_review',
    account_name TEXT NOT NULL,
    account_no TEXT NOT NULL,
    account_type TEXT NOT NULL,
    reviewed_by UUID REFERENCES users(id),
    reviewed_at TIMESTAMPTZ,
    remark TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS coupons (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_merchant_id UUID NOT NULL REFERENCES merchants(id),
    merchant_id UUID REFERENCES merchants(id),
    product_id UUID REFERENCES products(id),
    coupon_type coupon_type_enum NOT NULL,
    scope coupon_scope_enum NOT NULL DEFAULT 'merchant',
    title TEXT NOT NULL,
    min_order_amount NUMERIC(18,2) NOT NULL DEFAULT 0,
    discount_amount NUMERIC(18,2) NOT NULL DEFAULT 0,
    discount_rate NUMERIC(5,4) NOT NULL DEFAULT 0,
    total_qty INTEGER NOT NULL DEFAULT 0,
    claimed_qty INTEGER NOT NULL DEFAULT 0,
    starts_at TIMESTAMPTZ,
    ends_at TIMESTAMPTZ,
    status TEXT NOT NULL DEFAULT 'active',
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS coupon_user_claims (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_merchant_id UUID NOT NULL REFERENCES merchants(id),
    coupon_id UUID NOT NULL REFERENCES coupons(id),
    user_id UUID NOT NULL REFERENCES users(id),
    status coupon_claim_status_enum NOT NULL DEFAULT 'claimed',
    claimed_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    used_at TIMESTAMPTZ,
    released_at TIMESTAMPTZ,
    UNIQUE(coupon_id, user_id)
);

CREATE TABLE IF NOT EXISTS coupon_usages (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_merchant_id UUID NOT NULL REFERENCES merchants(id),
    coupon_id UUID NOT NULL REFERENCES coupons(id),
    claim_id UUID NOT NULL REFERENCES coupon_user_claims(id),
    user_id UUID NOT NULL REFERENCES users(id),
    member_order_id UUID NOT NULL REFERENCES member_orders(id),
    discount_amount NUMERIC(18,2) NOT NULL CHECK (discount_amount >= 0),
    status coupon_usage_status_enum NOT NULL DEFAULT 'used',
    used_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    released_at TIMESTAMPTZ
);

CREATE TABLE IF NOT EXISTS commissions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_merchant_id UUID NOT NULL REFERENCES merchants(id),
    member_order_id UUID NOT NULL REFERENCES member_orders(id),
    leader_user_id UUID NOT NULL REFERENCES users(id),
    commission_type TEXT NOT NULL DEFAULT 'commission',
    amount NUMERIC(18,2) NOT NULL CHECK (amount >= 0),
    state earning_state_enum NOT NULL DEFAULT 'pending',
    settled_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS pickup_hub_earnings (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_merchant_id UUID NOT NULL REFERENCES merchants(id),
    leader_order_id UUID NOT NULL REFERENCES leader_orders(id),
    pickup_hub_id UUID NOT NULL REFERENCES pickup_hubs(id),
    amount NUMERIC(18,2) NOT NULL CHECK (amount >= 0),
    state earning_state_enum NOT NULL DEFAULT 'pending',
    settled_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS delivery_fee_earnings (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_merchant_id UUID NOT NULL REFERENCES merchants(id),
    delivery_task_id UUID NOT NULL REFERENCES delivery_tasks(id),
    driver_user_id UUID NOT NULL REFERENCES users(id),
    amount NUMERIC(18,2) NOT NULL CHECK (amount >= 0),
    state earning_state_enum NOT NULL DEFAULT 'pending',
    settled_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS merchant_settlements (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_merchant_id UUID NOT NULL REFERENCES merchants(id),
    leader_order_id UUID NOT NULL REFERENCES leader_orders(id),
    merchant_id UUID NOT NULL REFERENCES merchants(id),
    amount NUMERIC(18,2) NOT NULL CHECK (amount >= 0),
    state earning_state_enum NOT NULL DEFAULT 'pending',
    settled_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS supply_partner_receivables (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_merchant_id UUID NOT NULL REFERENCES merchants(id),
    fulfillment_order_id UUID NOT NULL REFERENCES merchant_fulfillment_orders(id),
    supply_partner_id UUID NOT NULL REFERENCES supply_partners(id),
    amount NUMERIC(18,2) NOT NULL CHECK (amount >= 0),
    state earning_state_enum NOT NULL DEFAULT 'pending',
    settled_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS settlements (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    settlement_no TEXT NOT NULL UNIQUE,
    tenant_merchant_id UUID NOT NULL REFERENCES merchants(id),
    owner_type owner_type_enum NOT NULL,
    owner_id UUID NOT NULL,
    period_start TIMESTAMPTZ,
    period_end TIMESTAMPTZ,
    gross_amount NUMERIC(18,2) NOT NULL DEFAULT 0,
    fee_amount NUMERIC(18,2) NOT NULL DEFAULT 0,
    net_amount NUMERIC(18,2) NOT NULL DEFAULT 0,
    state settlement_state_enum NOT NULL DEFAULT 'pending',
    reviewed_by UUID REFERENCES users(id),
    reviewed_at TIMESTAMPTZ,
    posted_at TIMESTAMPTZ,
    paid_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS settlement_items (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    settlement_id UUID NOT NULL REFERENCES settlements(id) ON DELETE CASCADE,
    tenant_merchant_id UUID NOT NULL REFERENCES merchants(id),
    source_table TEXT NOT NULL,
    source_id UUID NOT NULL,
    owner_type owner_type_enum NOT NULL,
    owner_id UUID NOT NULL,
    amount NUMERIC(18,2) NOT NULL CHECK (amount >= 0),
    currency TEXT NOT NULL DEFAULT 'CNY',
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS ledger_entries (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_merchant_id UUID NOT NULL REFERENCES merchants(id),
    settlement_id UUID REFERENCES settlements(id),
    source_type TEXT,
    source_id UUID,
    owner_type owner_type_enum,
    owner_id UUID,
    entry_type ledger_entry_type_enum NOT NULL,
    debit_amount NUMERIC(18,2) NOT NULL DEFAULT 0,
    credit_amount NUMERIC(18,2) NOT NULL DEFAULT 0,
    currency TEXT NOT NULL DEFAULT 'CNY',
    occurred_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    remark TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
