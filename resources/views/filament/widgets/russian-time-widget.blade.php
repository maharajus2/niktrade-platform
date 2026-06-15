<x-filament-widgets::widget>
    <x-filament::section>
        <div
            class="space-y-4"
            x-data="{
                timezone: @js($this->getSelectedTimezone()),
                timezones: @js($this->timezones),
                hourAngle: 0,
                minuteAngle: 0,
                secondAngle: 0,
                timer: null,

                init() {
                    const city = localStorage.getItem('niktrade-dashboard-time-city');

                    if (city) {
                        $wire.set('selectedCity', city);
                        this.timezone = this.timezones[city] ?? 'Europe/Moscow';
                    }

                    this.tick();
                    this.timer = setInterval(() => this.tick(), 1000);
                },

                tick() {
                    const parts = new Intl.DateTimeFormat('ru-RU', {
                        timeZone: this.timezone,
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit',
                        hour12: false,
                    }).formatToParts(new Date());

                    const values = Object.fromEntries(parts.map((part) => [part.type, part.value]));
                    const hours = Number(values.hour);
                    const minutes = Number(values.minute);
                    const seconds = Number(values.second);

                    this.hourAngle = ((hours % 12) * 30) + (minutes * 0.5);
                    this.minuteAngle = (minutes * 6) + (seconds * 0.1);
                    this.secondAngle = seconds * 6;
                },

                changeCity(event) {
                    localStorage.setItem('niktrade-dashboard-time-city', event.target.value);
                    this.timezone = this.timezones[event.target.value] ?? 'Europe/Moscow';
                    this.tick();
                },
            }"
            wire:poll.30s
        >
            <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-5">
                    <div class="relative h-28 w-28 shrink-0 rounded-full border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <div class="absolute left-1/2 top-2 h-2 w-px -translate-x-1/2 rounded-full bg-gray-400 dark:bg-gray-500"></div>
                        <div class="absolute bottom-2 left-1/2 h-2 w-px -translate-x-1/2 rounded-full bg-gray-400 dark:bg-gray-500"></div>
                        <div class="absolute left-2 top-1/2 h-px w-2 -translate-y-1/2 rounded-full bg-gray-400 dark:bg-gray-500"></div>
                        <div class="absolute right-2 top-1/2 h-px w-2 -translate-y-1/2 rounded-full bg-gray-400 dark:bg-gray-500"></div>

                        <div
                            class="absolute left-1/2 top-1/2 h-8 w-1 origin-bottom -translate-x-1/2 -translate-y-full rounded-full bg-gray-950 dark:bg-white"
                            x-bind:style="`transform: translate(-50%, -100%) rotate(${hourAngle}deg);`"
                        ></div>

                        <div
                            class="absolute left-1/2 top-1/2 h-10 w-0.5 origin-bottom -translate-x-1/2 -translate-y-full rounded-full bg-gray-700 dark:bg-gray-200"
                            x-bind:style="`transform: translate(-50%, -100%) rotate(${minuteAngle}deg);`"
                        ></div>

                        <div
                            class="absolute left-1/2 top-1/2 h-11 w-px origin-bottom -translate-x-1/2 -translate-y-full rounded-full bg-danger-500"
                            x-bind:style="`transform: translate(-50%, -100%) rotate(${secondAngle}deg);`"
                        ></div>

                        <div class="absolute left-1/2 top-1/2 h-2.5 w-2.5 -translate-x-1/2 -translate-y-1/2 rounded-full bg-danger-500 ring-2 ring-white dark:ring-gray-900"></div>
                    </div>

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
                </div>

                <label class="w-full sm:w-56">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">
                        Город
                    </span>

                    <select
                        wire:model.live="selectedCity"
                        x-on:change="changeCity($event)"
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
