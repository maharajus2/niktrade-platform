<?php

namespace App\Filament\Widgets\Hr;

use App\Filament\Resources\AdminUsers\UserResource;
use App\Filament\Widgets\Hr\Concerns\CanViewHrDashboard;
use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class HrBirthdaysWidget extends Widget
{
    use CanViewHrDashboard;

    protected string $view = 'filament.widgets.hr.birthdays-widget';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'xl' => 2,
    ];

    protected function getViewData(): array
    {
        $birthdays = User::query()
            ->with('department')
            ->whereNotNull('date_of_birth')
            ->get()
            ->map(function (User $employee): array {
                $birthday = $this->nextBirthday($employee);

                return [
                    'name' => $employee->name,
                    'department' => $employee->department?->name,
                    'avatar_path' => $employee->avatar_path,
                    'date' => $birthday,
                    'age' => $employee->age_label,
                    'url' => UserResource::getUrl('view', ['record' => $employee]),
                ];
            })
            ->sortBy('date')
            ->values();

        return [
            'today' => $birthdays->filter(fn (array $item): bool => $item['date']->isToday())->values()->all(),
            'week' => $birthdays->filter(fn (array $item): bool => $item['date']->betweenIncluded(today(), today()->addDays(7)))->take(8)->values()->all(),
            'month' => $birthdays->filter(fn (array $item): bool => $item['date']->betweenIncluded(today(), today()->addDays(30)))->take(10)->values()->all(),
        ];
    }

    private function nextBirthday(User $employee): Carbon
    {
        $birthday = Carbon::parse($employee->date_of_birth)->year((int) today()->format('Y'));

        return $birthday->isBefore(today()) ? $birthday->addYear() : $birthday;
    }
}
