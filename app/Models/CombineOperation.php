<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CombineOperation extends Model
{
    protected $guarded = [];

    protected $casts = [
        'fulfillment_orders_before' => 'integer',
        'fulfillment_orders_after' => 'integer',
    ];

    public function auditLog(): BelongsTo
    {
        return $this->belongsTo(AuditLog::class);
    }

    public function webhook(): BelongsTo
    {
        return $this->belongsTo(Webhook::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(ShopifyShop::class, 'shop_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(CombineOperationLog::class);
    }
}
