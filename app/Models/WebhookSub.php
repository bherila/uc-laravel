<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookSub extends Model
{
    protected $guarded = [];

    protected $casts = [
        'shopify_request' => 'array',
        'shopify_response' => 'array',
    ];

    public function webhook(): BelongsTo
    {
        return $this->belongsTo(Webhook::class);
    }
}
