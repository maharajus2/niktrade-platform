<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Customer extends Authenticatable
{
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'phone_verified_at',
        'avatar_path',
        'telegram_id',
        'telegram_username',
        'telegram_first_name',
        'telegram_last_name',
        'telegram_verified_at',
        'password',
        'birthday',
        'is_active',
        'accepts_marketing',
        'is_quick_registered',
        'comment',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'birthday' => 'date',
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'telegram_verified_at' => 'datetime',
        'password' => 'hashed',
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

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function phoneVerifications(): HasMany
    {
        return $this->hasMany(CustomerPhoneVerification::class);
    }

    public function telegramLinkTokens(): HasMany
    {
        return $this->hasMany(CustomerTelegramLinkToken::class);
    }

    public function hasVerifiedPhone(): bool
    {
        return $this->phone_verified_at !== null;
    }

    public function hasVerifiedTelegram(): bool
    {
        return $this->telegram_id !== null && $this->telegram_verified_at !== null;
    }

    public function getTelegramDisplayNameAttribute(): string
    {
        if ($this->telegram_username) {
            return '@' . $this->telegram_username;
        }

        return trim($this->telegram_first_name . ' ' . $this->telegram_last_name);
    }
}
