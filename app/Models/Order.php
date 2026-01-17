<?php

namespace App\Models;

use App\Traits\SerializesDatesAsLocal;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use SerializesDatesAsLocal;

    protected $table = 'order_list';
    protected $primaryKey = 'order_guid';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'order_qty' => 'integer',
        'order_total_price' => 'decimal:2',
        'order_discount' => 'decimal:2',
        'order_credit_discount' => 'decimal:2',
        'order_tax' => 'decimal:2',
        'order_unit_price' => 'decimal:2',
        'order_upgraded_value' => 'decimal:2',
        'order_allocated_cogs' => 'decimal:2',
        'order_cc_fee' => 'decimal:2',
        'order_cash_in' => 'decimal:2',
        'order_disc_c' => 'decimal:2',
        'order_disc_f' => 'decimal:2',
        'order_disc_s' => 'decimal:2',
        'order_disc_r' => 'decimal:2',
        'order_disc_t' => 'decimal:2',
        'order_disc_m' => 'decimal:2',
        'order_disc_g' => 'decimal:2',
        'order_disc_other' => 'decimal:2',
        'order_ship_revenue' => 'decimal:2',
        'cohort_fp_mth' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(UserList::class, 'order_user', 'user_guid');
    }
}
