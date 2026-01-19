<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'email',
        'password',
        'pw',
        'salt',
        'alias',
        'is_admin',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'pw',
        'salt',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_admin' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    /**
     * Shops this user has access to.
     */
    public function shops(): BelongsToMany
    {
        return $this->belongsToMany(ShopifyShop::class, 'user_shop_accesses', 'user_id', 'shopify_shop_id')
            ->withPivot('access_level')
            ->withTimestamps();
    }

    /**
     * Shop access records for this user.
     */
    public function shopAccesses(): HasMany
    {
        return $this->hasMany(UserShopAccess::class);
    }

    /**
     * Check if user is an admin (id=1 or is_admin flag).
     */
    public function isAdmin(): bool
    {
        return $this->id === 1 || $this->is_admin;
    }

    /**
     * Check if user has any access to a specific shop.
     */
    public function hasShopAccess(int $shopId): bool
    {
        if ($this->isAdmin()) {
            return true;
        }
        return $this->shopAccesses()->where('shopify_shop_id', $shopId)->exists();
    }

    /**
     * Check if user has write access to a specific shop.
     */
    public function hasShopWriteAccess(int $shopId): bool
    {
        if ($this->isAdmin()) {
            return true;
        }
        return $this->shopAccesses()
            ->where('shopify_shop_id', $shopId)
            ->where('access_level', 'read-write')
            ->exists();
    }

    /**
     * Get the access level for a specific shop.
     */
    public function getShopAccessLevel(int $shopId): ?string
    {
        if ($this->isAdmin()) {
            return 'read-write';
        }
        $access = $this->shopAccesses()->where('shopify_shop_id', $shopId)->first();
        return $access?->access_level;
    }
}
