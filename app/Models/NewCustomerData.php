<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewCustomerData extends Model
{
    protected $table = 'new_customer_data_after_bk_from_lcc';
    protected $primaryKey = 'Customer ID';
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'Total Spent' => 'decimal:2',
        'Order Count' => 'integer',
        'Item Count' => 'integer',
    ];
}
