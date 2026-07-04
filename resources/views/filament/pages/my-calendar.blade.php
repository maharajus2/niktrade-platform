<x-filament-panels::page>
    @livewire('employee-schedule-calendar', ['employeeId' => auth()->id()], key('my-calendar-'.auth()->id()))
</x-filament-panels::page>
