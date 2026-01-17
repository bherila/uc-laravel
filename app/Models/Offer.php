<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    protected $table = 'v3_offer';
    protected $primaryKey = 'offer_id';
    public $timestamps = false;

    protected $guarded = [];

    public function manifests()
    {
        return $this->hasMany(OfferManifest::class, 'offer_id', 'offer_id');
    }
}
