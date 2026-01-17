<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerList extends Model
{
    protected $table = 'customer_list_july_2023';
    protected $primaryKey = 'email';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'orders' => 'integer',
        'ltv' => 'decimal:2',
    ];
}
