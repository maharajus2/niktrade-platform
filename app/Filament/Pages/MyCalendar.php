<?php

namespace App\Filament\Pages;

use App\Models\User;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class MyCalendar extends Page
{
    protected string $view = 'filament.pages.my-calendar';

    protected static ?string $title = 'Мой календарь';

    protected static ?string $navigationLabel = 'Мой календарь';

    protected static ?int $navigationSort = 0;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        return auth()->user() instanceof User;
    }

    public function getTitle(): string|Htmlable
    {
        return 'Мой календарь';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Личный календарь сотрудника: смены, отпуска, больничные, выходные и будущие личные события.';
    }
}
