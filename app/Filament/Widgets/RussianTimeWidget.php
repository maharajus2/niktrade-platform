<?php

namespace App\Filament\Widgets;

use Carbon\CarbonImmutable;
use Filament\Widgets\Widget;

class RussianTimeWidget extends Widget
{
    protected static string $view = 'filament.widgets.russian-time-widget';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'md' => 1,
    ];

    public string $selectedCity = 'Москва';

    public array $timezones = [
        'Москва' => 'Europe/Moscow',
        'Калининград' => 'Europe/Kaliningrad',
        'Самара' => 'Europe/Samara',
        'Екатеринбург' => 'Asia/Yekaterinburg',
        'Омск' => 'Asia/Omsk',
        'Красноярск' => 'Asia/Krasnoyarsk',
        'Иркутск' => 'Asia/Irkutsk',
        'Якутск' => 'Asia/Yakutsk',
        'Владивосток' => 'Asia/Vladivostok',
        'Магадан' => 'Asia/Magadan',
        'Камчатка' => 'Asia/Kamchatka',
    ];

    public function getCurrentTime(): string
    {
        return CarbonImmutable::now($this->getSelectedTimezone())->format('H:i:s');
    }

    public function getCurrentDate(): string
    {
        return CarbonImmutable::now($this->getSelectedTimezone())->translatedFormat('d F Y');
    }

    public function getSelectedTimezone(): string
    {
        return $this->timezones[$this->selectedCity] ?? 'Europe/Moscow';
    }
}
