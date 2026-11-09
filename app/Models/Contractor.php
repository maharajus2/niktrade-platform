<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Contractor extends Authenticatable
{
    protected $fillable = [
        'company_name', 'inn', 'kpp', 'ogrn', 'legal_address', 'director',
        'contact_name', 'phone', 'email', 'password', 'discount_percent',
        'is_active', 'manager_id', 'external_code', 'comment', 'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'password' => 'hashed',
        'discount_percent' => 'decimal:2',
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(BusinessRequest::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(BusinessCartItem::class);
    }

    public function isSoleProprietor(): bool
    {
        return strlen($this->inn) === 12;
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->company_name;
    }

    public function discountedPrice(float $price): float
    {
        return round($price * (1 - (float) $this->discount_percent / 100), 2);
    }
}
