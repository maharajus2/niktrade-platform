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
        'comment',
    ];

    protected $casts = [
        'birthday' => 'date',
        'is_active' => 'boolean',
        'accepts_marketing' => 'boolean',
    ];

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }
}
