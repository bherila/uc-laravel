<?php

namespace App\Models;

use App\Traits\SerializesDatesAsLocal;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use SerializesDatesAsLocal;

    protected $table = 'v3_audit_log';
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'event_ts' => 'datetime',
        'event_userid' => 'integer',
        'offer_id' => 'integer',
        'order_id' => 'integer',
        'time_taken_ms' => 'integer',
    ];
}
