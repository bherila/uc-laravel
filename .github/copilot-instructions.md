# UC Laravel Copilot Instructions

## Architecture Overview

- **Backend**: Laravel 12 (PHP 8.3+) API with Blade shell templates
- **Frontend**: React 19 + TypeScript via Vite
- **UI**: shadcn/ui components with Radix UI primitives and Tailwind CSS v4
- **Database**: MySQL with Eloquent ORM
- **External**: Shopify GraphQL Admin API integration
- **Multi-Tenant**: Shop-based with per-store API credentials and user access control

## Multi-Tenant Architecture

The application supports multiple Shopify stores with granular user access:

### Access Control Model
- **Admin Users**: `is_admin = true` or user ID 1 - full access to all stores and admin pages
- **Shop Access**: Users are granted access to specific shops via `user_shop_accesses` table
  - `read-only`: View offers and manifests, no modifications
  - `read-write`: Full CRUD on offers and manifests

### Middleware
- `admin` - Requires admin status ([app/Http/Middleware/EnsureAdmin.php](app/Http/Middleware/EnsureAdmin.php))
- `shop.access:read` - At least read-only access to shop ([app/Http/Middleware/EnsureShopAccess.php](app/Http/Middleware/EnsureShopAccess.php))
- `shop.access:write` - Read-write access to shop

### Shop-Scoped API Pattern
All offer and Shopify API endpoints are scoped to a shop:
```php
// routes/api.php
Route::prefix('shops/{shop}')
    ->middleware(['auth', 'shop.access:read'])
    ->group(function () {
        Route::get('offers', [OfferController::class, 'index']);
        // ...
    });
```

### Creating Shop-Specific Services
Controllers create services with shop-specific configuration:
```php
// In OfferController
private function makeOfferService(ShopifyShop $shop): OfferService
{
    $client = new ShopifyClient($shop);
    return new OfferService(
        new ShopifyProductService($client),
        new ShopifyOrderService($client)
    );
}
```

## Project Pattern

Blade routes in [routes/web.php](routes/web.php) return minimal views that mount React roots with `data-*` props for passing server data to client. React entrypoints are configured in [vite.config.ts](vite.config.ts) with alias `@` → `resources/js`.

### Mounting Pattern Example

Blade template ([resources/views/shop/offers/detail.blade.php](resources/views/shop/offers/detail.blade.php)):
```blade
@extends('layouts.app')
@section('content')
<div id="offer-detail-root" 
     data-api-base="{{ url('/api') }}"
     data-shop-id="{{ $shopId }}"
     data-offer-id="{{ $offerId }}">
</div>
@endsection
@push('head')
@vite('resources/js/offer-detail.tsx')
@endpush
```

React entrypoint ([resources/js/offer-detail.tsx](resources/js/offer-detail.tsx)):
```tsx
const root = document.getElementById('offer-detail-root');
const shopId = root?.dataset.shopId;
const offerId = root?.dataset.offerId;
const apiBase = root?.dataset.apiBase || '/api';
// API calls use: `${apiBase}/shops/${shopId}/offers/${offerId}`
```

## Core Domain Models

All under [app/Models](app/Models):
- `ShopifyShop` - Shopify store with API credentials
- `User` - Application user with admin status
- `UserShopAccess` - Pivot linking users to shops with access level
- `Offer` - Wine offers with Shopify variant linkage (shop-scoped)
- `OfferManifest` - Individual bottle allocations to orders
- `OrderToVariant` - Links Shopify orders to variants for webhook processing

## Service Layer

### Offer Services ([app/Services/Offer](app/Services/Offer))
- `OfferService` - CRUD, detail views with manifests, metafield updates, order data
- `OfferManifestService` - Manifest quantity management, product data enrichment

### Shopify Services ([app/Services/Shopify](app/Services/Shopify))
- `ShopifyClient` - Base GraphQL client with shop-specific credentials
- `ShopifyProductService` - Product data, inventory, metafields
- `ShopifyOrderService` - Order queries, cancel, capture
- `ShopifyOrderProcessingService` - Full order processing with manifest allocation
- `ShopifyOrderEditService` - Order edit mutations (line items, discounts, shipping)
- `ShopifyFulfillmentService` - Fulfillment order operations

## API Surface

Defined in [routes/api.php](routes/api.php):

### Shops
- `GET /api/shops` - List accessible shops for current user

### Offers (shop-scoped)
- `GET/POST /api/shops/{shop}/offers` - List/create offers
- `GET/DELETE /api/shops/{shop}/offers/{id}` - Get/delete (use `?detail=1` for manifests)
- `GET /api/shops/{shop}/offers/{id}/metafields` - Update and return Shopify metafields
- `GET /api/shops/{shop}/offers/{id}/orders` - Get orders with manifest allocations

### Manifests (shop-scoped)
- `GET/PUT /api/shops/{shop}/offers/{id}/manifests` - Get summary / update quantities

### Shopify (shop-scoped)
- `GET /api/shops/{shop}/shopify/products?type=deal|manifest-item` - Get tagged products
- `POST /api/shops/{shop}/shopify/product-data` - Get product data by variant IDs
- `POST /api/shops/{shop}/shopify/set-inventory` - Set inventory quantity
- `POST /api/shopify/webhook` - Order webhook (HMAC verified, uses X-Shopify-Shop-Domain header)

### Admin
- `GET/POST /api/admin/users` - List/create users
- `GET/PUT/DELETE /api/admin/users/{id}` - User CRUD
- `PUT /api/admin/users/{id}/shop-accesses` - Update user shop access
- `GET/POST /api/admin/stores` - List/create stores
- `GET/PUT/DELETE /api/admin/stores/{id}` - Store CRUD
- `GET /api/admin/webhooks` - List webhooks
- `GET /api/admin/webhooks/{id}` - Get details
- `POST /api/admin/webhooks/{id}/rerun` - Re-run webhook

## Frontend Patterns

### Data Fetching
Use [resources/js/fetchWrapper.ts](resources/js/fetchWrapper.ts):
```typescript
const data = await fetchWrapper.get(`${apiBase}/offers`);
await fetchWrapper.post(`${apiBase}/offers`, { offer_name: '...', ... });
await fetchWrapper.put(`${apiBase}/offers/${id}/manifests`, { manifests: [...] });
await fetchWrapper.delete(`${apiBase}/offers/${id}`, {});
```

Includes CSRF meta header, `credentials: include`, JSON parsing with fallback.

### UI Components
All under [resources/js/components/ui](resources/js/components/ui):
- Use shadcn/ui components (Button, Table, Badge, Alert, Input, Label, Checkbox, Textarea, etc.)
- Money formatting via [resources/js/lib/currency.ts](resources/js/lib/currency.ts)

### Page Structure
Each page follows this pattern:
1. Mount element with data attributes
2. `createRoot` renders the TSX component
3. Component fetches data from API on mount
4. Loading states handled using `<Skeleton>` components from `resources/js/components/ui/skeleton.tsx` instead of raw text.
5. Container + MainTitle + content layout

## Key UI Flows

### Offers (Shop List) ([resources/js/shops.tsx](resources/js/shops.tsx))
- Entry point for most users (labeled "Offers" in navbar).
- Lists accessible shops with access level badges.
- Links to shop dashboard/offers.

### Admin User Management ([resources/js/admin-users.tsx](resources/js/admin-users.tsx), [resources/js/admin-user-detail.tsx](resources/js/admin-user-detail.tsx))
- Labeled "Users" in navbar.
- List users with create/delete actions.
- Edit user details and shop access assignments.

### Manage Shops (Admin Store Management) ([resources/js/admin-stores.tsx](resources/js/admin-stores.tsx), [resources/js/admin-store-detail.tsx](resources/js/admin-store-detail.tsx))
- Labeled "Manage Shops" in navbar.
- List stores with create/delete actions.
- Edit store details and API credentials.
- **Logic**: When the first store is created, any existing offers with `shop_id IS NULL` are automatically assigned to it.

### Webhook Management ([resources/js/admin-webhooks.tsx](resources/js/admin-webhooks.tsx), [resources/js/admin-webhook-detail.tsx](resources/js/admin-webhook-detail.tsx))
- Labeled "Webhooks" in navbar (needs to be added).
- Lists all incoming webhooks with status badges.
- Detail page shows payload, headers, and execution logs (webhook_sub events).
- Re-run functionality creates a new webhook record linked to the original.

## Shopify Performance & Caching

The `ShopifyProductService` implements a robust caching strategy to minimize API calls to Shopify:
- **Extended TTL**: Most product and variant data is cached for **1 hour**.
- **Individual Variant Caching**: Variants are cached by their specific ID hash, allowing for partial cache hits in bulk requests.
- **Proactive Invalidation**: Caches are automatically cleared when inventory is updated or metafields are written via the service.
- **Shop-Specific**: All caching is scoped to the specific Shopify shop credentials.

### Offer List ([resources/js/offers.tsx](resources/js/offers.tsx))
- Lists offers with Shopify product data
- Links to detail page, delete action

### Offer Detail ([resources/js/offer-detail.tsx](resources/js/offer-detail.tsx))
- Shows offer info, manifest table grouped by variant
- Action buttons: Add Bottles, View Orders, Profitability, Metafields
- Handles deficit alerts and Shopify quantity sync
- Delete manifest action for unallocated items

### Add Manifest ([resources/js/offer-add-manifest.tsx](resources/js/offer-add-manifest.tsx))
- Product selector with search filter
- Quantity input, submits to PUT manifests endpoint

### Profitability ([resources/js/offer-profitability.tsx](resources/js/offer-profitability.tsx))
- Calculates margins from offer price vs unit costs
- Shows product breakdown and sell-through scenarios
- Best/worst case profit analysis

### Metafields ([resources/js/offer-metafields.tsx](resources/js/offer-metafields.tsx))
- Fetches and displays offer metafield JSON
- Updates Shopify product metafields on load

### Order Manifests ([resources/js/offer-manifests.tsx](resources/js/offer-manifests.tsx))
- Shows orders with purchased vs upgrade items
- Highlights quantity mismatches
- Links to Shopify order admin

## Authentication

- Laravel session-based auth with login redirect
- [bootstrap/app.php](bootstrap/app.php) configures guest redirect with intended URL
- [LoginController](app/Http/Controllers/Auth/LoginController.php) handles redirect param

## Configuration

### Shopify ([config/services.php](config/services.php))
Shopify API credentials are now stored per-store in the `shopify_shops` table, not in environment variables. The legacy config block remains for reference:
```php
'shopify' => [
    'store_url' => env('SHOPIFY_STORE_URL'),
    'access_token' => env('SHOPIFY_ACCESS_TOKEN'),
    'webhook_secret' => env('SHOPIFY_WEBHOOK_SECRET'),
],
```

## Build/Test Workflow

```bash
# Install
composer install && pnpm install
cp .env.example .env
php artisan key:generate
php artisan migrate

# Development
composer dev    # Runs artisan serve + Vite concurrently
# Or separately:
php artisan serve
pnpm dev

# Testing
composer test   # PHPUnit
pnpm test       # Jest

# Build
pnpm build
```

## When Extending

1. **New pages**: Add blade template, TSX entry point, Vite input, web route
2. **New API endpoints**: Add service method, controller method, api route
3. **Shopify operations**: Add GraphQL constant and method to appropriate service
4. **Keep** `credentials: include` in fetches for session + CSRF
5. **Use** shadcn components, not react-bootstrap
6. **Follow** first-or-create patterns for per-entity resources