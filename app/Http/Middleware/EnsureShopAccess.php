<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\ShopifyShop;

class EnsureShopAccess
{
    /**
     * Handle an incoming request.
     * Ensures the user has access to the shop specified by {shop} route parameter.
     * 
     * @param string $accessLevel 'read' or 'write' (default: 'read')
     */
    public function handle(Request $request, Closure $next, string $accessLevel = 'read'): Response
    {
        $user = $request->user();
        $shopId = $request->route('shop');

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            return redirect()->route('login');
        }

        // Validate shop exists
        $shop = ShopifyShop::find($shopId);
        if (!$shop) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Shop not found'], 404);
            }
            abort(404, 'Shop not found');
        }

        // Check access level
        if ($accessLevel === 'write') {
            if (!$user->hasShopWriteAccess($shopId)) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'Write access required for this shop'], 403);
                }
                abort(403, 'Write access required for this shop');
            }
        } else {
            if (!$user->hasShopAccess($shopId)) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'Access denied for this shop'], 403);
                }
                abort(403, 'Access denied for this shop');
            }
        }

        // Attach shop to request for use in controllers
        $request->attributes->set('shop', $shop);

        return $next($request);
    }
}
