<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComputedBuyerVarietal extends Model
{
    protected $table = 'computed_buyer_varietals';
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'total_paid' => 'decimal:5',
    ];
}
