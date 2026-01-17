<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ShopifyShop extends Model
{
    protected $table = 'shopify_shops';

    protected $fillable = [
        'name',
        'shop_domain',
        'app_name',
        'admin_api_token',
        'api_version',
        'api_key',
        'api_secret_key',
        'webhook_version',
        'webhook_secret',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'admin_api_token',
        'api_key',
        'api_secret_key',
        'webhook_secret',
    ];

    /**
     * Users who have access to this shop.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_shop_accesses', 'shopify_shop_id', 'user_id')
            ->withPivot('access_level')
            ->withTimestamps();
    }

    /**
     * User access records for this shop.
     */
    public function userAccesses(): HasMany
    {
        return $this->hasMany(UserShopAccess::class, 'shopify_shop_id');
    }

    /**
     * Offers belonging to this shop.
     */
    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class, 'shop_id');
    }

    /**
     * Get the full GraphQL API URL for this shop.
     */
    public function getGraphqlUrlAttribute(): string
    {
        return "https://{$this->shop_domain}/admin/api/{$this->api_version}/graphql.json";
    }

    /**
     * Get the store URL (admin base).
     */
    public function getStoreUrlAttribute(): string
    {
        return "https://{$this->shop_domain}";
    }
}
