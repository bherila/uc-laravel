<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventorySnapshot extends Model
{
    protected $table = '2023_05_31_inventory';
    protected $primaryKey = 'sku';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'units_on_hand' => 'integer',
        'cost_basis_unit' => 'decimal:2',
        'srp_unit' => 'decimal:2',
    ];
}
