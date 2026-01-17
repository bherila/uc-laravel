<?php

namespace App\Models;

use App\Traits\SerializesDatesAsLocal;
use Illuminate\Database\Eloquent\Model;

class OrderLock extends Model
{
    use SerializesDatesAsLocal;

    protected $table = 'order_lock';
    protected $primaryKey = 'order_id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'locked_at' => 'datetime',
    ];
}
