<?php

declare(strict_types=1);

namespace App\Services\Shopify;

use App\Models\WebhookSub;
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
    private ?int $webhookId = null;

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

    public function setWebhookId(?int $webhookId): self
    {
        $this->webhookId = $webhookId;
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
     * @param int $retries Internal use for tracking retries
     * @return array<string, mixed>
     */
    public function graphql(string $query, array $variables = [], int $retries = 0): array
    {
        // Check for large ID arrays that need batching in nodes queries
        foreach ($variables as $key => $value) {
            if (is_array($value) && count($value) > 250 && preg_match('/nodes\s*\(\s*ids\s*:/i', $query)) {
                return $this->graphqlBatched($query, $variables, $key);
            }
        }

        $startTime = (int) (microtime(true) * 1000);
        $payload = ['query' => $query];
        if (!empty($variables)) {
            $payload['variables'] = $variables;
        }

        $response = $this->client()->post('/graphql.json', $payload);
        $elapsed = (int) (microtime(true) * 1000) - $startTime;

        if ($this->webhookId) {
            try {
                WebhookSub::create([
                    'webhook_id' => $this->webhookId,
                    'event' => 'Shopify GraphQL',
                    'time_taken_ms' => $elapsed,
                    'shopify_request' => json_encode(['query' => $query, 'variables' => $variables]),
                    'shopify_response' => $response->body(),
                    'shopify_response_code' => $response->status(),
                ]);
            } catch (\Exception $e) {
                // Ignore logging errors to avoid breaking the flow
            }
        }

        // Handle rate limiting
        if ($response->status() === 429) {
            if ($retries < 5) {
                $retryAfter = $response->header('Retry-After');
                $waitSeconds = $retryAfter ? (int) $retryAfter : 2;
                sleep($waitSeconds);
                return $this->graphql($query, $variables, $retries + 1);
            }
        }

        if (!$response->successful()) {
            throw new \RuntimeException('Shopify GraphQL request failed: ' . $response->body());
        }

        $data = $response->json();

        if (isset($data['errors']) && !empty($data['errors'])) {
            // Check for batch-related errors if we somehow missed them proactively
            foreach ($data['errors'] as $error) {
                if (($error['extensions']['code'] ?? '') === 'MAX_INPUT_SIZE_EXCEEDED') {
                    // This should have been caught by the proactive check, but just in case:
                    // We can't easily auto-fix here without knowing which variable is the culprit if multiple arrays exist
                }
            }
            throw new \RuntimeException('Shopify GraphQL errors: ' . json_encode($data['errors']));
        }

        return $data['data'] ?? [];
    }

    /**
     * Split a nodes query into batches of 250
     */
    private function graphqlBatched(string $query, array $variables, string $idsKey): array
    {
        $allIds = $variables[$idsKey];
        $batches = array_chunk($allIds, 250);
        $mergedNodes = [];
        $lastResult = [];

        foreach ($batches as $batch) {
            $currentVariables = $variables;
            $currentVariables[$idsKey] = $batch;

            // Call graphql recursively (it will skip batching logic since count is <= 250)
            $result = $this->graphql($query, $currentVariables);

            if (isset($result['nodes']) && is_array($result['nodes'])) {
                $mergedNodes = array_merge($mergedNodes, $result['nodes']);
            }

            $lastResult = $result;
        }

        if (!empty($mergedNodes)) {
            $lastResult['nodes'] = $mergedNodes;
        }

        return $lastResult;
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
