<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Webhook extends Model
{
    protected $guarded = [];

    protected $casts = [
        'valid_hmac' => 'boolean',
        'valid_shop_matched' => 'boolean',
        'error_ts' => 'datetime',
        'success_ts' => 'datetime',
        'payload' => 'array', // Convenient casting if payload is JSON
        'headers' => 'array', // Convenient casting if headers are JSON
    ];

    public function subs(): HasMany
    {
        return $this->hasMany(WebhookSub::class);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(ShopifyShop::class, 'shop_id');
    }

    public function rerunOf(): BelongsTo
    {
        return $this->belongsTo(Webhook::class, 'rerun_of_id');
    }

    public function combineOperations(): HasMany
    {
        return $this->hasMany(CombineOperation::class);
    }
}
