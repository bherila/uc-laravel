/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `2023_05_31_inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `2023_05_31_inventory` (
  `sku` varchar(19) NOT NULL,
  `description` varchar(121) DEFAULT NULL,
  `units_on_hand` int(11) DEFAULT NULL,
  `cost_basis_unit` decimal(6,2) DEFAULT NULL,
  `srp_unit` decimal(7,2) DEFAULT NULL,
  PRIMARY KEY (`sku`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `20251106_deduped_new_variant_ids`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `20251106_deduped_new_variant_ids` (
  `COL 1` varchar(13) DEFAULT NULL,
  `COL 2` varchar(14) DEFAULT NULL,
  `COL 3` varchar(18) DEFAULT NULL,
  `COL 4` varchar(7) DEFAULT NULL,
  `COL 5` varchar(58) DEFAULT NULL,
  `COL 6` varchar(13) DEFAULT NULL,
  `COL 7` varchar(168) DEFAULT NULL,
  `COL 8` varchar(13) DEFAULT NULL,
  `COL 9` varchar(103) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `20251106_v3_offer_manifest`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `20251106_v3_offer_manifest` (
  `m_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `offer_id` bigint(20) NOT NULL,
  `mf_variant` varchar(50) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `assignee_id` varchar(50) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `assignment_ordering` float NOT NULL,
  PRIMARY KEY (`m_id`),
  KEY `manifest_v2_offer_id_assign_order_index` (`offer_id`,`assignment_ordering`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `combine_operation_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `combine_operation_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `combine_operation_id` bigint(20) unsigned NOT NULL,
  `event` text DEFAULT NULL,
  `time_taken_ms` int(11) DEFAULT NULL,
  `shopify_request` text DEFAULT NULL,
  `shopify_response` text DEFAULT NULL,
  `shopify_response_code` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `combine_operation_logs_combine_operation_id_foreign` (`combine_operation_id`),
  CONSTRAINT `combine_operation_logs_combine_operation_id_foreign` FOREIGN KEY (`combine_operation_id`) REFERENCES `combine_operations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `combine_operations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `combine_operations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `audit_log_id` bigint(20) unsigned DEFAULT NULL,
  `webhook_id` bigint(20) unsigned DEFAULT NULL,
  `shop_id` bigint(20) unsigned DEFAULT NULL,
  `order_id` varchar(100) NOT NULL,
  `order_id_numeric` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `original_shipping_method` varchar(255) DEFAULT NULL,
  `fulfillment_orders_before` int(11) DEFAULT NULL,
  `fulfillment_orders_after` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `combine_operations_order_id_index` (`order_id`),
  KEY `combine_operations_shop_id_index` (`shop_id`),
  KEY `combine_operations_audit_log_id_index` (`audit_log_id`),
  KEY `combine_operations_webhook_id_index` (`webhook_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `computed_buyer_varietals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `computed_buyer_varietals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `winner_guid` char(36) NOT NULL,
  `cola_varietal` varchar(50) NOT NULL,
  `total_paid` decimal(19,5) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `cola_varietal` (`cola_varietal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customer_list_july_2023`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_list_july_2023` (
  `customer_name` varchar(34) DEFAULT NULL,
  `email` varchar(58) NOT NULL,
  `orders` int(11) DEFAULT NULL,
  `ltv` decimal(8,2) DEFAULT NULL,
  `first_order` varchar(16) DEFAULT NULL,
  `last_order` varchar(16) DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `item_detail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `item_detail` (
  `itemdetail_guid` varchar(36) NOT NULL,
  `cola_id` varchar(50) DEFAULT NULL,
  `cola_name` varchar(200) DEFAULT NULL,
  `cola_region` varchar(200) DEFAULT NULL,
  `cola_appellation` varchar(200) DEFAULT NULL,
  `cola_varietal` varchar(200) DEFAULT NULL,
  `cola_vintage` char(4) DEFAULT NULL,
  `cola_abv` float DEFAULT NULL,
  `about_wine` longtext DEFAULT NULL,
  `tasting_notes` longtext DEFAULT NULL,
  `winemaker_notes` longtext DEFAULT NULL,
  `label_img_url` varchar(512) DEFAULT NULL,
  `bottle_img_url` varchar(512) DEFAULT NULL,
  `retail_price` float DEFAULT NULL,
  `winery_id` char(36) DEFAULT NULL,
  `url_key` varchar(256) DEFAULT NULL,
  `brand` varchar(256) DEFAULT NULL,
  `country_code` char(2) DEFAULT NULL,
  `upc` mediumtext DEFAULT NULL,
  `is_wine` char(1) DEFAULT NULL,
  `is_beer` char(1) DEFAULT NULL,
  `is_liquor` char(1) DEFAULT NULL,
  `is_sparkling` char(1) DEFAULT NULL,
  `is_cult` char(1) DEFAULT NULL,
  `is_small_production` char(1) DEFAULT NULL,
  `ct_wine_id` int(11) DEFAULT NULL,
  `ct_producer_id` int(11) DEFAULT NULL,
  `ct_likes` int(11) DEFAULT NULL,
  `ct_tasting_notes` int(11) DEFAULT NULL,
  `ct_review` int(11) DEFAULT NULL,
  `ct_community_score` varchar(20) DEFAULT NULL,
  `ct_qty` int(11) DEFAULT NULL,
  `wine_vineyard` varchar(50) DEFAULT NULL,
  `wine_web_url` varchar(512) DEFAULT NULL,
  `wine_drink_start` varchar(20) DEFAULT NULL,
  `wine_drink_end` varchar(20) DEFAULT NULL,
  `wine_producer_uuid` varchar(36) DEFAULT NULL,
  `redirect_to` text DEFAULT NULL,
  `item_tsv` mediumtext DEFAULT NULL,
  `wine_ml` text DEFAULT NULL,
  `cola_fanciful_name` mediumtext DEFAULT NULL,
  `wd_varietal` varchar(50) DEFAULT NULL,
  `wd_region` varchar(50) DEFAULT NULL,
  `is_blend` char(1) DEFAULT NULL,
  `price_range` varchar(50) DEFAULT NULL,
  `item_lbs` varchar(20) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `blur_bottle_img` varchar(256) DEFAULT NULL,
  `blur_label_img` varchar(256) DEFAULT NULL,
  PRIMARY KEY (`itemdetail_guid`),
  KEY `retail_price` (`retail_price`),
  KEY `cola_varietal` (`cola_varietal`),
  KEY `cola_appellation` (`cola_appellation`),
  KEY `cola_region` (`cola_region`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `item_sku`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `item_sku` (
  `sku` varchar(255) NOT NULL,
  `srp` decimal(10,2) DEFAULT NULL,
  `is_autographed` tinyint(1) DEFAULT NULL,
  `is_taxable` tinyint(1) DEFAULT NULL,
  `is_counted_for_shipment` tinyint(1) DEFAULT NULL,
  `drink_by_date` timestamp NULL DEFAULT NULL,
  `sku_itemdetail_guid` text DEFAULT NULL,
  `index` int(11) DEFAULT NULL,
  `last_order_date` datetime DEFAULT NULL,
  `last_restock` datetime DEFAULT NULL,
  `last_stock_update` datetime DEFAULT NULL,
  `last_stock_qty` int(11) NOT NULL DEFAULT 0,
  `next_delivery_date` timestamp NULL DEFAULT NULL,
  `last_count_owed` int(11) NOT NULL DEFAULT 0,
  `x_friendly_name` varchar(255) DEFAULT NULL,
  `scramble_letters` varchar(255) DEFAULT NULL,
  `scramble_qty_allowed` int(11) NOT NULL DEFAULT 0,
  `sku_allowed_states` varchar(255) DEFAULT NULL,
  `comment` mediumtext DEFAULT NULL,
  `sku_tsv` mediumtext DEFAULT NULL,
  `sku_cogs_unit` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_pallet_program` tinyint(1) NOT NULL DEFAULT 0,
  `is_deprecated` tinyint(1) DEFAULT NULL,
  `sku_varietal` text DEFAULT NULL,
  `sku_region` text DEFAULT NULL,
  `last_count_shipped` int(11) NOT NULL DEFAULT 0,
  `is_in_wd` tinyint(1) NOT NULL DEFAULT 0,
  `sku_was_swap` tinyint(1) NOT NULL DEFAULT 0,
  `sku_sort` int(11) NOT NULL DEFAULT 0,
  `sku_preswap` mediumtext DEFAULT NULL,
  `sku_postswap` mediumtext DEFAULT NULL,
  `sku_qty_reserved` int(11) DEFAULT 0,
  `sku_cogs_is_estimated` tinyint(1) NOT NULL DEFAULT 0,
  `sku_taxset_id` text DEFAULT NULL,
  `qty_offsite` int(11) NOT NULL DEFAULT 0,
  `sku_external_id` varchar(32) DEFAULT NULL,
  `sku_fq_lo` int(11) NOT NULL DEFAULT 0,
  `sku_fq_hi` int(11) NOT NULL DEFAULT 35,
  `sku_velocity` decimal(10,2) NOT NULL DEFAULT 0.00,
  `last_vip_qty` int(11) NOT NULL DEFAULT 0,
  `last_open_xfer_qty` int(11) NOT NULL DEFAULT 0,
  `sku_is_dropship` tinyint(1) NOT NULL DEFAULT 0,
  `sku_ship_alone` tinyint(1) NOT NULL DEFAULT 0,
  `sku_supplier_guid` text DEFAULT NULL,
  `next_stock_update` datetime DEFAULT NULL,
  `sku_exclude_metrics` tinyint(1) DEFAULT 0,
  `netsuite_synced` datetime DEFAULT NULL,
  `category` varchar(30) DEFAULT NULL,
  `country_code` varchar(255) DEFAULT NULL,
  `unlimited_allocation_until` datetime DEFAULT NULL,
  `avg_purchase_price` decimal(10,2) DEFAULT NULL,
  `last_purchase_price` decimal(10,2) DEFAULT NULL,
  `dont_buy_after` datetime DEFAULT NULL,
  `pack_size` int(11) DEFAULT NULL,
  PRIMARY KEY (`sku`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `member_list_export_2023_07_06`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `member_list_export_2023_07_06` (
  `Email` varchar(67) DEFAULT NULL,
  `Klaviyo ID` varchar(26) NOT NULL,
  `First Name` varchar(52) DEFAULT NULL,
  `Last Name` varchar(55) DEFAULT NULL,
  `Phone Number` varchar(11) DEFAULT NULL,
  `Address` varchar(45) DEFAULT NULL,
  `Address 2` varchar(10) DEFAULT NULL,
  `City` varchar(33) DEFAULT NULL,
  `State / Region` varchar(35) DEFAULT NULL,
  `Country` varchar(24) DEFAULT NULL,
  `Zip Code` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`Klaviyo ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `new_customer_data_after_bk_from_lcc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `new_customer_data_after_bk_from_lcc` (
  `Customer ID` bigint(13) NOT NULL AUTO_INCREMENT,
  `Customer Created At` varchar(10) DEFAULT NULL,
  `First Order Date` varchar(10) DEFAULT NULL,
  `Email` varchar(44) DEFAULT NULL,
  `First Name` varchar(25) DEFAULT NULL,
  `Last Name` varchar(21) DEFAULT NULL,
  `Billing Address First Name` varchar(32) DEFAULT NULL,
  `Billing Address Last Name` varchar(44) DEFAULT NULL,
  `Billing Address Company` varchar(60) DEFAULT NULL,
  `Billing Address Address 1` varchar(81) DEFAULT NULL,
  `Billing Address Address 2` varchar(60) DEFAULT NULL,
  `Billing Address City` varchar(38) DEFAULT NULL,
  `Billing Address State` varchar(2) DEFAULT NULL,
  `Billing Address Postcode` varchar(10) DEFAULT NULL,
  `Billing Address Country` varchar(2) DEFAULT NULL,
  `Billing Address Email` varchar(44) DEFAULT NULL,
  `Billing Address Phone` varchar(16) DEFAULT NULL,
  `Shipping Address First Name` varchar(10) DEFAULT NULL,
  `Shipping Address Last Name` varchar(10) DEFAULT NULL,
  `Shipping Address Company` varchar(10) DEFAULT NULL,
  `Shipping Address Address 1` varchar(10) DEFAULT NULL,
  `Shipping Address Address 2` varchar(10) DEFAULT NULL,
  `Shipping Address City` varchar(10) DEFAULT NULL,
  `Shipping Address State` varchar(10) DEFAULT NULL,
  `Shipping Address Postcode` varchar(10) DEFAULT NULL,
  `Shipping Address Country` varchar(10) DEFAULT NULL,
  `Total Spent` decimal(7,2) DEFAULT NULL,
  `Order Count` int(2) DEFAULT NULL,
  `Item Count` int(5) DEFAULT NULL,
  `Last Order Date` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`Customer ID`),
  KEY `Email` (`Email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `old_order_data_500k_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `old_order_data_500k_orders` (
  `order_guid` char(36) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `order_user` char(36) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `order_qty` int(3) DEFAULT NULL,
  `order_total_price` decimal(7,2) DEFAULT NULL,
  `order_timestamp` datetime DEFAULT NULL,
  `order_offer_id` char(36) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `order_user_nth` varchar(10) DEFAULT NULL,
  `order_auth_date` datetime DEFAULT NULL,
  `order_transaction_id` bigint(20) DEFAULT NULL,
  `order_yymm_pst` varchar(7) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `order_type` varchar(16) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `offer_guid` char(36) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `offer_title` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `offer_price` varchar(4) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `offer_meta_title` varchar(132) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `offer_subtitle` varchar(243) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `offer_primary_varietal` varchar(25) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  PRIMARY KEY (`order_guid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `order_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_list` (
  `order_guid` varchar(36) NOT NULL,
  `order_sku_list` mediumtext CHARACTER SET utf8mb3 COLLATE utf8mb3_bin DEFAULT NULL,
  `order_user` varchar(36) DEFAULT NULL,
  `order_billing_address` varchar(36) DEFAULT NULL,
  `order_qty` int(3) DEFAULT NULL,
  `order_total_price` decimal(8,2) DEFAULT NULL,
  `order_discount` decimal(8,2) DEFAULT NULL,
  `order_credit_discount` decimal(8,2) DEFAULT NULL,
  `order_tax` decimal(8,2) DEFAULT NULL,
  `order_timestamp` varchar(26) DEFAULT NULL,
  `order_status` varchar(14) DEFAULT NULL,
  `order_payment_status` varchar(15) DEFAULT NULL,
  `order_offer_id` varchar(36) DEFAULT NULL,
  `order_utm_source` text DEFAULT NULL,
  `order_utm_medium` text DEFAULT NULL,
  `order_utm_campaign` text DEFAULT NULL,
  `order_user_nth` int(4) DEFAULT NULL,
  `order_auth_code` varchar(12) DEFAULT NULL,
  `order_auth_date` varchar(26) DEFAULT NULL,
  `order_billing_instrument` varchar(36) DEFAULT NULL,
  `order_transaction_id` varchar(12) DEFAULT NULL,
  `x_user_email` varchar(44) DEFAULT NULL,
  `order_unit_price` decimal(8,2) DEFAULT NULL,
  `order_promo_code` varchar(36) DEFAULT NULL,
  `x_order_is_authorized_or_captured` enum('false','true') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `cohort_mth` varchar(2) DEFAULT NULL,
  `order_reveal_date` varchar(26) DEFAULT NULL,
  `cohort_fp_mth` decimal(8,2) DEFAULT NULL,
  `is_test_order` varchar(5) DEFAULT NULL,
  `order_rejected_dt` varchar(26) DEFAULT NULL,
  `order_upgraded_value` decimal(8,2) DEFAULT NULL,
  `order_allocated_cogs` decimal(8,2) DEFAULT NULL,
  `order_cohort_fpdate` varchar(26) DEFAULT NULL,
  `order_cc_fee` decimal(8,2) DEFAULT NULL,
  `order_refund_transaction_id` varchar(27) DEFAULT NULL,
  `order_original_mf` varchar(10) DEFAULT NULL,
  `order_yymm_pst` varchar(7) DEFAULT NULL,
  `order_mc_eid` varchar(12) DEFAULT NULL,
  `order_mc_cid` varchar(10) DEFAULT NULL,
  `order_subscription_id` varchar(10) DEFAULT NULL,
  `order_is_void` enum('false','true') DEFAULT NULL,
  `order_cash_in` decimal(8,2) DEFAULT NULL,
  `order_disc_c` decimal(8,2) DEFAULT NULL,
  `order_disc_f` decimal(8,2) DEFAULT NULL,
  `order_disc_s` decimal(8,2) DEFAULT NULL,
  `order_disc_r` decimal(8,2) DEFAULT NULL,
  `order_disc_t` decimal(8,2) DEFAULT NULL,
  `order_disc_m` decimal(8,2) DEFAULT NULL,
  `order_disc_g` decimal(8,2) DEFAULT NULL,
  `order_disc_other` decimal(8,2) DEFAULT NULL,
  `order_ship_revenue` decimal(8,2) DEFAULT NULL,
  `order_previous_order` varchar(36) DEFAULT NULL,
  `utm_content` varchar(112) DEFAULT NULL,
  `utm_term` varchar(70) DEFAULT NULL,
  `netsuite_synced` varchar(33) DEFAULT NULL,
  `payment_intent_id` varchar(27) DEFAULT NULL,
  `utm_device` varchar(10) DEFAULT NULL,
  `utm_placement` varchar(25) DEFAULT NULL,
  `utm_site` varchar(39) DEFAULT NULL,
  `order_type` enum('TastingRoom','SignupGift','UCAdminSingleSKU','Online-Web','ScrambleLetter','UCGrant','ScramblePrize','Offer','WineClub') DEFAULT NULL,
  PRIMARY KEY (`order_guid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `order_lock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_lock` (
  `order_id` varchar(100) NOT NULL,
  `locked_at` datetime NOT NULL,
  PRIMARY KEY (`order_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
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
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `shopify_product_variant`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shopify_product_variant` (
  `variantId` varchar(191) NOT NULL,
  `productId` varchar(191) NOT NULL,
  `productName` varchar(191) NOT NULL,
  `variantName` varchar(191) NOT NULL,
  `variantPrice` varchar(191) DEFAULT NULL,
  `variantCompareAtPrice` varchar(191) DEFAULT NULL,
  `variantInventoryQuantity` int(11) NOT NULL,
  `variantSku` varchar(191) NOT NULL,
  `variantWeight` varchar(191) DEFAULT NULL,
  PRIMARY KEY (`variantId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `shopify_shops`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shopify_shops` (
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
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_list` (
  `user_guid` char(36) NOT NULL,
  `user_birthday` varchar(23) DEFAULT NULL,
  `user_default_address` varchar(36) DEFAULT NULL,
  `user_email` varchar(58) DEFAULT NULL,
  `user_fname` varchar(25) DEFAULT NULL,
  `user_lname` varchar(26) DEFAULT NULL,
  `user_is21` varchar(1) DEFAULT NULL,
  `user_is_testaccount` varchar(1) DEFAULT NULL,
  `user_image_url` varchar(97) DEFAULT NULL,
  `user_url_profile` varchar(66) DEFAULT NULL,
  `user_name` varchar(32) DEFAULT NULL,
  `user_login_dt` varchar(26) DEFAULT NULL,
  `user_signup_dt` varchar(26) DEFAULT NULL,
  `user_last_purchase_dt` datetime DEFAULT NULL,
  `user_first_purchase_dt` datetime DEFAULT NULL,
  `user_referred_by_id` varchar(36) DEFAULT NULL,
  `user_referral_domain` varchar(10) DEFAULT NULL,
  `session_utm_source` varchar(73) DEFAULT NULL,
  `session_utm_campaign` varchar(99) DEFAULT NULL,
  `session_utm_medium` varchar(35) DEFAULT NULL,
  `x_life_credit` decimal(8,2) DEFAULT NULL,
  `x_total_qty` int(4) DEFAULT NULL,
  `x_life_spend` decimal(8,2) DEFAULT NULL,
  `x_life_discount` decimal(7,2) DEFAULT NULL,
  `x_acquisition_cost` decimal(10,2) DEFAULT NULL,
  `x_achievement_points` int(2) DEFAULT NULL,
  `user_is_private` varchar(1) DEFAULT NULL,
  `user_is_red_buyer` varchar(1) DEFAULT NULL,
  `user_is_white_buyer` varchar(1) DEFAULT NULL,
  `user_is_largeformat_buyer` varchar(1) DEFAULT NULL,
  `user_min_price` varchar(3) DEFAULT NULL,
  `user_max_price` varchar(3) DEFAULT NULL,
  `user_avg_price` varchar(8) DEFAULT NULL,
  `user_is_push` varchar(1) DEFAULT NULL,
  `user_outreach_dt` varchar(26) DEFAULT NULL,
  `ls_is_student` varchar(1) DEFAULT NULL,
  `ls_is_personal_email` varchar(1) DEFAULT NULL,
  `ls_grade` varchar(23) DEFAULT NULL,
  `ls_company_state_code` varchar(2) DEFAULT NULL,
  `ls_fname` varchar(10) DEFAULT NULL,
  `ls_lname` varchar(12) DEFAULT NULL,
  `ls_location_state` varchar(20) DEFAULT NULL,
  `ls_company_name` varchar(48) DEFAULT NULL,
  `ls_company_industry` varchar(32) DEFAULT NULL,
  `ls_company_country` varchar(2) DEFAULT NULL,
  `ls_company_emps` varchar(5) DEFAULT NULL,
  `ls_is_spam` varchar(1) DEFAULT NULL,
  `ls_customer_fit` varchar(9) DEFAULT NULL,
  `ls_customer_fit_ext` varchar(336) DEFAULT NULL,
  `x_order_count` int(3) DEFAULT NULL,
  `user_inactive_dt` varchar(26) DEFAULT NULL,
  `user_email_n` varchar(10) DEFAULT NULL,
  `user_note` varchar(91) DEFAULT NULL,
  `user_expiry` varchar(26) DEFAULT NULL,
  `user_last_ship_date` varchar(26) DEFAULT NULL,
  `x_cloud_value` decimal(7,2) DEFAULT NULL,
  `x_cloud_count` int(4) DEFAULT NULL,
  `user_is_vip` varchar(1) DEFAULT NULL,
  `user_signup_ym_pst` varchar(7) DEFAULT NULL,
  `suspended_at` varchar(29) DEFAULT NULL,
  `last_synced_at` varchar(29) DEFAULT NULL,
  `is_admin` varchar(1) DEFAULT NULL,
  `utm_content` varchar(85) DEFAULT NULL,
  `utm_term` varchar(72) DEFAULT NULL,
  `user_password` varchar(64) DEFAULT NULL,
  `stripe_customer_id` varchar(18) DEFAULT NULL,
  `google_id` varchar(21) DEFAULT NULL,
  `utm_device` varchar(10) DEFAULT NULL,
  `utm_placement` varchar(119) DEFAULT NULL,
  `utm_site` varchar(91) DEFAULT NULL,
  `holdout_num` varchar(3) DEFAULT NULL,
  PRIMARY KEY (`user_guid`),
  KEY `user_email` (`user_email`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_shop_accesses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_shop_accesses` (
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
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `email` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `pw` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `salt` bigint(20) NOT NULL DEFAULT 0,
  `alias` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `ax_maxmin` tinyint(1) NOT NULL DEFAULT 0,
  `ax_homes` tinyint(1) DEFAULT 0,
  `ax_tax` tinyint(1) NOT NULL DEFAULT 0,
  `ax_evdb` tinyint(1) DEFAULT 0,
  `ax_spgp` tinyint(1) NOT NULL DEFAULT 0,
  `ax_uc` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_pk` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `v3_audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `v3_audit_log` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `event_ts` timestamp NOT NULL DEFAULT current_timestamp(),
  `event_name` varchar(50) NOT NULL,
  `event_ext` mediumtext DEFAULT NULL,
  `event_userid` bigint(20) DEFAULT NULL,
  `offer_id` int(11) DEFAULT NULL,
  `order_id` bigint(20) unsigned DEFAULT NULL,
  `time_taken_ms` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `v3_offer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `v3_offer` (
  `offer_id` int(11) NOT NULL AUTO_INCREMENT,
  `shop_id` bigint(20) unsigned DEFAULT NULL,
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `offer_name` varchar(100) NOT NULL,
  `offer_variant_id` varchar(100) NOT NULL,
  `offer_product_name` varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY (`offer_id`),
  UNIQUE KEY `v3_offer_unique_name` (`offer_name`),
  UNIQUE KEY `v3_offer_pk` (`offer_variant_id`),
  KEY `v3_offer_shop_id_foreign` (`shop_id`),
  CONSTRAINT `v3_offer_shop_id_foreign` FOREIGN KEY (`shop_id`) REFERENCES `shopify_shops` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `v3_offer_manifest`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `v3_offer_manifest` (
  `m_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `offer_id` bigint(20) NOT NULL,
  `mf_variant` varchar(50) NOT NULL,
  `assignee_id` varchar(50) DEFAULT NULL,
  `assignment_ordering` float NOT NULL,
  `webhook_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`m_id`),
  KEY `manifest_v2_offer_id_assign_order_index` (`offer_id`,`assignment_ordering`),
  KEY `v3_offer_manifest_webhook_id_index` (`webhook_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `v3_offer_manifest_exp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `v3_offer_manifest_exp` (
  `m_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `offer_id` bigint(20) NOT NULL,
  `mf_variant` bigint(20) NOT NULL,
  `assignee_id` bigint(20) DEFAULT NULL,
  `assignment_ordering` float NOT NULL,
  PRIMARY KEY (`m_id`),
  KEY `manifest_v2_offer_id_assign_order_index` (`offer_id`,`assignment_ordering`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `v3_order_to_variant`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `v3_order_to_variant` (
  `order_id` varchar(100) NOT NULL,
  `variant_id` varchar(100) NOT NULL,
  `offer_id` int(11) DEFAULT NULL,
  UNIQUE KEY `v3_order_to_variant_variant_id_order_id_uindex` (`variant_id`,`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `v3_order_to_variant_exp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `v3_order_to_variant_exp` (
  `order_id` bigint(20) DEFAULT NULL,
  `variant_id` bigint(20) DEFAULT NULL,
  `offer_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `webhook_subs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `webhook_subs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `webhook_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `event` text DEFAULT NULL,
  `time_taken_ms` int(11) DEFAULT NULL,
  `shopify_request` text DEFAULT NULL,
  `shopify_response` text DEFAULT NULL,
  `shopify_response_code` int(11) DEFAULT NULL,
  `offer_id` bigint(20) unsigned DEFAULT NULL,
  `order_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `webhook_subs_webhook_id_foreign` (`webhook_id`),
  CONSTRAINT `webhook_subs_webhook_id_foreign` FOREIGN KEY (`webhook_id`) REFERENCES `webhooks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `webhooks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `webhooks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `rerun_of_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `payload` text DEFAULT NULL,
  `headers` text DEFAULT NULL,
  `shopify_topic` varchar(255) DEFAULT NULL,
  `shop_id` bigint(20) unsigned DEFAULT NULL,
  `is_force_repick` tinyint(1) NOT NULL DEFAULT 0,
  `valid_hmac` tinyint(1) DEFAULT NULL,
  `valid_shop_matched` tinyint(1) DEFAULT NULL,
  `error_ts` timestamp NULL DEFAULT NULL,
  `success_ts` timestamp NULL DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `webhooks_shopify_topic_index` (`shopify_topic`),
  KEY `webhooks_shop_id_foreign` (`shop_id`),
  CONSTRAINT `webhooks_shop_id_foreign` FOREIGN KEY (`shop_id`) REFERENCES `shopify_shops` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
--
-- WARNING: can't read the INFORMATION_SCHEMA.libraries table. It's most probably an old server 5.5.5-10.6.24-MariaDB.
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'2026_01_17_065914_update_users_table_for_laravel_auth',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'2026_01_17_071659_create_sessions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'2026_01_17_073317_add_last_login_at_to_users_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2026_01_17_080205_create_cache_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2026_01_17_091808_create_shopify_shops_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2026_01_17_091812_create_user_shop_accesses_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2026_01_17_091816_remove_deprecated_user_fields_and_add_is_admin',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2026_01_17_091820_add_shop_id_to_offers_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2026_01_20_040740_create_jobs_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2026_01_20_062828_create_webhooks_tables',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2026_01_20_065704_add_time_taken_ms_to_webhook_subs_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2026_01_20_080132_add_is_archived_to_v3_offer_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2026_01_20_090000_add_topic_and_shop_id_to_webhooks_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2026_01_30_051618_create_combine_operation_logs_table',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2026_01_30_053444_add_webhook_id_to_combine_operations_table',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2026_02_01_184209_add_webhook_id_to_v3_offer_manifest_table',13);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2026_02_01_184210_add_is_force_repick_to_webhooks_table',13);
