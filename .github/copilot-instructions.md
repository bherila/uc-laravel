# UC Laravel Copilot Instructions

## Architecture Overview

- **Backend**: Laravel 12 (PHP 8.3+) API with Blade shell templates
- **Frontend**: React 19 + TypeScript via Vite
- **UI**: shadcn/ui components with Radix UI primitives and Tailwind CSS v4
- **Database**: MySQL with Eloquent ORM
- **External**: Shopify GraphQL Admin API integration

## Project Pattern

Blade routes in [routes/web.php](routes/web.php) return minimal views that mount React roots with `data-*` props for passing server data to client. React entrypoints are configured in [vite.config.ts](vite.config.ts) with alias `@` → `resources/js`.

### Mounting Pattern Example

Blade template ([resources/views/offer-detail.blade.php](resources/views/offer-detail.blade.php)):
```blade
@extends('layouts.app')
@section('content')
<div id="offer-detail-root" 
     data-api-base="{{ url('/api') }}" 
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
const offerId = root?.dataset.offerId;
const apiBase = root?.dataset.apiBase || '/api';
```

## Core Domain Models

All under [app/Models](app/Models):
- `Offer` - Wine offers with Shopify variant linkage
- `OfferManifest` - Individual bottle allocations to orders
- `OrderToVariant` - Links Shopify orders to variants for webhook processing

## Service Layer

### Offer Services ([app/Services/Offer](app/Services/Offer))
- `OfferService` - CRUD, detail views with manifests, metafield updates, order data
- `OfferManifestService` - Manifest quantity management, product data enrichment

### Shopify Services ([app/Services/Shopify](app/Services/Shopify))
- `ShopifyClient` - Base GraphQL client with lazy config validation
- `ShopifyProductService` - Product data, inventory, metafields
- `ShopifyOrderService` - Order queries, cancel, capture
- `ShopifyOrderProcessingService` - Full order processing with manifest allocation
- `ShopifyOrderEditService` - Order edit mutations (line items, discounts, shipping)
- `ShopifyFulfillmentService` - Fulfillment order operations

## API Surface

Defined in [routes/api.php](routes/api.php):

### Offers
- `GET/POST /api/offers` - List/create offers
- `GET/DELETE /api/offers/{id}` - Get/delete (use `?detail=1` for manifests)
- `GET /api/offers/{id}/metafields` - Update and return Shopify metafields
- `GET /api/offers/{id}/orders` - Get orders with manifest allocations

### Manifests
- `GET/PUT /api/offers/{id}/manifests` - Get summary / update quantities

### Shopify
- `GET /api/shopify/products?type=deal|manifest-item` - Get tagged products
- `POST /api/shopify/product-data` - Get product data by variant IDs
- `POST /api/shopify/set-inventory` - Set inventory quantity
- `POST /api/shopify/webhook` - Order webhook (HMAC verified, no auth)

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
4. Loading/error states handled with early returns
5. Container + MainTitle + content layout

## Key UI Flows

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