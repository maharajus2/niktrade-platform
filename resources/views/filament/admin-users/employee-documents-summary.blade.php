@php
    use App\Support\EmployeeRequiredDocuments;

    $missing = $employee->missingRequiredDocuments();
    $expiring = $employee->expiringDocuments();
@endphp

<div class="mb-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Документы сотрудника</h3>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Комплектность: <span class="font-medium text-gray-950 dark:text-white">{{ $employee->documentCompletenessPercent() }}%</span>
            </p>
        </div>

        <span @class([
            'inline-flex w-fit items-center rounded-md px-2 py-1 text-xs font-medium',
            'bg-green-50 text-green-700 ring-1 ring-green-600/20 dark:bg-green-500/10 dark:text-green-400' => $missing === [] && $expiring->isEmpty(),
            'bg-yellow-50 text-yellow-800 ring-1 ring-yellow-600/20 dark:bg-yellow-500/10 dark:text-yellow-300' => $missing === [] && $expiring->isNotEmpty(),
            'bg-red-50 text-red-700 ring-1 ring-red-600/20 dark:bg-red-500/10 dark:text-red-400' => $missing !== [],
        ])>
            {{ \App\Filament\Resources\AdminUsers\UserResource::documentStatusLabel($employee) }}
        </span>
    </div>

    <div class="mt-4 grid gap-4 md:grid-cols-2">
        <div>
            <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Не хватает</div>
            @if ($missing === [])
                <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">Обязательные документы загружены.</div>
            @else
                <ul class="mt-2 space-y-1 text-sm text-gray-700 dark:text-gray-300">
                    @foreach ($missing as $category)
                        <li>{{ EmployeeRequiredDocuments::label($category) }}</li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div>
            <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Истекают скоро</div>
            @if ($expiring->isEmpty())
                <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">Нет документов с ближайшим истечением срока.</div>
            @else
                <ul class="mt-2 space-y-1 text-sm text-gray-700 dark:text-gray-300">
                    @foreach ($expiring as $document)
                        <li>{{ $document->getCategoryLabel() }} — {{ $document->getExpirationLabel() }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>
