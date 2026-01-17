<?php

declare(strict_types=1);

namespace App\Services\Shopify;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;

class ShopifyClient
{
    private ?string $shopName;
    private ?string $accessToken;
    private string $apiVersion;

    public function __construct()
    {
        $this->shopName = config('services.shopify.shop_name');
        $this->accessToken = config('services.shopify.access_token');
        $this->apiVersion = config('services.shopify.api_version', '2025-01');
    }

    /**
     * Check if the client is properly configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->shopName) && !empty($this->accessToken);
    }

    /**
     * Ensure the client is configured before making requests
     */
    private function ensureConfigured(): void
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('Shopify shop_name and access_token must be configured in services.shopify');
        }
    }

    protected function client(): PendingRequest
    {
        $this->ensureConfigured();
        
        return Http::baseUrl("https://{$this->shopName}.myshopify.com/admin/api/{$this->apiVersion}")
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

    public function getShopName(): string
    {
        return $this->shopName;
    }
}
