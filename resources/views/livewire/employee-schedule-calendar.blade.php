<div class="space-y-4">
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">
        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
            <div>
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">График работы</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Индивидуальные рабочие дни и смены сотрудника.
                </p>
            </div>

            @if ($employee->isIndividualSchedule())
                <div class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        wire:click="previousMonth"
                        class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-200 dark:hover:bg-white/10"
                    >
                        Назад
                    </button>

                    <button
                        type="button"
                        wire:click="goToToday"
                        class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-200 dark:hover:bg-white/10"
                    >
                        Сегодня
                    </button>

                    <button
                        type="button"
                        wire:click="nextMonth"
                        class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-200 dark:hover:bg-white/10"
                    >
                        Вперёд
                    </button>

                    @if ($canGenerate)
                        <button
                            type="button"
                            wire:click="openGenerateForm"
                            class="rounded-lg bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500"
                        >
                            Заполнить месяц
                        </button>
                    @endif
                </div>
            @endif
        </div>

        @if (! $employee->isIndividualSchedule())
            <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 dark:border-white/10 dark:bg-white/5 dark:text-gray-300">
                Для сотрудника выбран тип графика: <span class="font-semibold">{{ $employee->getScheduleTypeLabel() }}</span>.
                Календарь используется только для индивидуального графика.
            </div>
        @else
            <div class="mt-5 flex items-center justify-between">
                <h4 class="text-lg font-semibold text-gray-950 dark:text-white">{{ $monthLabel }}</h4>
                <span class="text-sm text-gray-500 dark:text-gray-400">Пн - Вс</span>
            </div>

            @if ($monthEntriesCount === 0)
                <div class="mt-4 rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4 text-sm text-gray-600 dark:border-white/10 dark:bg-white/5 dark:text-gray-300">
                    На этот месяц смены не назначены.
                </div>
            @endif

            @if ($entryFormVisible)
                <form wire:submit="saveEntry" class="mt-4 rounded-xl border border-primary-200 bg-primary-50/70 p-4 dark:border-primary-500/30 dark:bg-primary-500/10">
                    <div class="grid gap-4 md:grid-cols-4">
                        <label class="space-y-1">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Дата</span>
                            <input
                                type="date"
                                wire:model="entryDate"
                                class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-900 dark:text-white"
                            >
                            @error('entryDate') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                        </label>

                        <label class="space-y-1">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Начало</span>
                            <input
                                type="time"
                                wire:model="startsAt"
                                class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-900 dark:text-white"
                            >
                            @error('startsAt') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                        </label>

                        <label class="space-y-1">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Окончание</span>
                            <input
                                type="time"
                                wire:model="endsAt"
                                class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-900 dark:text-white"
                            >
                            @error('endsAt') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                        </label>

                        <label class="space-y-1 md:col-span-4">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Комментарий</span>
                            <textarea
                                wire:model="comment"
                                rows="2"
                                class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-900 dark:text-white"
                            ></textarea>
                            @error('comment') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                        </label>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <button type="submit" class="rounded-lg bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500">
                            Сохранить смену
                        </button>

                        <button type="button" wire:click="cancelEntryForm" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-200 dark:hover:bg-white/10">
                            Отмена
                        </button>

                        @if ($editingEntryId)
                            <button type="button" wire:click="deleteEntry({{ $editingEntryId }})" wire:confirm="Удалить смену?" class="rounded-lg border border-danger-300 bg-white px-3 py-2 text-sm font-medium text-danger-700 shadow-sm hover:bg-danger-50 dark:border-danger-500/40 dark:bg-white/5 dark:text-danger-300">
                                Удалить
                            </button>
                        @endif
                    </div>
                </form>
            @endif

            @if ($generateFormVisible)
                <form wire:submit="generateMonth" class="mt-4 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                    <div class="grid gap-4 md:grid-cols-4">
                        <label class="space-y-1">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">С даты</span>
                            <input type="date" wire:model="generateFromDate" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-900 dark:text-white">
                            @error('generateFromDate') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                        </label>

                        <label class="space-y-1">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">По дату</span>
                            <input type="date" wire:model="generateToDate" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-900 dark:text-white">
                            @error('generateToDate') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                        </label>

                        <label class="space-y-1">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Начало</span>
                            <input type="time" wire:model="generateStartsAt" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-900 dark:text-white">
                            @error('generateStartsAt') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                        </label>

                        <label class="space-y-1">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Окончание</span>
                            <input type="time" wire:model="generateEndsAt" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-900 dark:text-white">
                            @error('generateEndsAt') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                        </label>

                        <div class="space-y-2 md:col-span-4">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Рабочие дни</span>
                            <div class="flex flex-wrap gap-2">
                                @foreach ([1 => 'Пн', 2 => 'Вт', 3 => 'Ср', 4 => 'Чт', 5 => 'Пт', 6 => 'Сб', 7 => 'Вс'] as $value => $label)
                                    <label class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 dark:border-white/10 dark:bg-gray-900 dark:text-gray-200">
                                        <input type="checkbox" wire:model="generateWeekdays" value="{{ $value }}" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                        <span>{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('generateWeekdays') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                        </div>

                        <label class="space-y-1 md:col-span-4">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Комментарий</span>
                            <textarea wire:model="generateComment" rows="2" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-900 dark:text-white"></textarea>
                            @error('generateComment') <span class="text-xs text-danger-600">{{ $message }}</span> @enderror
                        </label>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <button type="submit" class="rounded-lg bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500">
                            Создать смены
                        </button>

                        <button type="button" wire:click="cancelGenerateForm" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-200 dark:hover:bg-white/10">
                            Отмена
                        </button>
                    </div>
                </form>
            @endif

            <div class="mt-4 hidden overflow-hidden rounded-xl border border-gray-200 md:block dark:border-white/10">
                <div class="grid grid-cols-7 border-b border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5">
                    @foreach ($weekdays as $weekday)
                        <div class="px-3 py-2 text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {{ $weekday }}
                        </div>
                    @endforeach
                </div>

                <div class="grid grid-cols-7 bg-white dark:bg-gray-900">
                    @foreach ($weeks as $week)
                        @foreach ($week as $day)
                            <div class="min-h-36 border-b border-r border-gray-200 p-2 last:border-r-0 dark:border-white/10 {{ $day['inMonth'] ? '' : 'bg-gray-50 text-gray-400 dark:bg-white/5' }}">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full text-sm font-semibold {{ $day['isToday'] ? 'bg-primary-600 text-white' : 'text-gray-700 dark:text-gray-200' }}">
                                        {{ $day['day'] }}
                                    </span>

                                    @if ($canUpdate && ! $day['isPast'] && $day['inMonth'])
                                        <button
                                            type="button"
                                            wire:click="startCreate('{{ $day['date'] }}')"
                                            class="rounded-md px-2 py-1 text-xs font-medium text-primary-700 hover:bg-primary-50 dark:text-primary-300 dark:hover:bg-primary-500/10"
                                        >
                                            + Смена
                                        </button>
                                    @endif
                                </div>

                                <div class="mt-2 space-y-2">
                                    @forelse ($day['entries'] as $entry)
                                        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-2 text-xs text-emerald-950 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-100">
                                            <div class="flex items-start justify-between gap-2">
                                                <button
                                                    type="button"
                                                    @if ($canUpdate && ! $day['isPast'])
                                                        wire:click="startEdit({{ $entry->id }})"
                                                    @endif
                                                    class="font-semibold text-left {{ $canUpdate && ! $day['isPast'] ? 'hover:underline' : 'cursor-default' }}"
                                                >
                                                    {{ $entry->timeLabel() }}
                                                </button>

                                                @if ($canUpdate && ! $day['isPast'])
                                                    <button
                                                        type="button"
                                                        wire:click="deleteEntry({{ $entry->id }})"
                                                        wire:confirm="Удалить смену?"
                                                        class="shrink-0 text-danger-600 hover:text-danger-500"
                                                    >
                                                        Удалить
                                                    </button>
                                                @endif
                                            </div>

                                            @if ($entry->comment)
                                                <div class="mt-1 line-clamp-2 text-emerald-800 dark:text-emerald-200">{{ $entry->comment }}</div>
                                            @endif
                                        </div>
                                    @empty
                                        <div class="text-xs text-gray-400">Нет смен</div>
                                    @endforelse
                                </div>
                            </div>
                        @endforeach
                    @endforeach
                </div>
            </div>

            <div class="mt-4 space-y-3 md:hidden">
                @foreach ($weeks as $week)
                    @foreach ($week as $day)
                        @continue(! $day['inMonth'])

                        <div class="rounded-xl border border-gray-200 bg-white p-3 dark:border-white/10 dark:bg-gray-900">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <div class="font-semibold text-gray-950 dark:text-white">
                                        {{ $day['day'] }} {{ $monthLabel }}
                                    </div>
                                    @if ($day['isToday'])
                                        <div class="text-xs font-medium text-primary-600 dark:text-primary-300">Сегодня</div>
                                    @endif
                                </div>

                                @if ($canUpdate && ! $day['isPast'])
                                    <button
                                        type="button"
                                        wire:click="startCreate('{{ $day['date'] }}')"
                                        class="rounded-lg bg-primary-600 px-3 py-2 text-xs font-semibold text-white shadow-sm"
                                    >
                                        + Смена
                                    </button>
                                @endif
                            </div>

                            <div class="mt-3 space-y-2">
                                @forelse ($day['entries'] as $entry)
                                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-950 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-100">
                                        <div class="flex items-start justify-between gap-2">
                                            <button
                                                type="button"
                                                @if ($canUpdate && ! $day['isPast'])
                                                    wire:click="startEdit({{ $entry->id }})"
                                                @endif
                                                class="font-semibold text-left {{ $canUpdate && ! $day['isPast'] ? 'hover:underline' : 'cursor-default' }}"
                                            >
                                                {{ $entry->timeLabel() }}
                                            </button>

                                            @if ($canUpdate && ! $day['isPast'])
                                                <button
                                                    type="button"
                                                    wire:click="deleteEntry({{ $entry->id }})"
                                                    wire:confirm="Удалить смену?"
                                                    class="text-xs font-medium text-danger-600"
                                                >
                                                    Удалить
                                                </button>
                                            @endif
                                        </div>

                                        @if ($entry->comment)
                                            <div class="mt-1 text-sm text-emerald-800 dark:text-emerald-200">{{ $entry->comment }}</div>
                                        @endif
                                    </div>
                                @empty
                                    <div class="rounded-lg border border-dashed border-gray-200 p-3 text-sm text-gray-500 dark:border-white/10 dark:text-gray-400">
                                        Нет смен
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                @endforeach
            </div>
        @endif
    </div>
</div>
