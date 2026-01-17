<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'email',
        'password',
        'alias',
        'ax_maxmin',
        'ax_homes',
        'ax_tax',
        'ax_evdb',
        'ax_spgp',
        'ax_uc',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'ax_maxmin' => 'boolean',
        'ax_homes' => 'boolean',
        'ax_tax' => 'boolean',
        'ax_evdb' => 'boolean',
        'ax_spgp' => 'boolean',
        'ax_uc' => 'boolean',
        'last_login_at' => 'datetime',
    ];
}