-- SQLite Schema for Testing
-- Generated from mysql-schema.sql
-- This schema is used by Laravel's RefreshDatabase trait when DB_CONNECTION=sqlite

PRAGMA foreign_keys = ON;

-- Core Laravel Tables

CREATE TABLE IF NOT EXISTS "users" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "email" TEXT NOT NULL UNIQUE,
  "email_verified_at" TEXT,
  "password" TEXT,
  "is_admin" INTEGER NOT NULL DEFAULT 0,
  "pw" TEXT,
  "salt" INTEGER NOT NULL DEFAULT 0,
  "alias" TEXT,
  "last_login_at" TEXT,
  "remember_token" TEXT,
  "ax_maxmin" INTEGER NOT NULL DEFAULT 0,
  "ax_homes" INTEGER DEFAULT 0,
  "ax_tax" INTEGER NOT NULL DEFAULT 0,
  "ax_evdb" INTEGER DEFAULT 0,
  "ax_spgp" INTEGER NOT NULL DEFAULT 0,
  "ax_uc" INTEGER NOT NULL DEFAULT 0,
  "created_at" TEXT,
  "updated_at" TEXT
);

CREATE TABLE IF NOT EXISTS "password_reset_tokens" (
  "email" TEXT PRIMARY KEY NOT NULL,
  "token" TEXT NOT NULL,
  "created_at" TEXT
);

CREATE TABLE IF NOT EXISTS "sessions" (
  "id" TEXT PRIMARY KEY NOT NULL,
  "user_id" INTEGER,
  "ip_address" TEXT,
  "user_agent" TEXT,
  "payload" TEXT NOT NULL,
  "last_activity" INTEGER NOT NULL
);
CREATE INDEX IF NOT EXISTS "sessions_user_id_index" ON "sessions" ("user_id");
CREATE INDEX IF NOT EXISTS "sessions_last_activity_index" ON "sessions" ("last_activity");

CREATE TABLE IF NOT EXISTS "cache" (
  "key" TEXT PRIMARY KEY NOT NULL,
  "value" TEXT NOT NULL,
  "expiration" INTEGER NOT NULL
);
CREATE INDEX IF NOT EXISTS "cache_expiration_index" ON "cache" ("expiration");

CREATE TABLE IF NOT EXISTS "cache_locks" (
  "key" TEXT PRIMARY KEY NOT NULL,
  "owner" TEXT NOT NULL,
  "expiration" INTEGER NOT NULL
);
CREATE INDEX IF NOT EXISTS "cache_locks_expiration_index" ON "cache_locks" ("expiration");

CREATE TABLE IF NOT EXISTS "jobs" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "queue" TEXT NOT NULL,
  "payload" TEXT NOT NULL,
  "attempts" INTEGER NOT NULL,
  "reserved_at" INTEGER,
  "available_at" INTEGER NOT NULL,
  "created_at" INTEGER NOT NULL
);
CREATE INDEX IF NOT EXISTS "jobs_queue_index" ON "jobs" ("queue");

CREATE TABLE IF NOT EXISTS "migrations" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "migration" TEXT NOT NULL,
  "batch" INTEGER NOT NULL
);

-- Shopify Multi-tenant Tables

CREATE TABLE IF NOT EXISTS "shopify_shops" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "name" TEXT NOT NULL,
  "shop_domain" TEXT NOT NULL UNIQUE,
  "app_name" TEXT,
  "admin_api_token" TEXT,
  "api_version" TEXT NOT NULL DEFAULT '2025-01',
  "api_key" TEXT,
  "api_secret_key" TEXT,
  "webhook_version" TEXT NOT NULL DEFAULT '2025-01',
  "webhook_secret" TEXT,
  "is_active" INTEGER NOT NULL DEFAULT 1,
  "created_at" TEXT,
  "updated_at" TEXT
);

CREATE TABLE IF NOT EXISTS "user_shop_accesses" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "user_id" INTEGER NOT NULL,
  "shopify_shop_id" INTEGER NOT NULL,
  "access_level" TEXT NOT NULL DEFAULT 'read-only' CHECK ("access_level" IN ('read-only', 'read-write')),
  "created_at" TEXT,
  "updated_at" TEXT,
  UNIQUE ("user_id", "shopify_shop_id"),
  FOREIGN KEY ("shopify_shop_id") REFERENCES "shopify_shops" ("id") ON DELETE CASCADE,
  FOREIGN KEY ("user_id") REFERENCES "users" ("id") ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS "user_shop_accesses_shopify_shop_id_foreign" ON "user_shop_accesses" ("shopify_shop_id");

-- Offer Tables

CREATE TABLE IF NOT EXISTS "v3_offer" (
  "offer_id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "shop_id" INTEGER,
  "is_archived" INTEGER NOT NULL DEFAULT 0,
  "offer_name" TEXT NOT NULL UNIQUE,
  "offer_variant_id" TEXT NOT NULL UNIQUE,
  "offer_product_name" TEXT NOT NULL DEFAULT '',
  FOREIGN KEY ("shop_id") REFERENCES "shopify_shops" ("id") ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS "v3_offer_shop_id_foreign" ON "v3_offer" ("shop_id");

CREATE TABLE IF NOT EXISTS "v3_offer_manifest" (
  "m_id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "offer_id" INTEGER NOT NULL,
  "mf_variant" TEXT NOT NULL,
  "assignee_id" TEXT,
  "assignment_ordering" REAL NOT NULL
);
CREATE INDEX IF NOT EXISTS "manifest_v2_offer_id_assign_order_index" ON "v3_offer_manifest" ("offer_id", "assignment_ordering");

CREATE TABLE IF NOT EXISTS "v3_order_to_variant" (
  "order_id" TEXT NOT NULL,
  "variant_id" TEXT NOT NULL,
  "offer_id" INTEGER,
  UNIQUE ("variant_id", "order_id")
);

CREATE TABLE IF NOT EXISTS "order_lock" (
  "order_id" TEXT PRIMARY KEY NOT NULL,
  "locked_at" TEXT NOT NULL
);

-- Webhook Tables

CREATE TABLE IF NOT EXISTS "webhooks" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "rerun_of_id" INTEGER,
  "created_at" TEXT,
  "updated_at" TEXT,
  "payload" TEXT,
  "headers" TEXT,
  "shopify_topic" TEXT,
  "shop_id" INTEGER,
  "valid_hmac" INTEGER,
  "valid_shop_matched" INTEGER,
  "error_ts" TEXT,
  "success_ts" TEXT,
  "error_message" TEXT,
  FOREIGN KEY ("shop_id") REFERENCES "shopify_shops" ("id") ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS "webhooks_shopify_topic_index" ON "webhooks" ("shopify_topic");
CREATE INDEX IF NOT EXISTS "webhooks_shop_id_foreign" ON "webhooks" ("shop_id");

CREATE TABLE IF NOT EXISTS "webhook_subs" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "webhook_id" INTEGER NOT NULL,
  "created_at" TEXT,
  "updated_at" TEXT,
  "event" TEXT,
  "time_taken_ms" INTEGER,
  "shopify_request" TEXT,
  "shopify_response" TEXT,
  "shopify_response_code" INTEGER,
  "offer_id" INTEGER,
  "order_id" INTEGER,
  FOREIGN KEY ("webhook_id") REFERENCES "webhooks" ("id") ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS "webhook_subs_webhook_id_foreign" ON "webhook_subs" ("webhook_id");

-- Combine Operations Tables

CREATE TABLE IF NOT EXISTS "combine_operations" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "audit_log_id" INTEGER,
  "webhook_id" INTEGER,
  "shop_id" INTEGER,
  "order_id" TEXT NOT NULL,
  "order_id_numeric" INTEGER,
  "user_id" INTEGER,
  "status" TEXT NOT NULL DEFAULT 'pending',
  "error_message" TEXT,
  "original_shipping_method" TEXT,
  "fulfillment_orders_before" INTEGER,
  "fulfillment_orders_after" INTEGER,
  "created_at" TEXT,
  "updated_at" TEXT
);
CREATE INDEX IF NOT EXISTS "combine_operations_order_id_index" ON "combine_operations" ("order_id");
CREATE INDEX IF NOT EXISTS "combine_operations_shop_id_index" ON "combine_operations" ("shop_id");
CREATE INDEX IF NOT EXISTS "combine_operations_audit_log_id_index" ON "combine_operations" ("audit_log_id");
CREATE INDEX IF NOT EXISTS "combine_operations_webhook_id_index" ON "combine_operations" ("webhook_id");

CREATE TABLE IF NOT EXISTS "combine_operation_logs" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "combine_operation_id" INTEGER NOT NULL,
  "event" TEXT,
  "time_taken_ms" INTEGER,
  "shopify_request" TEXT,
  "shopify_response" TEXT,
  "shopify_response_code" INTEGER,
  "created_at" TEXT,
  "updated_at" TEXT,
  FOREIGN KEY ("combine_operation_id") REFERENCES "combine_operations" ("id") ON DELETE CASCADE
);

-- Audit Log Table

CREATE TABLE IF NOT EXISTS "v3_audit_log" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "event_ts" TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "event_name" TEXT NOT NULL,
  "event_ext" TEXT,
  "event_userid" INTEGER,
  "offer_id" INTEGER,
  "order_id" INTEGER,
  "time_taken_ms" INTEGER
);

-- Shopify Product Cache

CREATE TABLE IF NOT EXISTS "shopify_product_variant" (
  "variantId" TEXT PRIMARY KEY NOT NULL,
  "productId" TEXT NOT NULL,
  "productName" TEXT NOT NULL,
  "variantName" TEXT NOT NULL,
  "variantPrice" TEXT,
  "variantCompareAtPrice" TEXT,
  "variantInventoryQuantity" INTEGER NOT NULL,
  "variantSku" TEXT NOT NULL,
  "variantWeight" TEXT
);

-- Item Tables (used by application)

CREATE TABLE IF NOT EXISTS "item_detail" (
  "itemdetail_guid" TEXT PRIMARY KEY NOT NULL,
  "cola_id" TEXT,
  "cola_name" TEXT,
  "cola_region" TEXT,
  "cola_appellation" TEXT,
  "cola_varietal" TEXT,
  "cola_vintage" TEXT,
  "cola_abv" REAL,
  "about_wine" TEXT,
  "tasting_notes" TEXT,
  "winemaker_notes" TEXT,
  "label_img_url" TEXT,
  "bottle_img_url" TEXT,
  "retail_price" REAL,
  "winery_id" TEXT,
  "url_key" TEXT,
  "brand" TEXT,
  "country_code" TEXT,
  "upc" TEXT,
  "is_wine" TEXT,
  "is_beer" TEXT,
  "is_liquor" TEXT,
  "is_sparkling" TEXT,
  "is_cult" TEXT,
  "is_small_production" TEXT,
  "ct_wine_id" INTEGER,
  "ct_producer_id" INTEGER,
  "ct_likes" INTEGER,
  "ct_tasting_notes" INTEGER,
  "ct_review" INTEGER,
  "ct_community_score" TEXT,
  "ct_qty" INTEGER,
  "wine_vineyard" TEXT,
  "wine_web_url" TEXT,
  "wine_drink_start" TEXT,
  "wine_drink_end" TEXT,
  "wine_producer_uuid" TEXT,
  "redirect_to" TEXT,
  "item_tsv" TEXT,
  "wine_ml" TEXT,
  "cola_fanciful_name" TEXT,
  "wd_varietal" TEXT,
  "wd_region" TEXT,
  "is_blend" TEXT,
  "price_range" TEXT,
  "item_lbs" TEXT,
  "category" TEXT,
  "blur_bottle_img" TEXT,
  "blur_label_img" TEXT
);
CREATE INDEX IF NOT EXISTS "item_detail_retail_price" ON "item_detail" ("retail_price");
CREATE INDEX IF NOT EXISTS "item_detail_cola_varietal" ON "item_detail" ("cola_varietal");
CREATE INDEX IF NOT EXISTS "item_detail_cola_appellation" ON "item_detail" ("cola_appellation");
CREATE INDEX IF NOT EXISTS "item_detail_cola_region" ON "item_detail" ("cola_region");

CREATE TABLE IF NOT EXISTS "item_sku" (
  "sku" TEXT PRIMARY KEY NOT NULL,
  "srp" REAL,
  "is_autographed" INTEGER,
  "is_taxable" INTEGER,
  "is_counted_for_shipment" INTEGER,
  "drink_by_date" TEXT,
  "sku_itemdetail_guid" TEXT,
  "index" INTEGER,
  "last_order_date" TEXT,
  "last_restock" TEXT,
  "last_stock_update" TEXT,
  "last_stock_qty" INTEGER NOT NULL DEFAULT 0,
  "next_delivery_date" TEXT,
  "last_count_owed" INTEGER NOT NULL DEFAULT 0,
  "x_friendly_name" TEXT,
  "scramble_letters" TEXT,
  "scramble_qty_allowed" INTEGER NOT NULL DEFAULT 0,
  "sku_allowed_states" TEXT,
  "comment" TEXT,
  "sku_tsv" TEXT,
  "sku_cogs_unit" REAL NOT NULL DEFAULT 0.00,
  "is_pallet_program" INTEGER NOT NULL DEFAULT 0,
  "is_deprecated" INTEGER,
  "sku_varietal" TEXT,
  "sku_region" TEXT,
  "last_count_shipped" INTEGER NOT NULL DEFAULT 0,
  "is_in_wd" INTEGER NOT NULL DEFAULT 0,
  "sku_was_swap" INTEGER NOT NULL DEFAULT 0,
  "sku_sort" INTEGER NOT NULL DEFAULT 0,
  "sku_preswap" TEXT,
  "sku_postswap" TEXT,
  "sku_qty_reserved" INTEGER DEFAULT 0,
  "sku_cogs_is_estimated" INTEGER NOT NULL DEFAULT 0,
  "sku_taxset_id" TEXT,
  "qty_offsite" INTEGER NOT NULL DEFAULT 0,
  "sku_external_id" TEXT,
  "sku_fq_lo" INTEGER NOT NULL DEFAULT 0,
  "sku_fq_hi" INTEGER NOT NULL DEFAULT 35,
  "sku_velocity" REAL NOT NULL DEFAULT 0.00,
  "last_vip_qty" INTEGER NOT NULL DEFAULT 0,
  "last_open_xfer_qty" INTEGER NOT NULL DEFAULT 0,
  "sku_is_dropship" INTEGER NOT NULL DEFAULT 0,
  "sku_ship_alone" INTEGER NOT NULL DEFAULT 0,
  "sku_supplier_guid" TEXT,
  "next_stock_update" TEXT,
  "sku_exclude_metrics" INTEGER DEFAULT 0,
  "netsuite_synced" TEXT,
  "category" TEXT,
  "country_code" TEXT,
  "unlimited_allocation_until" TEXT,
  "avg_purchase_price" REAL,
  "last_purchase_price" REAL,
  "dont_buy_after" TEXT,
  "pack_size" INTEGER
);

-- Legacy User/Order Tables (may be referenced by application)

CREATE TABLE IF NOT EXISTS "user_list" (
  "user_guid" TEXT PRIMARY KEY NOT NULL,
  "user_birthday" TEXT,
  "user_default_address" TEXT,
  "user_email" TEXT,
  "user_fname" TEXT,
  "user_lname" TEXT,
  "user_is21" TEXT,
  "user_is_testaccount" TEXT,
  "user_image_url" TEXT,
  "user_url_profile" TEXT,
  "user_name" TEXT,
  "user_login_dt" TEXT,
  "user_signup_dt" TEXT,
  "user_last_purchase_dt" TEXT,
  "user_first_purchase_dt" TEXT,
  "user_referred_by_id" TEXT,
  "user_referral_domain" TEXT,
  "session_utm_source" TEXT,
  "session_utm_campaign" TEXT,
  "session_utm_medium" TEXT,
  "x_life_credit" REAL,
  "x_total_qty" INTEGER,
  "x_life_spend" REAL,
  "x_life_discount" REAL,
  "x_acquisition_cost" REAL,
  "x_achievement_points" INTEGER,
  "user_is_private" TEXT,
  "user_is_red_buyer" TEXT,
  "user_is_white_buyer" TEXT,
  "user_is_largeformat_buyer" TEXT,
  "user_min_price" TEXT,
  "user_max_price" TEXT,
  "user_avg_price" TEXT,
  "user_is_push" TEXT,
  "user_outreach_dt" TEXT,
  "ls_is_student" TEXT,
  "ls_is_personal_email" TEXT,
  "ls_grade" TEXT,
  "ls_company_state_code" TEXT,
  "ls_fname" TEXT,
  "ls_lname" TEXT,
  "ls_location_state" TEXT,
  "ls_company_name" TEXT,
  "ls_company_industry" TEXT,
  "ls_company_country" TEXT,
  "ls_company_emps" TEXT,
  "ls_is_spam" TEXT,
  "ls_customer_fit" TEXT,
  "ls_customer_fit_ext" TEXT,
  "x_order_count" INTEGER,
  "user_inactive_dt" TEXT,
  "user_email_n" TEXT,
  "user_note" TEXT,
  "user_expiry" TEXT,
  "user_last_ship_date" TEXT,
  "x_cloud_value" REAL,
  "x_cloud_count" INTEGER,
  "user_is_vip" TEXT,
  "user_signup_ym_pst" TEXT,
  "suspended_at" TEXT,
  "last_synced_at" TEXT,
  "is_admin" TEXT,
  "utm_content" TEXT,
  "utm_term" TEXT,
  "user_password" TEXT,
  "stripe_customer_id" TEXT,
  "google_id" TEXT,
  "utm_device" TEXT,
  "utm_placement" TEXT,
  "utm_site" TEXT,
  "holdout_num" TEXT
);
CREATE INDEX IF NOT EXISTS "user_list_user_email" ON "user_list" ("user_email");

CREATE TABLE IF NOT EXISTS "order_list" (
  "order_guid" TEXT PRIMARY KEY NOT NULL,
  "order_sku_list" TEXT,
  "order_user" TEXT,
  "order_billing_address" TEXT,
  "order_qty" INTEGER,
  "order_total_price" REAL,
  "order_discount" REAL,
  "order_credit_discount" REAL,
  "order_tax" REAL,
  "order_timestamp" TEXT,
  "order_status" TEXT,
  "order_payment_status" TEXT,
  "order_offer_id" TEXT,
  "order_utm_source" TEXT,
  "order_utm_medium" TEXT,
  "order_utm_campaign" TEXT,
  "order_user_nth" INTEGER,
  "order_auth_code" TEXT,
  "order_auth_date" TEXT,
  "order_billing_instrument" TEXT,
  "order_transaction_id" TEXT,
  "x_user_email" TEXT,
  "order_unit_price" REAL,
  "order_promo_code" TEXT,
  "x_order_is_authorized_or_captured" TEXT,
  "cohort_mth" TEXT,
  "order_reveal_date" TEXT,
  "cohort_fp_mth" REAL,
  "is_test_order" TEXT,
  "order_rejected_dt" TEXT,
  "order_upgraded_value" REAL,
  "order_allocated_cogs" REAL,
  "order_cohort_fpdate" TEXT,
  "order_cc_fee" REAL,
  "order_refund_transaction_id" TEXT,
  "order_original_mf" TEXT,
  "order_yymm_pst" TEXT,
  "order_mc_eid" TEXT,
  "order_mc_cid" TEXT,
  "order_subscription_id" TEXT,
  "order_is_void" TEXT,
  "order_cash_in" REAL,
  "order_disc_c" REAL,
  "order_disc_f" REAL,
  "order_disc_s" REAL,
  "order_disc_r" REAL,
  "order_disc_t" REAL,
  "order_disc_m" REAL,
  "order_disc_g" REAL,
  "order_disc_other" REAL,
  "order_ship_revenue" REAL,
  "order_previous_order" TEXT,
  "utm_content" TEXT,
  "utm_term" TEXT,
  "netsuite_synced" TEXT,
  "payment_intent_id" TEXT,
  "utm_device" TEXT,
  "utm_placement" TEXT,
  "utm_site" TEXT,
  "order_type" TEXT
);

CREATE TABLE IF NOT EXISTS "computed_buyer_varietals" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "winner_guid" TEXT NOT NULL,
  "cola_varietal" TEXT NOT NULL,
  "total_paid" REAL NOT NULL
);
CREATE INDEX IF NOT EXISTS "computed_buyer_varietals_cola_varietal" ON "computed_buyer_varietals" ("cola_varietal");

-- Insert migration records to prevent RefreshDatabase from re-running migrations
-- These must match the migration files in database/migrations
INSERT INTO "migrations" ("migration", "batch") VALUES ('2026_01_17_065914_update_users_table_for_laravel_auth', 1);
INSERT INTO "migrations" ("migration", "batch") VALUES ('2026_01_17_071659_create_sessions_table', 1);
INSERT INTO "migrations" ("migration", "batch") VALUES ('2026_01_17_073317_add_last_login_at_to_users_table', 2);
INSERT INTO "migrations" ("migration", "batch") VALUES ('2026_01_17_080205_create_cache_table', 3);
INSERT INTO "migrations" ("migration", "batch") VALUES ('2026_01_17_091808_create_shopify_shops_table', 4);
INSERT INTO "migrations" ("migration", "batch") VALUES ('2026_01_17_091812_create_user_shop_accesses_table', 5);
INSERT INTO "migrations" ("migration", "batch") VALUES ('2026_01_17_091816_remove_deprecated_user_fields_and_add_is_admin', 5);
INSERT INTO "migrations" ("migration", "batch") VALUES ('2026_01_17_091820_add_shop_id_to_offers_table', 5);
INSERT INTO "migrations" ("migration", "batch") VALUES ('2026_01_20_040740_create_jobs_table', 6);
INSERT INTO "migrations" ("migration", "batch") VALUES ('2026_01_20_062828_create_webhooks_tables', 7);
INSERT INTO "migrations" ("migration", "batch") VALUES ('2026_01_20_065704_add_time_taken_ms_to_webhook_subs_table', 8);
INSERT INTO "migrations" ("migration", "batch") VALUES ('2026_01_20_080132_add_is_archived_to_v3_offer_table', 9);
INSERT INTO "migrations" ("migration", "batch") VALUES ('2026_01_20_090000_add_topic_and_shop_id_to_webhooks_table', 10);
INSERT INTO "migrations" ("migration", "batch") VALUES ('2026_01_30_051618_create_combine_operation_logs_table', 11);
INSERT INTO "migrations" ("migration", "batch") VALUES ('2026_01_30_053444_add_webhook_id_to_combine_operations_table', 12);

-- Note: RefreshDatabase trait handles migrations table automatically
