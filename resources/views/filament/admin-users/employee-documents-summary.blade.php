@php
    use App\Models\EmployeeDocument;
    use App\Support\EmployeeRequiredDocuments;
    use Illuminate\Support\Carbon;

    $activeDocuments = $employee->activeDocuments()->latest()->get();
    $documentsByCategory = $activeDocuments->keyBy('category');
    $required = EmployeeRequiredDocuments::requiredFor($employee);
    $optional = EmployeeRequiredDocuments::optionalFor($employee);
    $missing = $employee->missingRequiredDocuments();
    $expiring = $employee->expiringDocuments();
    $expiredCount = $expiring->filter(fn (EmployeeDocument $document): bool => $document->isExpired())->count();
    $percent = $employee->documentCompletenessPercent();

    $groups = [
        'Основные' => ['passport', 'snils', 'inn', 'personal_data_consent'],
        'Кадровые' => ['employment_record', 'employment_contract', 'education_document'],
        'Медицинские' => ['medical_book', 'voluntary_medical_insurance'],
        'Миграционные' => [
            'foreign_passport',
            'passport_translation',
            'migration_card',
            'migration_registration',
            'patent',
            'work_permit',
            'visa',
            'temporary_residence_permit',
            'residence_permit',
            'tax_payment_receipt',
            'foreign_employment_notice',
            'foreign_dismissal_notice',
        ],
        'Прочие' => ['driver_license', 'other'],
    ];

    $relevantCategories = collect([...$required, ...$optional, ...$activeDocuments->pluck('category')->all()])
        ->unique()
        ->values();

    $dayLabel = function (int $days): string {
        $mod10 = $days % 10;
        $mod100 = $days % 100;

        if ($mod10 === 1 && $mod100 !== 11) {
            return 'день';
        }

        if ($mod10 >= 2 && $mod10 <= 4 && ! in_array($mod100, [12, 13, 14], true)) {
            return 'дня';
        }

        return 'дней';
    };

    $formatExpirationText = function (EmployeeDocument $document) use ($dayLabel): string {
        if (! $document->expires_at) {
            return 'Без срока';
        }

        $date = Carbon::parse($document->expires_at)->startOfDay();
        $today = today();

        if ($date->isBefore($today)) {
            $days = (int) $date->diffInDays($today);

            return $days > 0 ? 'Просрочен на '.$days.' '.$dayLabel($days) : 'Просрочен';
        }

        $days = (int) $today->diffInDays($date);

        if ($days === 0) {
            return 'Истекает сегодня';
        }

        return $days <= 30 ? 'Истекает через '.$days.' '.$dayLabel($days) : 'Действует';
    };

    $documentClasses = function (?EmployeeDocument $document, bool $isMissing): string {
        if ($document?->isExpired()) {
            return 'border-red-200 bg-red-50 text-red-900 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-100';
        }

        if ($document?->expiresSoon()) {
            return 'border-yellow-200 bg-yellow-50 text-yellow-900 dark:border-yellow-500/30 dark:bg-yellow-500/10 dark:text-yellow-100';
        }

        if ($document) {
            return 'border-green-200 bg-green-50 text-green-900 dark:border-green-500/30 dark:bg-green-500/10 dark:text-green-100';
        }

        if ($isMissing) {
            return 'border-red-200 bg-red-50 text-red-900 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-100';
        }

        return 'border-gray-200 bg-gray-50 text-gray-900 dark:border-white/10 dark:bg-white/5 dark:text-gray-100';
    };

    $statusClasses = function (?EmployeeDocument $document, bool $isMissing): string {
        if ($document?->isExpired()) {
            return 'bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-300';
        }

        if ($document?->expiresSoon()) {
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-500/20 dark:text-yellow-300';
        }

        if ($document) {
            return 'bg-green-100 text-green-700 dark:bg-green-500/20 dark:text-green-300';
        }

        if ($isMissing) {
            return 'bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-300';
        }

        return 'bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-gray-300';
    };
@endphp

<div class="mb-4 space-y-4">
    <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">Контроль документов</h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Комплектность и сроки документов сотрудника.</p>
            </div>

            <span @class([
                'inline-flex w-fit rounded-md px-2.5 py-1 text-xs font-medium ring-1',
                'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-500/10 dark:text-green-400' => $missing === [] && $expiring->isEmpty(),
                'bg-yellow-50 text-yellow-800 ring-yellow-600/20 dark:bg-yellow-500/10 dark:text-yellow-300' => $missing === [] && $expiring->isNotEmpty(),
                'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-500/10 dark:text-red-400' => $missing !== [],
            ])>
                {{ \App\Filament\Resources\AdminUsers\UserResource::documentStatusLabel($employee) }}
            </span>
        </div>

        <div class="mt-4 grid gap-3 lg:grid-cols-2">
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <div class="text-sm font-medium text-gray-950 dark:text-white">Комплектность</div>
                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">Обязательные документы</div>
                    </div>
                    <div class="text-2xl font-semibold text-gray-950 dark:text-white">{{ $percent }}%</div>
                </div>

                <div class="mt-4 h-2 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-800">
                    <div @class([
                        'h-2 rounded-full',
                        'bg-green-500' => $percent >= 100,
                        'bg-yellow-500' => $percent > 0 && $percent < 100,
                        'bg-red-500' => $percent === 0,
                    ]) style="width: {{ $percent }}%"></div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                @foreach ([
                    ['label' => 'Загружено', 'value' => $activeDocuments->count(), 'class' => 'text-green-700 dark:text-green-400'],
                    ['label' => 'Не хватает', 'value' => count($missing), 'class' => count($missing) > 0 ? 'text-red-700 dark:text-red-400' : 'text-gray-700 dark:text-gray-300'],
                    ['label' => 'Истекают', 'value' => $expiring->count(), 'class' => $expiring->isNotEmpty() ? 'text-yellow-800 dark:text-yellow-300' : 'text-gray-700 dark:text-gray-300'],
                    ['label' => 'Просрочены', 'value' => $expiredCount, 'class' => $expiredCount > 0 ? 'text-red-700 dark:text-red-400' : 'text-gray-700 dark:text-gray-300'],
                ] as $stat)
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-white/10 dark:bg-white/5">
                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</div>
                        <div class="mt-1 text-xl font-semibold {{ $stat['class'] }}">{{ $stat['value'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    @if ($missing !== [])
        <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Не хватает документов</h3>

            <div class="mt-3 grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($missing as $category)
                    <div class="rounded-lg border border-red-200 bg-red-50 p-3 dark:border-red-500/30 dark:bg-red-500/10">
                        <div class="flex items-start gap-2">
                            <svg class="mt-0.5 h-5 w-5 shrink-0 text-red-600 dark:text-red-300" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16ZM8.28 7.22a.75.75 0 0 0-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 1 0 1.06 1.06L10 11.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L11.06 10l1.72-1.72a.75.75 0 0 0-1.06-1.06L10 8.94 8.28 7.22Z" clip-rule="evenodd" />
                            </svg>
                            <div class="min-w-0">
                                <div class="text-sm font-medium text-red-900 dark:text-red-100">{{ EmployeeRequiredDocuments::label($category) }}</div>
                                <div class="mt-1 text-xs text-red-700 dark:text-red-300">Не загружен</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">
        <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Чеклист документов</h3>

        <div class="mt-4 space-y-5">
            @foreach ($groups as $groupLabel => $categories)
                @php
                    $visibleCategories = collect($categories)
                        ->filter(fn (string $category): bool => $relevantCategories->contains($category))
                        ->values();
                @endphp

                @if ($visibleCategories->isNotEmpty())
                    <div>
                        <div class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $groupLabel }}</div>
                        <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                            @foreach ($visibleCategories as $category)
                                @php
                                    $document = $documentsByCategory->get($category);
                                    $isRequired = in_array($category, $required, true);
                                    $isMissing = $isRequired && ! $document;
                                @endphp

                                <div class="rounded-lg border p-3 {{ $documentClasses($document, $isMissing) }}">
                                    <div class="flex items-start gap-2">
                                        <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full {{ $statusClasses($document, $isMissing) }}">
                                            @if ($document)
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.25 7.31a1 1 0 0 1-1.42.002L3.29 9.266a1 1 0 1 1 1.414-1.414l4.04 4.04 6.54-6.596a1 1 0 0 1 1.42-.006Z" clip-rule="evenodd" />
                                                </svg>
                                            @elseif ($isMissing)
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16ZM8.28 7.22a.75.75 0 0 0-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 1 0 1.06 1.06L10 11.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L11.06 10l1.72-1.72a.75.75 0 0 0-1.06-1.06L10 8.94 8.28 7.22Z" clip-rule="evenodd" />
                                                </svg>
                                            @else
                                                <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                            @endif
                                        </span>

                                        <div class="min-w-0">
                                            <div class="text-sm font-medium">{{ EmployeeRequiredDocuments::label($category) }}</div>
                                            <div class="mt-1 text-xs opacity-75">
                                                @if ($document)
                                                    {{ $document->expires_at ? $formatExpirationText($document) : 'Загружен' }}
                                                @elseif ($isMissing)
                                                    Не загружен
                                                @else
                                                    Не обязателен
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach

            @if ($relevantCategories->isEmpty())
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600 dark:border-white/10 dark:bg-white/5 dark:text-gray-400">
                    Для этого сотрудника пока нет релевантных документов.
                </div>
            @endif
        </div>
    </section>

    <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">
        <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Контроль сроков</h3>

        @if ($expiring->isEmpty())
            <div class="mt-3 rounded-lg border border-gray-200 bg-gray-50 p-3 text-sm text-gray-600 dark:border-white/10 dark:bg-white/5 dark:text-gray-400">
                Нет документов с ближайшим истечением срока.
            </div>
        @else
            <div class="mt-3 grid gap-2 md:grid-cols-2">
                @foreach ($expiring as $document)
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-white/10 dark:bg-white/5">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <div class="text-sm font-medium text-gray-950 dark:text-white">{{ $document->getCategoryLabel() }}</div>
                                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    {{ $document->expires_at?->format('d.m.Y') }}
                                </div>
                            </div>
                            <span @class([
                                'inline-flex w-fit rounded-md px-2 py-1 text-xs font-medium ring-1',
                                'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-500/10 dark:text-red-400' => $document->isExpired(),
                                'bg-yellow-50 text-yellow-800 ring-yellow-600/20 dark:bg-yellow-500/10 dark:text-yellow-300' => ! $document->isExpired(),
                            ])>
                                {{ $formatExpirationText($document) }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</div>
