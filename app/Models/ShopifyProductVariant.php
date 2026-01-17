<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopifyProductVariant extends Model
{
    protected $table = 'shopify_product_variant';
    protected $primaryKey = 'variantId';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'variantInventoryQuantity' => 'integer',
    ];
}
