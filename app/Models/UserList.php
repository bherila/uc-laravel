<?php

namespace App\Models;

use App\Traits\SerializesDatesAsLocal;
use Illuminate\Database\Eloquent\Model;

class UserList extends Model
{
    use SerializesDatesAsLocal;

    protected $table = 'user_list';
    protected $primaryKey = 'user_guid';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'user_last_purchase_dt' => 'datetime',
        'user_first_purchase_dt' => 'datetime',
        'x_life_credit' => 'decimal:2',
        'x_life_spend' => 'decimal:2',
        'x_life_discount' => 'decimal:2',
        'x_acquisition_cost' => 'decimal:2',
        'x_cloud_value' => 'decimal:2',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class, 'order_user', 'user_guid');
    }
}
