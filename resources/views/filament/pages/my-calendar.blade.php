<x-filament-panels::page>
    @component('layouts.work', [
        'title' => 'Календарь',
        'subtitle' => 'Личный рабочий календарь сотрудника',
        'user' => auth()->user(),
        'active' => 'calendar',
        'showSidebar' => true,
        'appClass' => 'nik-work-app--employee nik-work-app--calendar',
    ])
        @livewire('employee-schedule-calendar', ['employeeId' => auth()->id()], key('my-calendar-'.auth()->id()))
    @endcomponent
</x-filament-panels::page>
