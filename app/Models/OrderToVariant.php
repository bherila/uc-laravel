<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderToVariant extends Model
{
    protected $table = 'v3_order_to_variant';
    public $timestamps = false;
    protected $primaryKey = null;
    public $incrementing = false;

    protected $guarded = [];

    protected $casts = [
        'offer_id' => 'integer',
    ];
}
