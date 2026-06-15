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

                init() {
                    const city = localStorage.getItem('niktrade-dashboard-time-city');

                    if (city) {
                        $wire.set('selectedCity', city);
                        this.timezone = this.timezones[city] ?? 'Europe/Moscow';
                    }

                    this.tick();
                    setInterval(() => this.tick(), 1000);
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
                    <div
                        aria-label="Аналоговые часы"
                        style="
                            position: relative;
                            width: 112px;
                            height: 112px;
                            flex: 0 0 112px;
                            border-radius: 9999px;
                            border: 1px solid rgb(209 213 219);
                            background: radial-gradient(circle at center, #ffffff 0%, #f9fafb 100%);
                            box-shadow: 0 10px 20px rgba(15, 23, 42, 0.08);
                        "
                    >
                        <span style="position: absolute; left: 50%; top: 8px; width: 2px; height: 8px; transform: translateX(-50%); border-radius: 9999px; background: #6b7280;"></span>
                        <span style="position: absolute; left: 50%; bottom: 8px; width: 2px; height: 8px; transform: translateX(-50%); border-radius: 9999px; background: #6b7280;"></span>
                        <span style="position: absolute; left: 8px; top: 50%; width: 8px; height: 2px; transform: translateY(-50%); border-radius: 9999px; background: #6b7280;"></span>
                        <span style="position: absolute; right: 8px; top: 50%; width: 8px; height: 2px; transform: translateY(-50%); border-radius: 9999px; background: #6b7280;"></span>

                        <span
                            style="position: absolute; left: 50%; top: 50%; width: 4px; height: 32px; border-radius: 9999px; background: #111827; transform-origin: 50% 100%; transform: translate(-50%, -100%) rotate(0deg);"
                            x-bind:style="{ transform: `translate(-50%, -100%) rotate(${hourAngle}deg)` }"
                        ></span>

                        <span
                            style="position: absolute; left: 50%; top: 50%; width: 3px; height: 42px; border-radius: 9999px; background: #374151; transform-origin: 50% 100%; transform: translate(-50%, -100%) rotate(0deg);"
                            x-bind:style="{ transform: `translate(-50%, -100%) rotate(${minuteAngle}deg)` }"
                        ></span>

                        <span
                            style="position: absolute; left: 50%; top: 50%; width: 1px; height: 46px; border-radius: 9999px; background: #dc2626; transform-origin: 50% 100%; transform: translate(-50%, -100%) rotate(0deg);"
                            x-bind:style="{ transform: `translate(-50%, -100%) rotate(${secondAngle}deg)` }"
                        ></span>

                        <span style="position: absolute; left: 50%; top: 50%; width: 10px; height: 10px; transform: translate(-50%, -50%); border-radius: 9999px; background: #dc2626; border: 2px solid #ffffff;"></span>
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
