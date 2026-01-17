<?php

namespace App\Models;

use App\Traits\SerializesDatesAsLocal;
use Illuminate\Database\Eloquent\Model;

class OldOrderData extends Model
{
    use SerializesDatesAsLocal;

    protected $table = 'old_order_data_500k_orders';
    protected $primaryKey = 'order_guid';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'order_qty' => 'integer',
        'order_total_price' => 'decimal:2',
        'order_timestamp' => 'datetime',
        'order_auth_date' => 'datetime',
        'order_transaction_id' => 'integer',
    ];
}
