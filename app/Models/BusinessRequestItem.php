<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessRequestItem extends Model
{
    public $timestamps = false;

    protected $fillable = ['business_request_id', 'product_id', 'name', 'article', 'quantity', 'list_price', 'unit_price', 'line_total'];

    protected $casts = ['quantity' => 'integer', 'list_price' => 'decimal:2', 'unit_price' => 'decimal:2', 'line_total' => 'decimal:2'];

    public function businessRequest(): BelongsTo
    {
        return $this->belongsTo(BusinessRequest::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
