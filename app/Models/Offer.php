<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Offer extends Model
{
    protected $table = 'v3_offer';
    protected $primaryKey = 'offer_id';
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'shop_id' => 'integer',
    ];

    /**
     * Manifests for this offer.
     */
    public function manifests(): HasMany
    {
        return $this->hasMany(OfferManifest::class, 'offer_id', 'offer_id');
    }

    /**
     * The shop this offer belongs to.
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(ShopifyShop::class, 'shop_id');
    }
}
