# UC Laravel Database Schema

## Overview

UC Laravel is a multi-tenant application for managing wine offers with Shopify integration. The system supports multiple Shopify stores with granular user access control.

## Entity Relationship Diagram

```
┌─────────────────┐
│      users      │
│─────────────────│
│ id (PK)         │
│ email           │
│ password        │
│ alias           │
│ is_admin        │
│ last_login_at   │
└────────┬────────┘
         │
         │ many:many via user_shop_accesses
         ▼
┌────────────────────────┐      ┌─────────────────────┐
│  user_shop_accesses    │      │   shopify_shops     │
│────────────────────────│      │─────────────────────│
│ id (PK)                │      │ id (PK)             │
│ user_id (FK)           │◄────►│ name                │
│ shopify_shop_id (FK)   │      │ shop_domain         │
│ access_level           │      │ admin_api_token     │
└────────────────────────┘      │ webhook_secret      │
                                └──────────┬──────────┘
                                           │
                                           │ 1:many
                                           ▼
                                ┌─────────────────────┐
                                │      v3_offer       │
                                │─────────────────────│
                                │ offer_id (PK)       │
                                │ shop_id (FK)        │
                                │ offer_name          │
                                │ offer_variant_id    │
                                │ offer_product_name  │
                                └──────────┬──────────┘
                                           │
                                           │ 1:many
                                           ▼
                                ┌─────────────────────┐
                                │  v3_offer_manifest  │
                                │─────────────────────│
                                │ m_id (PK)           │
                                │ offer_id (FK)       │
                                │ mf_variant          │
                                │ assignee_id         │
                                │ assignment_ordering │
                                └─────────────────────┘
```

## Core Tables

### users

Stores application users with authentication and admin status.

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| id | bigint (PK) | No | Auto-increment primary key |
| email | varchar(50) | No | Unique email address |
| email_verified_at | timestamp | Yes | Email verification timestamp |
| password | varchar(100) | Yes | Hashed password |
| alias | varchar(50) | Yes | Display name |
| is_admin | boolean | No | Admin access flag (default: false) |
| last_login_at | timestamp | Yes | Last login timestamp |
| remember_token | varchar(100) | Yes | Laravel remember token |
| created_at | timestamp | Yes | Creation timestamp |
| updated_at | timestamp | Yes | Update timestamp |

**Indexes:**
- Primary key on `id`
- Unique constraint on `email`

**Notes:**
- User ID 1 is always treated as admin regardless of `is_admin` flag
- Admin users can access `/admin/*` routes for user and store management

### shopify_shops

Stores Shopify store configurations with API credentials.

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| id | bigint unsigned (PK) | No | Auto-increment primary key |
| name | varchar(255) | No | Display name for the store |
| shop_domain | varchar(255) | No | Shopify domain (e.g., mystore.myshopify.com) |
| app_name | varchar(1024) | Yes | Shopify app name |
| admin_api_token | varchar(1024) | Yes | Shopify Admin API token |
| api_version | varchar(1024) | No | API version (default: 2025-01) |
| api_key | varchar(1024) | Yes | Shopify API key |
| api_secret_key | varchar(1024) | Yes | Shopify API secret key |
| webhook_version | varchar(1024) | No | Webhook API version (default: 2025-01) |
| webhook_secret | varchar(1024) | Yes | HMAC secret for webhook verification |
| is_active | boolean | No | Whether shop is active (default: true) |
| created_at | timestamp | Yes | Creation timestamp |
| updated_at | timestamp | Yes | Update timestamp |

**Indexes:**
- Primary key on `id`
- Unique constraint on `shop_domain`

**Notes:**
- Sensitive fields (`admin_api_token`, `api_key`, `api_secret_key`, `webhook_secret`) are hidden from JSON serialization
- `shop_domain` is used to match incoming webhooks via X-Shopify-Shop-Domain header

### user_shop_accesses

Pivot table linking users to Shopify shops with access levels.

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| id | bigint unsigned (PK) | No | Auto-increment primary key |
| user_id | bigint | No | FK to users.id |
| shopify_shop_id | bigint unsigned | No | FK to shopify_shops.id |
| access_level | enum | No | 'read-only' or 'read-write' |
| created_at | timestamp | Yes | Creation timestamp |
| updated_at | timestamp | Yes | Update timestamp |

**Indexes:**
- Primary key on `id`
- Unique constraint on `(user_id, shopify_shop_id)`

**Foreign Keys:**
- `user_id` references `users.id` (ON DELETE CASCADE)
- `shopify_shop_id` references `shopify_shops.id` (ON DELETE CASCADE)

**Access Levels:**
- `read-only`: Can view offers and manifests but cannot modify
- `read-write`: Full access to create, edit, and delete offers/manifests

### v3_offer

Stores wine offer definitions linked to Shopify product variants.

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| offer_id | int (PK) | No | Auto-increment primary key |
| shop_id | bigint unsigned | Yes | FK to shopify_shops.id |
| offer_name | varchar(100) | No | Unique offer name |
| offer_variant_id | varchar(100) | No | Shopify variant ID (GID format) |
| offer_product_name | varchar(200) | No | Product display name |

**Indexes:**
- Primary key on `offer_id`
- Unique constraint on `offer_name`
- Unique constraint on `offer_variant_id`

**Foreign Keys:**
- `shop_id` references `shopify_shops.id` (ON DELETE SET NULL)

### v3_offer_manifest

Stores individual bottle allocations within offers.

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| m_id | bigint (PK) | No | Auto-increment primary key |
| offer_id | bigint | No | FK to v3_offer.offer_id |
| mf_variant | varchar(50) | No | Shopify variant ID for the bottle |
| assignee_id | varchar(50) | Yes | Shopify order ID when assigned |
| assignment_ordering | float | No | Priority order for allocation |

**Indexes:**
- Primary key on `m_id`
- Index on `(offer_id, assignment_ordering)` for efficient allocation queries

**Notes:**
- `assignee_id` is NULL for unallocated bottles
- Lower `assignment_ordering` values get allocated first
- Multiple manifest rows with same `mf_variant` represent multiple bottles

### v3_order_to_variant

Links Shopify orders to variants for webhook processing.

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| order_id | varchar(100) | No | Shopify order ID |
| variant_id | varchar(100) | No | Shopify variant ID |
| offer_id | int | Yes | FK to v3_offer.offer_id |

**Indexes:**
- Unique constraint on `(variant_id, order_id)`

**Notes:**
- Created when orders are processed via webhook
- Used to track which variants were purchased in each order

### v3_audit_log

Audit trail for system events.

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| id | bigint (PK) | No | Auto-increment primary key |
| event_ts | timestamp | No | Event timestamp (default: now) |
| event_name | varchar(50) | No | Event type identifier |
| event_ext | mediumtext | Yes | JSON event details |
| event_userid | bigint | Yes | User who triggered event |
| offer_id | int | Yes | Related offer ID |
| order_id | bigint unsigned | Yes | Related Shopify order ID |
| time_taken_ms | int | Yes | Operation duration in ms |

## Supporting Tables

### sessions

Laravel session storage for authenticated users.

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| id | varchar(255) (PK) | No | Session ID |
| user_id | bigint unsigned | Yes | FK to users.id |
| ip_address | varchar(45) | Yes | Client IP |
| user_agent | text | Yes | Browser user agent |
| payload | longtext | No | Serialized session data |
| last_activity | int | No | Unix timestamp |

### order_lock

Prevents concurrent processing of the same order.

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| order_id | varchar(100) (PK) | No | Shopify order ID |
| locked_at | datetime | No | Lock acquisition time |

### shopify_product_variant

Cache of Shopify product variant data.

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| variantId | varchar(191) (PK) | No | Shopify variant GID |
| productId | varchar(191) | No | Shopify product GID |
| productName | varchar(191) | No | Product title |
| variantName | varchar(191) | No | Variant title |
| variantPrice | varchar(191) | Yes | Price |
| variantCompareAtPrice | varchar(191) | Yes | Compare-at price |
| variantInventoryQuantity | int | No | Current inventory |
| variantSku | varchar(191) | No | SKU |
| variantWeight | varchar(191) | Yes | Weight |

## Legacy/Reference Tables

The database also contains several legacy tables used for historical data and analytics:

- `item_detail` - Wine product catalog
- `item_sku` - SKU-level inventory and pricing
- `user_list` - Customer data
- `order_list` - Historical orders
- `computed_buyer_varietals` - Aggregated buyer preferences
- `customer_list_*` - Customer export snapshots
- `member_list_export_*` - Member export snapshots
- `new_customer_data_*` - New customer data imports
- `old_order_data_*` - Historical order data

## Access Control Model

### Admin Access
- Users with `is_admin = true` or `id = 1` have admin access
- Admins can access `/admin/users` and `/admin/stores` routes
- Admins can manage all users and shops

### Shop Access
- Regular users must have an entry in `user_shop_accesses` to access a shop
- `read-only` access allows viewing offers and manifests
- `read-write` access allows full CRUD operations
- Shop access is enforced by `EnsureShopAccess` middleware

### Route Middleware
- `admin` - Requires admin status
- `shop.access:read` - Requires at least read-only access to shop
- `shop.access:write` - Requires read-write access to shop
