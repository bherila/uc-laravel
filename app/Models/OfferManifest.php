<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfferManifest extends Model
{
    protected $table = 'v3_offer_manifest';
    protected $primaryKey = 'm_id';
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'offer_id' => 'integer',
        'assignment_ordering' => 'float',
    ];

    public function offer()
    {
        return $this->belongsTo(Offer::class, 'offer_id', 'offer_id');
    }
}
