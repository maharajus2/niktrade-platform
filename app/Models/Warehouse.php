<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    protected $fillable = [
        'name',
        'city',
        'address',
        'postal_code',
        'region',
        'street',
        'house',
        'building',
        'premises',
        'phone',
        'working_hours',
        'working_days',
        'working_time_from',
        'working_time_to',
        'comment',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'working_days' => 'array',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function getFullAddressAttribute(): string
    {
        $structuredAddress = collect([
            $this->postal_code,
            $this->region,
            $this->city,
            $this->street,
            $this->house,
            $this->building,
            $this->premises,
        ])->filter()->implode(', ');

        return $structuredAddress ?: ($this->address ?: '');
    }

    public function getWorkingScheduleLabelAttribute(): string
    {
        $dayLabels = [
            'mon' => 'Пн',
            'tue' => 'Вт',
            'wed' => 'Ср',
            'thu' => 'Чт',
            'fri' => 'Пт',
            'sat' => 'Сб',
            'sun' => 'Вс',
        ];

        $days = collect($this->working_days ?? [])
            ->map(fn (string $day): ?string => $dayLabels[$day] ?? null)
            ->filter()
            ->implode(', ');

        $time = collect([$this->working_time_from, $this->working_time_to])
            ->filter()
            ->implode('–');

        return collect([$days, $time])->filter()->implode(' · ') ?: ($this->working_hours ?: '');
    }
}
