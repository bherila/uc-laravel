# UC Laravel

A multi-tenant Laravel-based web application for managing Underground Cellar wine offers and Shopify order processing across multiple stores. The application provides offer management, manifest allocation, profitability analysis, and Shopify integration with granular user access control.

## Features

- **Multi-Tenant Architecture**: Support for multiple Shopify stores with per-store API credentials
- **User Access Control**: Granular read-only or read-write access per user per store
- **Admin Management**: Dedicated admin pages for managing users and stores
- **Offer Management**: Create and manage wine offers with Shopify product integration
- **Manifest Allocation**: Add wine bottles to offers and track allocation to orders
- **Order Processing**: Automatic order manifest allocation via Shopify webhooks
- **Webhook Management**: Log, view, and re-run incoming Shopify webhooks with full payload inspection
- **Profitability Analysis**: Calculate margins, break-even scenarios, and sell-through projections
- **Metafield Sync**: Automatically update Shopify product metafields with offer data
- **Order Manifests**: View allocated orders and upgrade wine assignments

## Tech Stack

- **Backend**: Laravel 12 (PHP 8.3+)
- **Frontend**: React 19 with TypeScript
- **UI Components**: shadcn/ui + Radix UI primitives
- **Styling**: Tailwind CSS v4
- **Build**: Vite
- **Database**: MySQL
- **External API**: Shopify GraphQL Admin API

## Getting Started

### Prerequisites

- PHP 8.3 or higher
- Composer
- Node.js 18+ and pnpm
- MySQL

### Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd uc-laravel
   ```

2. **Install dependencies**
   ```bash
   composer install
   pnpm install
   ```

3. **Configure environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   
   Update `.env` with your database credentials. Note: Shopify API credentials are now stored per-store in the database, not in environment variables.

4. **Run migrations**
   ```bash
   php artisan migrate
   ```

5. **Build assets**
   ```bash
   pnpm run build
   ```

### Development

Run the development server:
```bash
# Using composer script (recommended)
composer dev

# Or run separately:
php artisan serve    # Laravel server
pnpm run dev         # Vite dev server
```

### Testing

```bash
# PHP tests
composer test

# TypeScript/Jest tests
pnpm test
```

## Project Structure

```
app/
├── Http/
│   ├── Controllers/            # API and web controllers
│   │   ├── AdminController.php     # User and store admin management
│   │   ├── ShopController.php      # Shop listing and dashboard
│   │   ├── OfferController.php     # Offer CRUD (shop-scoped)
│   │   ├── OfferManifestController.php
│   │   ├── ShopifyController.php   # Product data and inventory (shop-scoped)
│   │   ├── ShopifyWebhookController.php
│   │   └── WebhookController.php
│   └── Middleware/
│       ├── EnsureAdmin.php         # Admin access enforcement
│       └── EnsureShopAccess.php    # Shop access with read/write levels
├── Models/
│   ├── User.php                # User with admin status and shop access
│   ├── ShopifyShop.php         # Shopify store with API credentials
│   ├── UserShopAccess.php      # User-shop access pivot
│   ├── Offer.php               # Shop-scoped offers
│   ├── OfferManifest.php
│   └── OrderToVariant.php
└── Services/
    ├── Offer/
    │   ├── OfferService.php        # Shop-scoped offer operations
    │   └── OfferManifestService.php
    └── Shopify/
        ├── ShopifyClient.php       # Shop-specific API client
        ├── ShopifyOrderService.php
        ├── ShopifyProductService.php
        └── ShopifyOrderProcessingService.php

resources/js/
├── shops.tsx                   # Shop list page
├── shop-dashboard.tsx          # Shop dashboard
├── admin-users.tsx             # Admin user management
├── admin-user-detail.tsx       # Admin user edit with shop access
├── admin-stores.tsx            # Admin store management
├── admin-store-detail.tsx      # Admin store edit with API credentials
├── offers.tsx                  # Shop-scoped offer list
├── offer-new.tsx               # Create new offer (shop-scoped)
├── offer-detail.tsx            # Offer detail with manifests
├── offer-add-manifest.tsx      # Add bottles to offer
├── offer-profitability.tsx     # Profitability analysis
├── offer-metafields.tsx        # Shopify metafield viewer
├── offer-manifests.tsx         # Order manifest viewer
└── components/
    └── ui/                     # shadcn/ui components
```

## API Endpoints

### Shops
- `GET /api/shops` - List accessible shops for current user

### Offers (shop-scoped)
- `GET /api/shops/{shop}/offers` - List all offers with Shopify product data
- `POST /api/shops/{shop}/offers` - Create new offer
- `GET /api/shops/{shop}/offers/{id}` - Get offer details (add `?detail=1` for manifests)
- `DELETE /api/shops/{shop}/offers/{id}` - Delete offer and unassigned manifests
- `GET /api/shops/{shop}/offers/{id}/metafields` - Update and get Shopify metafields
- `GET /api/shops/{shop}/offers/{id}/orders` - Get orders for an offer

### Manifests (shop-scoped)
- `GET /api/shops/{shop}/offers/{id}/manifests` - Get manifest summary
- `PUT /api/shops/{shop}/offers/{id}/manifests` - Update manifest quantities

### Shopify (shop-scoped)
- `GET /api/shops/{shop}/shopify/products?type=deal|manifest-item` - Get products by type
- `POST /api/shops/{shop}/shopify/product-data` - Get product data by variant IDs
- `POST /api/shops/{shop}/shopify/set-inventory` - Set inventory quantity
- `POST /api/shopify/webhook` - Order webhook endpoint (uses X-Shopify-Shop-Domain header)

### Admin (requires admin access)
- `GET /api/admin/users` - List all users
- `POST /api/admin/users` - Create user
- `GET /api/admin/users/{id}` - Get user details with shop access
- `PUT /api/admin/users/{id}` - Update user
- `DELETE /api/admin/users/{id}` - Delete user
- `PUT /api/admin/users/{id}/shop-accesses` - Update user shop access
- `GET /api/admin/stores` - List all stores
- `POST /api/admin/stores` - Create store
- `GET /api/admin/stores/{id}` - Get store details
- `PUT /api/admin/stores/{id}` - Update store
- `DELETE /api/admin/stores/{id}` - Delete store
- `GET /api/admin/webhooks` - List webhooks
- `GET /api/admin/webhooks/{id}` - Get webhook details
- `POST /api/admin/webhooks/{id}/rerun` - Re-run webhook

## Web Routes

### Public
- `/` - Home page
- `/login` - Authentication

### Shops (requires auth)
- `/shops` - Shop list (shows accessible stores)
- `/shop/{id}` - Shop dashboard

### Offers (requires shop access)
- `/shop/{id}/offers` - Offer list
- `/shop/{id}/offers/new` - Create new offer
- `/shop/{id}/offers/{offerId}` - Offer detail page
- `/shop/{id}/offers/{offerId}/add-manifest` - Add bottles to offer
- `/shop/{id}/offers/{offerId}/profitability` - View profitability analysis
- `/shop/{id}/offers/{offerId}/metafields` - View/update Shopify metafields
- `/shop/{id}/offers/{offerId}/shopify_manifests` - View order allocations

### Admin (requires admin access)
- `/admin/users` - User management
- `/admin/users/{id}` - User detail with shop access editing
- `/admin/stores/{id}` - Store detail with API credentials
- `/admin/webhooks` - Webhook log list
- `/admin/webhooks/{id}` - Webhook detail and re-run

## Shopify Integration

The application integrates with Shopify for:

1. **Product Data**: Fetches deal and manifest-item products tagged in Shopify
2. **Inventory Management**: Sets inventory quantities for offer SKUs
3. **Order Webhooks**: Processes `orders/create` webhooks to allocate manifests
4. **Metafield Sync**: Updates product metafields with offer configuration

### Webhook Setup

Configure a webhook in Shopify Admin for each store:
- **Webhook URL**: `https://your-domain.com/api/shopify/webhook`
- **Event**: `orders/create`
- **Format**: JSON

The webhook handler identifies the store using the `X-Shopify-Shop-Domain` header and verifies the HMAC signature using the store-specific webhook secret.

## Authentication & Authorization

### Authentication
The application uses Laravel's built-in session-based authentication. Protected routes redirect to `/login` with the intended URL preserved for post-login redirect.

### Authorization Levels

1. **Admin Access** (`is_admin = true` or user ID 1)
   - Full access to all stores and offers
   - Access to `/admin/*` routes for user and store management
   - Can create, edit, and delete users and stores

2. **Read-Write Shop Access**
   - Full CRUD operations on offers within assigned stores
   - Can add/modify manifests and sync inventory

3. **Read-Only Shop Access**
   - View-only access to offers and manifests within assigned stores
   - Cannot create, modify, or delete data

### Middleware
- `admin` - Requires admin status, returns 403 if not admin
- `shop.access:read` - Requires at least read-only access to the shop
- `shop.access:write` - Requires read-write access to the shop
