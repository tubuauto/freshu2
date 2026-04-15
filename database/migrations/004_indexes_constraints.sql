CREATE INDEX IF NOT EXISTS idx_users_tenant_role ON users(tenant_merchant_id, role);
CREATE INDEX IF NOT EXISTS idx_member_orders_tenant_leader ON member_orders(tenant_merchant_id, leader_user_id);
CREATE INDEX IF NOT EXISTS idx_member_orders_tenant_customer ON member_orders(tenant_merchant_id, customer_user_id);
CREATE INDEX IF NOT EXISTS idx_member_orders_status ON member_orders(status, payment_status);
CREATE INDEX IF NOT EXISTS idx_leader_orders_tenant_leader ON leader_orders(tenant_merchant_id, leader_user_id);
CREATE INDEX IF NOT EXISTS idx_wallet_transactions_wallet_created ON wallet_transactions(wallet_id, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_wallet_transactions_related ON wallet_transactions(related_type, related_id);
CREATE INDEX IF NOT EXISTS idx_recharge_orders_customer_status ON recharge_orders(customer_user_id, status);
CREATE INDEX IF NOT EXISTS idx_withdrawals_owner_status ON withdrawal_requests(owner_type, owner_id, status);
CREATE INDEX IF NOT EXISTS idx_coupons_tenant_status ON coupons(tenant_merchant_id, status);
CREATE INDEX IF NOT EXISTS idx_delivery_tasks_driver_status ON delivery_tasks(driver_user_id, status);
CREATE INDEX IF NOT EXISTS idx_settlements_tenant_owner_state ON settlements(tenant_merchant_id, owner_type, owner_id, state);
CREATE INDEX IF NOT EXISTS idx_ledger_entries_tenant_time ON ledger_entries(tenant_merchant_id, occurred_at DESC);

ALTER TABLE wallets ADD CONSTRAINT chk_wallet_balances_non_negative
CHECK (withdrawable_balance >= 0 AND non_withdrawable_balance >= 0 AND frozen_balance >= 0);

ALTER TABLE settlements ADD CONSTRAINT chk_settlement_amount_non_negative
CHECK (gross_amount >= 0 AND fee_amount >= 0 AND net_amount >= 0);
