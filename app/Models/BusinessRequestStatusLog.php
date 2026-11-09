<?php

namespace App\Models;

use App\Enums\BusinessRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessRequestStatusLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['business_request_id', 'from_status', 'to_status', 'user_id', 'note', 'created_at'];

    protected $casts = ['from_status' => BusinessRequestStatus::class, 'to_status' => BusinessRequestStatus::class, 'created_at' => 'datetime'];

    public function businessRequest(): BelongsTo
    {
        return $this->belongsTo(BusinessRequest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
