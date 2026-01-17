<?php

namespace App\Models;

use App\Traits\SerializesDatesAsLocal;
use Illuminate\Database\Eloquent\Model;

class ItemSku extends Model
{
    use SerializesDatesAsLocal;

    protected $table = 'item_sku';
    protected $primaryKey = 'sku';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'srp' => 'decimal:2',
        'is_autographed' => 'boolean',
        'is_taxable' => 'boolean',
        'is_counted_for_shipment' => 'boolean',
        'drink_by_date' => 'datetime',
        'last_order_date' => 'datetime',
        'last_restock' => 'datetime',
        'last_stock_update' => 'datetime',
        'last_stock_qty' => 'integer',
        'next_delivery_date' => 'datetime',
        'last_count_owed' => 'integer',
        'scramble_qty_allowed' => 'integer',
        'sku_cogs_unit' => 'decimal:2',
        'is_pallet_program' => 'boolean',
        'is_deprecated' => 'boolean',
        'last_count_shipped' => 'integer',
        'is_in_wd' => 'boolean',
        'sku_was_swap' => 'boolean',
        'sku_sort' => 'integer',
        'sku_qty_reserved' => 'integer',
        'sku_cogs_is_estimated' => 'boolean',
        'qty_offsite' => 'integer',
        'sku_fq_lo' => 'integer',
        'sku_fq_hi' => 'integer',
        'sku_velocity' => 'decimal:2',
        'last_vip_qty' => 'integer',
        'last_open_xfer_qty' => 'integer',
        'sku_is_dropship' => 'boolean',
        'sku_ship_alone' => 'boolean',
        'next_stock_update' => 'datetime',
        'sku_exclude_metrics' => 'boolean',
        'netsuite_synced' => 'datetime',
        'unlimited_allocation_until' => 'datetime',
        'avg_purchase_price' => 'decimal:2',
        'last_purchase_price' => 'decimal:2',
        'dont_buy_after' => 'datetime',
        'pack_size' => 'integer',
    ];

    public function detail()
    {
        return $this->belongsTo(ItemDetail::class, 'sku_itemdetail_guid', 'itemdetail_guid');
    }
}
