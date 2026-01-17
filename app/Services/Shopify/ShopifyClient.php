<?php

declare(strict_types=1);

namespace App\Services\Shopify;

use App\Models\ShopifyShop;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;

class ShopifyClient
{
    private ?string $shopDomain;
    private ?string $accessToken;
    private string $apiVersion;
    private ?ShopifyShop $shop = null;

    public function __construct(?ShopifyShop $shop = null)
    {
        if ($shop) {
            $this->configureFromShop($shop);
        } else {
            // Fallback to environment config for backwards compatibility
            $shopName = config('services.shopify.shop_name');
            $this->shopDomain = $shopName ? "{$shopName}.myshopify.com" : null;
            $this->accessToken = config('services.shopify.access_token');
            $this->apiVersion = config('services.shopify.api_version', '2025-01');
        }
    }

    /**
     * Configure the client from a ShopifyShop model.
     */
    public function configureFromShop(ShopifyShop $shop): self
    {
        $this->shop = $shop;
        $this->shopDomain = $shop->shop_domain;
        $this->accessToken = $shop->admin_api_token;
        $this->apiVersion = $shop->api_version ?? '2025-01';
        return $this;
    }

    /**
     * Get the current shop model.
     */
    public function getShop(): ?ShopifyShop
    {
        return $this->shop;
    }

    /**
     * Check if the client is properly configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->shopDomain) && !empty($this->accessToken);
    }

    /**
     * Ensure the client is configured before making requests
     */
    private function ensureConfigured(): void
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('Shopify shop_domain and access_token must be configured');
        }
    }

    protected function client(): PendingRequest
    {
        $this->ensureConfigured();
        
        return Http::baseUrl("https://{$this->shopDomain}/admin/api/{$this->apiVersion}")
            ->withHeaders([
                'X-Shopify-Access-Token' => $this->accessToken,
                'Content-Type' => 'application/json',
            ])
            ->timeout(30);
    }

    /**
     * Execute a GraphQL query against Shopify
     *
     * @param string $query The GraphQL query
     * @param array<string, mixed> $variables Variables for the query
     * @return array<string, mixed>
     */
    public function graphql(string $query, array $variables = []): array
    {
        $response = $this->client()->post('/graphql.json', [
            'query' => $query,
            'variables' => $variables,
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Shopify GraphQL request failed: ' . $response->body());
        }

        $data = $response->json();

        if (isset($data['errors']) && !empty($data['errors'])) {
            throw new \RuntimeException('Shopify GraphQL errors: ' . json_encode($data['errors']));
        }

        return $data['data'] ?? [];
    }

    public function getShopDomain(): ?string
    {
        return $this->shopDomain;
    }

    /**
     * @deprecated Use getShopDomain() instead
     */
    public function getShopName(): ?string
    {
        if (!$this->shopDomain) {
            return null;
        }
        // Extract shop name from domain for backwards compatibility
        return str_replace('.myshopify.com', '', $this->shopDomain);
    }
}
