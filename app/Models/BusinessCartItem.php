<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessCartItem extends Model
{
    protected $fillable = ['contractor_id', 'product_id', 'quantity'];

    protected $casts = ['quantity' => 'integer'];

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
