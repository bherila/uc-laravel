SET FOREIGN_KEY_CHECKS = 0;

-- 1. Drop tables that are present in Prod but not in Dev
-- (Skipped per instructions: Preserving 20251106_deduped_new_variant_ids, 20251106_v3_offer_manifest)


-- 2. Create new tables present in Dev
CREATE TABLE IF NOT EXISTS `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `shopify_shops` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `shop_domain` varchar(255) NOT NULL,
  `app_name` varchar(1024) DEFAULT NULL,
  `admin_api_token` varchar(1024) DEFAULT NULL,
  `api_version` varchar(1024) NOT NULL DEFAULT '2025-01',
  `api_key` varchar(1024) DEFAULT NULL,
  `api_secret_key` varchar(1024) DEFAULT NULL,
  `webhook_version` varchar(1024) NOT NULL DEFAULT '2025-01',
  `webhook_secret` varchar(1024) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `shopify_shops_shop_domain_unique` (`shop_domain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 3. Modify existing tables to match Dev schema (Engine, Charset, Columns)

-- 2023_05_31_inventory
ALTER TABLE `2023_05_31_inventory` ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE `2023_05_31_inventory` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- computed_buyer_varietals
ALTER TABLE `computed_buyer_varietals` ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE `computed_buyer_varietals` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `computed_buyer_varietals` COMMENT = '';

-- customer_list_july_2023
ALTER TABLE `customer_list_july_2023` ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE `customer_list_july_2023` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `customer_list_july_2023` MODIFY `orders` int(11) DEFAULT NULL;

-- item_detail
ALTER TABLE `item_detail` ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE `item_detail` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `item_detail` MODIFY `ct_wine_id` int(11) DEFAULT NULL;
ALTER TABLE `item_detail` MODIFY `ct_review` int(11) DEFAULT NULL;
ALTER TABLE `item_detail` MODIFY `ct_qty` int(11) DEFAULT NULL;

-- item_sku
ALTER TABLE `item_sku` ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE `item_sku` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `item_sku` MODIFY `sku_fq_lo` int(11) NOT NULL DEFAULT 0;
ALTER TABLE `item_sku` MODIFY `sku_fq_hi` int(11) NOT NULL DEFAULT 35;

-- member_list_export_2023_07_06
ALTER TABLE `member_list_export_2023_07_06` ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE `member_list_export_2023_07_06` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- new_customer_data_after_bk_from_lcc
ALTER TABLE `new_customer_data_after_bk_from_lcc` ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE `new_customer_data_after_bk_from_lcc` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `new_customer_data_after_bk_from_lcc` MODIFY `Customer ID` bigint(20) NOT NULL AUTO_INCREMENT;
ALTER TABLE `new_customer_data_after_bk_from_lcc` MODIFY `Order Count` int(11) DEFAULT NULL;
ALTER TABLE `new_customer_data_after_bk_from_lcc` MODIFY `Item Count` int(11) DEFAULT NULL;

-- old_order_data_500k_orders
ALTER TABLE `old_order_data_500k_orders` ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE `old_order_data_500k_orders` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `old_order_data_500k_orders` MODIFY `order_qty` int(11) DEFAULT NULL;

-- order_list
ALTER TABLE `order_list` ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE `order_list` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `order_list` MODIFY `order_qty` int(11) DEFAULT NULL;
ALTER TABLE `order_list` MODIFY `order_user_nth` int(11) DEFAULT NULL;

-- order_lock
ALTER TABLE `order_lock` ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- user_list
ALTER TABLE `user_list` ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE `user_list` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `user_list` MODIFY `x_total_qty` int(11) DEFAULT NULL;
ALTER TABLE `user_list` MODIFY `x_achievement_points` int(11) DEFAULT NULL;
ALTER TABLE `user_list` MODIFY `x_order_count` int(11) DEFAULT NULL;
ALTER TABLE `user_list` MODIFY `x_cloud_count` int(11) DEFAULT NULL;

-- users (Major Schema Update)
-- Rename uid -> id
ALTER TABLE `users` CHANGE `uid` `id` bigint(20) NOT NULL AUTO_INCREMENT;
-- KEEP `pw` as is.
-- KEEP `salt` as is.
-- KEEP `ax_*` columns as is.
-- ADD `password` (new, nullable, for Laravel)
ALTER TABLE `users` ADD COLUMN `password` varchar(100) DEFAULT NULL AFTER `email`;
-- Add other new columns
ALTER TABLE `users` ADD COLUMN `email_verified_at` timestamp NULL DEFAULT NULL AFTER `email`;
ALTER TABLE `users` ADD COLUMN `is_admin` tinyint(1) NOT NULL DEFAULT 0 AFTER `password`;
ALTER TABLE `users` ADD COLUMN `last_login_at` timestamp NULL DEFAULT NULL AFTER `alias`;
ALTER TABLE `users` ADD COLUMN `remember_token` varchar(100) DEFAULT NULL AFTER `last_login_at`;
ALTER TABLE `users` ADD COLUMN `created_at` timestamp NULL DEFAULT NULL;
ALTER TABLE `users` ADD COLUMN `updated_at` timestamp NULL DEFAULT NULL;
-- Ensure engine
ALTER TABLE `users` ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Create junction tables and add foreign keys
CREATE TABLE IF NOT EXISTS `user_shop_accesses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL,
  `shopify_shop_id` bigint(20) unsigned NOT NULL,
  `access_level` enum('read-only','read-write') NOT NULL DEFAULT 'read-only',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_shop_accesses_user_id_shopify_shop_id_unique` (`user_id`,`shopify_shop_id`),
  KEY `user_shop_accesses_shopify_shop_id_foreign` (`shopify_shop_id`),
  CONSTRAINT `user_shop_accesses_shopify_shop_id_foreign` FOREIGN KEY (`shopify_shop_id`) REFERENCES `shopify_shops` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_shop_accesses_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- v3_audit_log
ALTER TABLE `v3_audit_log` ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE `v3_audit_log` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- v3_offer
ALTER TABLE `v3_offer` ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE `v3_offer` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- Add shop_id and foreign key
ALTER TABLE `v3_offer` ADD COLUMN `shop_id` bigint(20) unsigned DEFAULT NULL AFTER `offer_id`;
ALTER TABLE `v3_offer` ADD CONSTRAINT `v3_offer_shop_id_foreign` FOREIGN KEY (`shop_id`) REFERENCES `shopify_shops` (`id`) ON DELETE SET NULL;

-- v3_offer_manifest
ALTER TABLE `v3_offer_manifest` ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE `v3_offer_manifest` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `v3_offer_manifest` MODIFY `mf_variant` varchar(50) NOT NULL;
ALTER TABLE `v3_offer_manifest` MODIFY `assignee_id` varchar(50) DEFAULT NULL;

-- v3_order_to_variant
ALTER TABLE `v3_order_to_variant` ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE `v3_order_to_variant` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `v3_order_to_variant` MODIFY `order_id` varchar(100) NOT NULL;
ALTER TABLE `v3_order_to_variant` MODIFY `variant_id` varchar(100) NOT NULL;

-- 5. Sync migrations table
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES 
(1,'2026_01_17_065914_update_users_table_for_laravel_auth',1),
(2,'2026_01_17_071659_create_sessions_table',1),
(3,'2026_01_17_073317_add_last_login_at_to_users_table',2),
(4,'2026_01_17_080205_create_cache_table',3),
(6,'2026_01_17_091808_create_shopify_shops_table',4),
(7,'2026_01_17_091812_create_user_shop_accesses_table',5),
(8,'2026_01_17_091816_remove_deprecated_user_fields_and_add_is_admin',5),
(9,'2026_01_17_091820_add_shop_id_to_offers_table',5)
ON DUPLICATE KEY UPDATE migration=VALUES(migration), batch=VALUES(batch);

SET FOREIGN_KEY_CHECKS = 1;
