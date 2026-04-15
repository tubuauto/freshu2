DO $$ BEGIN
    CREATE TYPE role_type_enum AS ENUM ('admin','customer','leader','merchant','supply_partner','pickup_hub','driver');
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

DO $$ BEGIN
    CREATE TYPE owner_type_enum AS ENUM ('customer','leader','merchant','pickup_hub','driver','supply_partner');
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

DO $$ BEGIN
    CREATE TYPE wallet_bucket_enum AS ENUM ('withdrawable_balance','non_withdrawable_balance','frozen_balance');
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

DO $$ BEGIN
    CREATE TYPE wallet_direction_enum AS ENUM ('credit','debit');
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

DO $$ BEGIN
    CREATE TYPE member_order_status_enum AS ENUM (
        'draft','awaiting_payment','paid','assigned_to_leader_order','merchant_fulfilled','at_pickup_hub',
        'out_for_delivery','delivered','completed','cancelled','refunded'
    );
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

DO $$ BEGIN
    CREATE TYPE member_order_payment_status_enum AS ENUM (
        'unpaid','partially_paid','paid','failed','partially_refunded','refunded'
    );
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

DO $$ BEGIN
    CREATE TYPE member_order_collection_status_enum AS ENUM (
        'not_collected','pending_offline_collection','collected_offline','waived','disputed'
    );
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

DO $$ BEGIN
    CREATE TYPE leader_order_status_enum AS ENUM (
        'draft','submitted','confirmed','paid','routed_to_supply_partner','in_fulfillment',
        'merchant_fulfilled','at_pickup_hub','handed_over','completed','cancelled'
    );
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

DO $$ BEGIN
    CREATE TYPE delivery_task_status_enum AS ENUM (
        'pending_assignment','assigned','accepted','picked_up','out_for_delivery','delivered','failed','cancelled'
    );
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

DO $$ BEGIN
    CREATE TYPE recharge_order_status_enum AS ENUM (
        'initiated','awaiting_payment','awaiting_offline_confirmation','awaiting_review',
        'confirmed','paid','failed','expired','cancelled'
    );
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

DO $$ BEGIN
    CREATE TYPE withdrawal_request_status_enum AS ENUM (
        'pending_review','approved','rejected','processing','completed','failed','cancelled'
    );
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

DO $$ BEGIN
    CREATE TYPE wallet_tx_type_enum AS ENUM (
        'recharge_credit','recharge_bonus_credit','cash_recharge_credit','wallet_payment_debit','wallet_refund_credit',
        'withdrawal_freeze','withdrawal_release','withdrawal_complete_debit','withdrawal_reject_return',
        'settlement_credit','settlement_debit','adjustment_freeze','adjustment_unfreeze'
    );
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

DO $$ BEGIN
    CREATE TYPE ledger_entry_type_enum AS ENUM (
        'recharge_inflow','order_payment_inflow','coupon_discount_expense','commission_expense',
        'pickup_hub_fee_expense','delivery_fee_expense','merchant_settlement_payable',
        'supply_partner_settlement_payable','withdrawal_payable','refund_outflow','platform_adjustment'
    );
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

DO $$ BEGIN
    CREATE TYPE settlement_state_enum AS ENUM (
        'pending','calculating','ready_for_review','approved','rejected','posted','paid','partially_paid','closed','cancelled'
    );
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

DO $$ BEGIN
    CREATE TYPE earning_state_enum AS ENUM ('pending','settled','cancelled');
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

DO $$ BEGIN
    CREATE TYPE payment_method_enum AS ENUM ('online','wallet','cash','external');
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

DO $$ BEGIN
    CREATE TYPE coupon_type_enum AS ENUM ('threshold_discount','no_threshold','percentage_discount','new_user');
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

DO $$ BEGIN
    CREATE TYPE coupon_scope_enum AS ENUM ('merchant','product','global');
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

DO $$ BEGIN
    CREATE TYPE coupon_claim_status_enum AS ENUM ('claimed','used','released','expired');
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

DO $$ BEGIN
    CREATE TYPE coupon_usage_status_enum AS ENUM ('used','released','cancelled');
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

DO $$ BEGIN
    CREATE TYPE delivery_driver_type_enum AS ENUM ('leader_self','independent_driver');
EXCEPTION WHEN duplicate_object THEN NULL; END $$;

DO $$ BEGIN
    CREATE TYPE fulfillment_status_enum AS ENUM ('routed','in_fulfillment','merchant_fulfilled','cancelled');
EXCEPTION WHEN duplicate_object THEN NULL; END $$;
