<x-filament-widgets::widget>
    <x-filament::section>
        <div
            class="space-y-4"
            x-data
            x-init="
                const city = localStorage.getItem('niktrade-dashboard-time-city');

                if (city) {
                    $wire.set('selectedCity', city);
                }
            "
            wire:poll.30s
        >
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        Текущее время
                    </div>

                    <div class="mt-1 text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">
                        {{ $this->getCurrentTime() }}
                    </div>

                    <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ $this->getCurrentDate() }} · {{ $this->getSelectedTimezone() }}
                    </div>
                </div>

                <label class="w-full sm:w-56">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">
                        Город
                    </span>

                    <select
                        wire:model.live="selectedCity"
                        x-on:change="localStorage.setItem('niktrade-dashboard-time-city', $event.target.value)"
                        class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                    >
                        @foreach ($timezones as $city => $timezone)
                            <option value="{{ $city }}">{{ $city }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
