# UC Laravel

A Laravel-based web application for managing Underground Cellar wine offers and Shopify order processing. The application provides offer management, manifest allocation, profitability analysis, and Shopify integration.

## Features

- **Offer Management**: Create and manage wine offers with Shopify product integration
- **Manifest Allocation**: Add wine bottles to offers and track allocation to orders
- **Order Processing**: Automatic order manifest allocation via Shopify webhooks
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
   
   Update `.env` with your database credentials and Shopify API keys:
   ```
   SHOPIFY_STORE_URL=your-store.myshopify.com
   SHOPIFY_ACCESS_TOKEN=shpat_xxxxx
   SHOPIFY_WEBHOOK_SECRET=your-webhook-secret
   ```

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
├── Http/Controllers/           # API and web controllers
│   ├── OfferController.php     # Offer CRUD and metafields
│   ├── OfferManifestController.php
│   ├── ShopifyController.php   # Product data and inventory
│   └── ShopifyWebhookController.php
├── Models/                     # Eloquent models
│   ├── Offer.php
│   ├── OfferManifest.php
│   └── OrderToVariant.php
└── Services/
    ├── Offer/                  # Offer business logic
    │   ├── OfferService.php
    │   └── OfferManifestService.php
    └── Shopify/                # Shopify API services
        ├── ShopifyClient.php
        ├── ShopifyOrderService.php
        ├── ShopifyProductService.php
        └── ShopifyOrderProcessingService.php

resources/js/
├── offers.tsx                  # Offer list page
├── offer-new.tsx               # Create new offer
├── offer-detail.tsx            # Offer detail with manifests
├── offer-add-manifest.tsx      # Add bottles to offer
├── offer-profitability.tsx     # Profitability analysis
├── offer-metafields.tsx        # Shopify metafield viewer
├── offer-manifests.tsx         # Order manifest viewer
└── components/
    └── ui/                     # shadcn/ui components
```

## API Endpoints

### Offers
- `GET /api/offers` - List all offers with Shopify product data
- `POST /api/offers` - Create new offer
- `GET /api/offers/{id}` - Get offer details (add `?detail=1` for manifests)
- `DELETE /api/offers/{id}` - Delete offer and unassigned manifests
- `GET /api/offers/{id}/metafields` - Update and get Shopify metafields
- `GET /api/offers/{id}/orders` - Get orders for an offer

### Manifests
- `GET /api/offers/{id}/manifests` - Get manifest summary
- `PUT /api/offers/{id}/manifests` - Update manifest quantities

### Shopify
- `GET /api/shopify/products?type=deal|manifest-item` - Get products by type
- `POST /api/shopify/product-data` - Get product data by variant IDs
- `POST /api/shopify/set-inventory` - Set inventory quantity
- `POST /api/shopify/webhook` - Order webhook endpoint

## Web Routes

- `/` - Home page
- `/login` - Authentication
- `/offers` - Offer list (requires auth)
- `/offers/new` - Create new offer
- `/offers/{id}` - Offer detail page
- `/offers/{id}/add-manifest` - Add bottles to offer
- `/offers/{id}/profitability` - View profitability analysis
- `/offers/{id}/metafields` - View/update Shopify metafields
- `/offers/{id}/shopify_manifests` - View order allocations

## Shopify Integration

The application integrates with Shopify for:

1. **Product Data**: Fetches deal and manifest-item products tagged in Shopify
2. **Inventory Management**: Sets inventory quantities for offer SKUs
3. **Order Webhooks**: Processes `orders/create` webhooks to allocate manifests
4. **Metafield Sync**: Updates product metafields with offer configuration

### Webhook Setup

Configure a webhook in Shopify Admin:
- **Webhook URL**: `https://your-domain.com/api/shopify/webhook`
- **Event**: `orders/create`
- **Format**: JSON

## Authentication

The application uses Laravel's built-in session-based authentication. Protected routes redirect to `/login` with the intended URL preserved for post-login redirect.

## License

Proprietary - All Rights Reserved
