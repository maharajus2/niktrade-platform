@php
    $workflow = $record->approvalWorkflow;
    $events = $workflow?->events?->sortBy('created_at') ?? collect();
@endphp

<div class="space-y-4">
    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <div class="grid gap-3 sm:grid-cols-2">
            <div>
                <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Сотрудник</div>
                <div class="text-sm font-semibold text-gray-950 dark:text-white">{{ $record->employee?->name ?? '—' }}</div>
            </div>
            <div>
                <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Статус</div>
                <div class="text-sm font-semibold text-gray-950 dark:text-white">{{ $record->getStatusLabel() }}</div>
            </div>
            <div>
                <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Период</div>
                <div class="text-sm font-semibold text-gray-950 dark:text-white">{{ $record->getDateRangeLabel() }}</div>
            </div>
            <div>
                <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Раздел</div>
                <div class="text-sm font-semibold text-gray-950 dark:text-white">
                    @if ($record->isDeletedState())
                        Удалённые
                    @elseif ($record->isArchived())
                        Архив
                    @else
                        Активные
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <h3 class="text-sm font-semibold text-gray-950 dark:text-white">История заявки</h3>

        @if ($events->isEmpty())
            <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">История пока пуста.</p>
        @else
            <div class="mt-4 space-y-4">
                @foreach ($events as $event)
                    <div class="border-l-2 border-primary-500 pl-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm font-semibold text-gray-950 dark:text-white">{{ $event->getActionLabel() }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $event->created_at?->format('d.m.Y H:i') }}</span>
                        </div>

                        <div class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                            {{ $event->actor?->name ?? 'Система' }}
                        </div>

                        @if ($event->forwardedTo)
                            <div class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                                Передано: <span class="font-medium">{{ $event->forwardedTo->name }}</span>
                            </div>
                        @endif

                        @if (filled($event->comment))
                            <div class="mt-2 rounded-md bg-gray-50 p-3 text-sm text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                {{ $event->comment }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
