<x-filament-widgets::widget>
    @include('filament.widgets.hr.partials.styles')

    <div id="employee-documents" class="nt-hr-dashboard rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">Мои документы</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Комплектность и сроки ваших документов.</p>
            </div>

            <a href="#employee-documents" class="text-sm font-medium text-primary-600 hover:text-primary-500">Открыть документы</a>
        </div>

        <div class="mt-5">
            <div class="flex items-center justify-between gap-3">
                <div class="text-sm text-gray-600">Комплектность</div>
                <div class="text-sm font-semibold text-gray-950">{{ $completeness }}%</div>
            </div>
            <div class="mt-2 rounded-full bg-gray-100" style="height: 8px;">
                <div class="rounded-full bg-primary-600" style="height: 8px; width: {{ $completeness }}%;"></div>
            </div>
        </div>

        <div class="mt-5 grid grid-cols-2 gap-3">
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                <div class="text-xs text-gray-500">Не хватает</div>
                <div class="mt-1 text-2xl font-semibold text-gray-950">{{ $missingCount }}</div>
            </div>
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                <div class="text-xs text-gray-500">Истекают</div>
                <div class="mt-1 text-2xl font-semibold text-gray-950">{{ $expiringCount }}</div>
            </div>
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                <div class="text-xs text-gray-500">Просрочены</div>
                <div class="mt-1 text-2xl font-semibold text-gray-950">{{ $expiredCount }}</div>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
