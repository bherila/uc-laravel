<?php

namespace App\Http\Controllers;

use App\Models\Webhook;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\ShopifyWebhookController;

class WebhookController extends Controller
{
    //
    // Web Routes
    //

    public function indexPage()
    {
        return view('admin.webhooks');
    }

    public function showPage(int $id)
    {
        return view('admin.webhook-detail', [
            'webhookId' => $id,
        ]);
    }

    //
    // API Routes
    //

    public function list(Request $request): JsonResponse
    {
        $webhooks = Webhook::with(['shop'])
            ->orderBy('id', 'desc')
            ->paginate(50); // Pagination is good for large lists

        return response()->json($webhooks);
    }

    public function get(int $id): JsonResponse
    {
        $webhook = Webhook::with(['subs', 'rerunOf', 'shop'])
            ->findOrFail($id);

        return response()->json($webhook);
    }

    public function rerun(int $id, Request $request): JsonResponse
    {
        $originalWebhook = Webhook::with(['shop'])->findOrFail($id);

        // Create a new request with the original payload and headers
        // We can't easily mock the full request object to pass to controller directly if it relies on specific request properties
        // But ShopifyWebhookController uses $request->getContent(), $request->header(), etc.
        
        // We will dispatch a self-request or instantiate the controller and call handle with a constructed request.
        // Constructing request is better to avoid network roundtrip and auth issues.
        
        $payload = $originalWebhook->payload;
        $headers = $originalWebhook->headers;
        if (is_string($headers)) {
            $headers = json_decode($headers, true) ?? [];
        }
        
        // Create new request
        // $request->getContent() returns the raw body. 
        // We can create a new Request instance.
        
        $newRequest = Request::create(
            '/api/shopify/webhook',
            'POST',
            [], // parameters
            [], // cookies
            [], // files
            [], // server
            is_array($payload) ? json_encode($payload) : $payload
        );
        
        // Set headers
        foreach ($headers as $key => $value) {
            // Headers in DB might be array of strings or single string depending on how it was captured.
            // json_encode($request->headers->all()) produces structure like {"header-name": ["value"]}
            if (is_array($value)) {
                $value = $value[0] ?? '';
            }
            $newRequest->headers->set($key, $value);
        }

        // Ensure X-Shopify-Shop-Domain is set if we have a shop_id but header is missing
        if (!$newRequest->header('X-Shopify-Shop-Domain') && $originalWebhook->shop) {
            $newRequest->headers->set('X-Shopify-Shop-Domain', $originalWebhook->shop->shop_domain);
        }

        if ($originalWebhook->shopify_topic) {
            $newRequest->headers->set('X-Shopify-Topic', $originalWebhook->shopify_topic);
        }

        // We also want to record that this is a rerun.
        // But ShopifyWebhookController creates the Webhook record.
        // We can't easily inject "rerun_of_id" into ShopifyWebhookController without modifying it further.
        // Or we can create the webhook record HERE, and pass it to ShopifyWebhookController?
        // ShopifyWebhookController currently creates the record.
        
        // Option 1: Modify ShopifyWebhookController to accept an optional pre-created webhook or rerun_of_id.
        // Option 2: Let ShopifyWebhookController create it, and then we find the latest webhook and update it? Risky concurrency.
        
        // Let's go with Option 1: Update ShopifyWebhookController to check for a header or attribute that indicates rerun?
        // Or cleaner: Refactor ShopifyWebhookController logic to a Service, but that's big.
        
        // Simplest: Add a header "X-Rerun-Of-Id" to the request we create.
        // Update ShopifyWebhookController to check this header and set rerun_of_id.
        
        $newRequest->headers->set('X-Rerun-Of-Id', $originalWebhook->id);
        
        // Mark as internal rerun to skip HMAC
        $newRequest->attributes->set('is_internal_rerun', true);
        
        // Call the controller
        $controller = app(ShopifyWebhookController::class);
        $response = $controller->handle($newRequest);
        
        // The controller created a new Webhook record. 
        // We want to return the ID of the new webhook?
        // The response is JSON. 
        // If we want to link the new webhook to the old one, we need ShopifyWebhookController to handle 'X-Rerun-Of-Id'.
        
        return $response;
    }
}
