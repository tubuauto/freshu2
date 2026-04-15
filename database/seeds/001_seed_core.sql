-- Core tenant and users
INSERT INTO merchants (id, name, contact_name, contact_phone, status)
VALUES ('10000000-0000-0000-0000-000000000001', 'Fresh2U Demo Merchant', 'Ops Team', '13000000000', 'active')
ON CONFLICT (id) DO NOTHING;

INSERT INTO users (id, tenant_merchant_id, role, email, password_hash, display_name, bound_leader_user_id, status)
VALUES
('00000000-0000-0000-0000-000000000001', '10000000-0000-0000-0000-000000000001', 'admin', 'admin@fresh2u.local', '$argon2id$v=19$m=65536,t=4,p=1$dUQvZWJNampqb2hRUEdFaA$vk7ZLscI09vqYnEKDzpRAGu7Gpl5ynkLnpGBPczFJLI', 'System Admin', NULL, 'active'),
('00000000-0000-0000-0000-000000000002', '10000000-0000-0000-0000-000000000001', 'merchant', 'merchant1@fresh2u.local', '$argon2id$v=19$m=65536,t=4,p=1$dUQvZWJNampqb2hRUEdFaA$vk7ZLscI09vqYnEKDzpRAGu7Gpl5ynkLnpGBPczFJLI', 'Merchant One', NULL, 'active'),
('00000000-0000-0000-0000-000000000003', '10000000-0000-0000-0000-000000000001', 'leader', 'leader1@fresh2u.local', '$argon2id$v=19$m=65536,t=4,p=1$dUQvZWJNampqb2hRUEdFaA$vk7ZLscI09vqYnEKDzpRAGu7Gpl5ynkLnpGBPczFJLI', 'Leader One', NULL, 'active'),
('00000000-0000-0000-0000-000000000004', '10000000-0000-0000-0000-000000000001', 'customer', 'customer1@fresh2u.local', '$argon2id$v=19$m=65536,t=4,p=1$dUQvZWJNampqb2hRUEdFaA$vk7ZLscI09vqYnEKDzpRAGu7Gpl5ynkLnpGBPczFJLI', 'Customer One', '00000000-0000-0000-0000-000000000003', 'active'),
('00000000-0000-0000-0000-000000000005', '10000000-0000-0000-0000-000000000001', 'pickup_hub', 'hub1@fresh2u.local', '$argon2id$v=19$m=65536,t=4,p=1$dUQvZWJNampqb2hRUEdFaA$vk7ZLscI09vqYnEKDzpRAGu7Gpl5ynkLnpGBPczFJLI', 'Hub One', NULL, 'active'),
('00000000-0000-0000-0000-000000000006', '10000000-0000-0000-0000-000000000001', 'driver', 'driver1@fresh2u.local', '$argon2id$v=19$m=65536,t=4,p=1$dUQvZWJNampqb2hRUEdFaA$vk7ZLscI09vqYnEKDzpRAGu7Gpl5ynkLnpGBPczFJLI', 'Driver One', NULL, 'active'),
('00000000-0000-0000-0000-000000000007', '10000000-0000-0000-0000-000000000001', 'supply_partner', 'supplier1@fresh2u.local', '$argon2id$v=19$m=65536,t=4,p=1$dUQvZWJNampqb2hRUEdFaA$vk7ZLscI09vqYnEKDzpRAGu7Gpl5ynkLnpGBPczFJLI', 'Supplier One', NULL, 'active')
ON CONFLICT (id) DO NOTHING;

INSERT INTO leader_profiles (id, tenant_merchant_id, user_id, team_name, region)
VALUES ('11000000-0000-0000-0000-000000000001', '10000000-0000-0000-0000-000000000001', '00000000-0000-0000-0000-000000000003', 'Morning Group', 'Downtown')
ON CONFLICT (id) DO NOTHING;

INSERT INTO customer_profiles (id, tenant_merchant_id, user_id, bound_leader_user_id, address_line)
VALUES ('12000000-0000-0000-0000-000000000001', '10000000-0000-0000-0000-000000000001', '00000000-0000-0000-0000-000000000004', '00000000-0000-0000-0000-000000000003', 'No.1 Sample Street')
ON CONFLICT (id) DO NOTHING;

INSERT INTO pickup_hubs (id, tenant_merchant_id, user_id, hub_name, address_line, status)
VALUES ('20000000-0000-0000-0000-000000000001', '10000000-0000-0000-0000-000000000001', '00000000-0000-0000-0000-000000000005', 'Central Pickup Hub', 'Warehouse A', 'active')
ON CONFLICT (id) DO NOTHING;

INSERT INTO drivers (id, tenant_merchant_id, user_id, driver_type, status)
VALUES ('40000000-0000-0000-0000-000000000001', '10000000-0000-0000-0000-000000000001', '00000000-0000-0000-0000-000000000006', 'independent_driver', 'active')
ON CONFLICT (id) DO NOTHING;

INSERT INTO supply_partners (id, tenant_merchant_id, user_id, partner_name, status)
VALUES ('30000000-0000-0000-0000-000000000001', '10000000-0000-0000-0000-000000000001', '00000000-0000-0000-0000-000000000007', 'Supplier Alpha', 'active')
ON CONFLICT (id) DO NOTHING;

-- Wallets
INSERT INTO wallets (id, tenant_merchant_id, owner_type, owner_id, currency, withdrawable_balance, non_withdrawable_balance, frozen_balance, status)
VALUES
('50000000-0000-0000-0000-000000000001', '10000000-0000-0000-0000-000000000001', 'customer', '00000000-0000-0000-0000-000000000004', 'CNY', 120.00, 20.00, 0.00, 'active'),
('50000000-0000-0000-0000-000000000002', '10000000-0000-0000-0000-000000000001', 'leader', '00000000-0000-0000-0000-000000000003', 'CNY', 0.00, 0.00, 0.00, 'active'),
('50000000-0000-0000-0000-000000000003', '10000000-0000-0000-0000-000000000001', 'merchant', '10000000-0000-0000-0000-000000000001', 'CNY', 0.00, 0.00, 0.00, 'active'),
('50000000-0000-0000-0000-000000000004', '10000000-0000-0000-0000-000000000001', 'pickup_hub', '20000000-0000-0000-0000-000000000001', 'CNY', 0.00, 0.00, 0.00, 'active'),
('50000000-0000-0000-0000-000000000005', '10000000-0000-0000-0000-000000000001', 'driver', '00000000-0000-0000-0000-000000000006', 'CNY', 0.00, 0.00, 0.00, 'active'),
('50000000-0000-0000-0000-000000000006', '10000000-0000-0000-0000-000000000001', 'supply_partner', '30000000-0000-0000-0000-000000000001', 'CNY', 0.00, 0.00, 0.00, 'active')
ON CONFLICT (id) DO NOTHING;

INSERT INTO wallet_transactions (
  id, wallet_id, owner_type, owner_id, transaction_type, direction, amount, balance_bucket,
  related_type, related_id, reference_no, before_balance, after_balance, status, remark, created_at
)
VALUES
('51000000-0000-0000-0000-000000000001', '50000000-0000-0000-0000-000000000001', 'customer', '00000000-0000-0000-0000-000000000004', 'recharge_credit', 'credit', 120.00, 'withdrawable_balance', 'seed', NULL, 'SEED-CUSTOMER-001', 0.00, 120.00, 'posted', 'Seed initial recharge balance', NOW()),
('51000000-0000-0000-0000-000000000002', '50000000-0000-0000-0000-000000000001', 'customer', '00000000-0000-0000-0000-000000000004', 'recharge_bonus_credit', 'credit', 20.00, 'non_withdrawable_balance', 'seed', NULL, 'SEED-CUSTOMER-002', 0.00, 20.00, 'posted', 'Seed initial bonus balance', NOW())
ON CONFLICT (id) DO NOTHING;

-- Products and campaigns
INSERT INTO products (id, tenant_merchant_id, merchant_id, name, description, base_price, status)
VALUES
('60000000-0000-0000-0000-000000000001', '10000000-0000-0000-0000-000000000001', '10000000-0000-0000-0000-000000000001', 'Organic Tomato Box', '2kg group-buy tomato box', 18.00, 'active'),
('60000000-0000-0000-0000-000000000002', '10000000-0000-0000-0000-000000000001', '10000000-0000-0000-0000-000000000001', 'Fresh Cucumber Pack', '1.5kg cucumber pack', 12.00, 'active')
ON CONFLICT (id) DO NOTHING;

INSERT INTO product_specs (id, tenant_merchant_id, product_id, spec_name, spec_value, price_delta, stock_qty)
VALUES
('61000000-0000-0000-0000-000000000001', '10000000-0000-0000-0000-000000000001', '60000000-0000-0000-0000-000000000001', 'size', '2kg', 0.00, 1000),
('61000000-0000-0000-0000-000000000002', '10000000-0000-0000-0000-000000000001', '60000000-0000-0000-0000-000000000002', 'size', '1.5kg', 0.00, 1000)
ON CONFLICT (id) DO NOTHING;

INSERT INTO leader_product_campaigns (id, tenant_merchant_id, leader_user_id, product_id, campaign_title, campaign_price, starts_at, ends_at, status)
VALUES
('62000000-0000-0000-0000-000000000001', '10000000-0000-0000-0000-000000000001', '00000000-0000-0000-0000-000000000003', '60000000-0000-0000-0000-000000000001', 'Morning Tomato Deal', 16.50, NOW() - INTERVAL '1 day', NOW() + INTERVAL '10 day', 'active')
ON CONFLICT (id) DO NOTHING;

-- Coupons
INSERT INTO coupons (
  id, tenant_merchant_id, merchant_id, product_id, coupon_type, scope, title,
  min_order_amount, discount_amount, discount_rate, total_qty, claimed_qty,
  starts_at, ends_at, status
)
VALUES
('70000000-0000-0000-0000-000000000001', '10000000-0000-0000-0000-000000000001', '10000000-0000-0000-0000-000000000001', NULL, 'threshold_discount', 'merchant', 'Spend 50 Save 10', 50.00, 10.00, 0.0000, 1000, 1, NOW() - INTERVAL '1 day', NOW() + INTERVAL '20 day', 'active'),
('70000000-0000-0000-0000-000000000002', '10000000-0000-0000-0000-000000000001', '10000000-0000-0000-0000-000000000001', NULL, 'percentage_discount', 'merchant', 'New User 15% Off', 0.00, 0.00, 0.1500, 1000, 1, NOW() - INTERVAL '1 day', NOW() + INTERVAL '20 day', 'active')
ON CONFLICT (id) DO NOTHING;

INSERT INTO coupon_user_claims (id, tenant_merchant_id, coupon_id, user_id, status, claimed_at)
VALUES
('71000000-0000-0000-0000-000000000001', '10000000-0000-0000-0000-000000000001', '70000000-0000-0000-0000-000000000001', '00000000-0000-0000-0000-000000000004', 'claimed', NOW()),
('71000000-0000-0000-0000-000000000002', '10000000-0000-0000-0000-000000000001', '70000000-0000-0000-0000-000000000002', '00000000-0000-0000-0000-000000000004', 'claimed', NOW())
ON CONFLICT (id) DO NOTHING;
