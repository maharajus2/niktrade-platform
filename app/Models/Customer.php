<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'birthday',
        'is_active',
        'accepts_marketing',
        'is_quick_registered',
        'comment',
    ];

    protected $casts = [
        'birthday' => 'date',
        'is_active' => 'boolean',
        'accepts_marketing' => 'boolean',
        'is_quick_registered' => 'boolean',
    ];

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }
}
