<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemDetail extends Model
{
    protected $table = 'item_detail';
    protected $primaryKey = 'itemdetail_guid';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'cola_abv' => 'float',
        'retail_price' => 'float',
        'ct_wine_id' => 'integer',
        'ct_producer_id' => 'integer',
        'ct_likes' => 'integer',
        'ct_tasting_notes' => 'integer',
        'ct_review' => 'integer',
        'ct_qty' => 'integer',
    ];

    public function skus()
    {
        return $this->hasMany(ItemSku::class, 'sku_itemdetail_guid', 'itemdetail_guid');
    }
}
