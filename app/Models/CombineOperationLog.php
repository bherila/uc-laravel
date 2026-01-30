<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CombineOperationLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'shopify_request' => 'array',
        'shopify_response' => 'array',
    ];

    public function combineOperation(): BelongsTo
    {
        return $this->belongsTo(CombineOperation::class);
    }
}
