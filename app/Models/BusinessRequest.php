<?php

namespace App\Models;

use App\Enums\BusinessRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class BusinessRequest extends Model
{
    protected $attributes = ['status' => 'new'];

    protected $fillable = ['number', 'contractor_id', 'status', 'comment', 'discount_percent', 'subtotal', 'total', 'items_count', 'manager_id', 'invoice_number', 'invoice_path', 'invoice_issued_at', 'manager_comment'];

    protected $casts = ['status' => BusinessRequestStatus::class, 'discount_percent' => 'decimal:2', 'subtotal' => 'decimal:2', 'total' => 'decimal:2', 'items_count' => 'integer', 'invoice_issued_at' => 'datetime'];

    protected static function booted(): void
    {
        static::saving(function (self $request): void {
            if ($request->isDirty('invoice_path') && filled($request->invoice_path)) {
                $disk = Storage::disk('local');
                if (! str_starts_with($request->invoice_path, 'business-invoices/')
                    || str_contains($request->invoice_path, '..')
                    || ! $disk->exists($request->invoice_path)
                    || $disk->mimeType($request->invoice_path) !== 'application/pdf'
                    || $disk->size($request->invoice_path) > 10 * 1024 * 1024) {
                    throw ValidationException::withMessages(['invoice_path' => 'Загрузите PDF счёта размером до 10 МБ в приватное хранилище.']);
                }
            }
            if ($request->status === BusinessRequestStatus::Invoiced
                && (blank($request->invoice_number) || blank($request->invoice_path) || ! Storage::disk('local')->exists($request->invoice_path))) {
                throw ValidationException::withMessages(['status' => 'Для статуса «Счёт выставлен» укажите номер счёта и загрузите PDF.']);
            }
            if (filled($request->invoice_path) && $request->invoice_issued_at === null) {
                $request->invoice_issued_at = now();
            }
        });
        static::created(fn (self $request) => $request->statusLogs()->create([
            'to_status' => $request->status, 'note' => 'Заявка создана', 'created_at' => now(),
        ]));
        static::updated(function (self $request): void {
            if ($request->wasChanged('status')) {
                $request->statusLogs()->create([
                    'from_status' => $request->getRawOriginal('status'), 'to_status' => $request->status,
                    'user_id' => Auth::guard('web')->id(), 'note' => $request->manager_comment, 'created_at' => now(),
                ]);
            }
        });
    }

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(Contractor::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BusinessRequestItem::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(BusinessRequestStatusLog::class)->orderBy('id');
    }
}
