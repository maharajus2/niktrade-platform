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
        $days = $this->formatWorkingDays();
        $time = $this->formatWorkingTime();

        return collect([$days, $time])->filter()->implode(' · ') ?: ($this->working_hours ?: '');
    }

    private function formatWorkingDays(): string
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

        $orderedDays = array_values(array_filter(
            array_keys($dayLabels),
            fn (string $day): bool => in_array($day, $this->working_days ?? [], true),
        ));

        if (count($orderedDays) === count($dayLabels)) {
            return 'Ежедневно';
        }

        $ranges = [];
        $rangeStart = null;
        $previousIndex = null;
        $dayIndexes = array_flip(array_keys($dayLabels));

        foreach ($orderedDays as $day) {
            $currentIndex = $dayIndexes[$day];

            if ($rangeStart === null) {
                $rangeStart = $day;
                $previousIndex = $currentIndex;

                continue;
            }

            if ($currentIndex === $previousIndex + 1) {
                $previousIndex = $currentIndex;

                continue;
            }

            $ranges[] = $this->formatDayRange($rangeStart, array_keys($dayLabels)[$previousIndex], $dayLabels);
            $rangeStart = $day;
            $previousIndex = $currentIndex;
        }

        if ($rangeStart !== null && $previousIndex !== null) {
            $ranges[] = $this->formatDayRange($rangeStart, array_keys($dayLabels)[$previousIndex], $dayLabels);
        }

        return implode(', ', $ranges);
    }

    private function formatDayRange(string $start, string $end, array $dayLabels): string
    {
        if ($start === $end) {
            return $dayLabels[$start];
        }

        return $dayLabels[$start] . '–' . $dayLabels[$end];
    }

    private function formatWorkingTime(): string
    {
        $from = trim((string) $this->working_time_from);
        $to = trim((string) $this->working_time_to);

        if ($from === '00:00' && $to === '23:59') {
            return 'Круглосуточно';
        }

        return collect([$from, $to])->filter()->implode('–');
    }
}
