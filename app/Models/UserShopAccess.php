<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserShopAccess extends Model
{
    protected $table = 'user_shop_accesses';

    protected $fillable = [
        'user_id',
        'shopify_shop_id',
        'access_level',
    ];

    protected $casts = [
        'access_level' => 'string',
    ];

    /**
     * The user who has this access.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The shop this access is for.
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(ShopifyShop::class, 'shopify_shop_id');
    }

    /**
     * Check if this is read-write access.
     */
    public function canWrite(): bool
    {
        return $this->access_level === 'read-write';
    }

    /**
     * Check if this is at least read-only access.
     */
    public function canRead(): bool
    {
        return in_array($this->access_level, ['read-only', 'read-write']);
    }
}
