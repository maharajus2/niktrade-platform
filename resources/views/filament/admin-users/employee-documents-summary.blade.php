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
            return $document->getExpirationLabel();
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

    $expirationClasses = function (EmployeeDocument $document): string {
        return match ($document->getExpirationState()) {
            EmployeeDocument::EXPIRATION_VALID => 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-500/10 dark:text-green-400',
            EmployeeDocument::EXPIRATION_WARNING_30,
            EmployeeDocument::EXPIRATION_WARNING_14,
            EmployeeDocument::EXPIRATION_WARNING_7,
            EmployeeDocument::EXPIRATION_WARNING_1 => 'bg-yellow-50 text-yellow-800 ring-yellow-600/20 dark:bg-yellow-500/10 dark:text-yellow-300',
            EmployeeDocument::EXPIRATION_EXPIRED => 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-500/10 dark:text-red-400',
            default => 'bg-gray-50 text-gray-600 ring-gray-500/10 dark:bg-white/5 dark:text-gray-300',
        };
    };
@endphp

<div class="mb-5 space-y-5">
    <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900 sm:p-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Обзор документов</p>
                <h3 class="mt-1 text-base font-semibold text-gray-950 dark:text-white">Контроль документов</h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Комплектность документов сотрудника и контроль ближайших сроков.</p>
            </div>

            <span @class([
                'inline-flex w-fit items-center rounded-md px-2.5 py-1 text-xs font-medium ring-1',
                'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-500/10 dark:text-green-400' => $missing === [] && $expiring->isEmpty(),
                'bg-yellow-50 text-yellow-800 ring-yellow-600/20 dark:bg-yellow-500/10 dark:text-yellow-300' => $missing === [] && $expiring->isNotEmpty(),
                'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-500/10 dark:text-red-400' => $missing !== [],
            ])>
                {{ \App\Filament\Resources\AdminUsers\UserResource::documentStatusLabel($employee) }}
            </span>
        </div>

        <div class="mt-5 grid gap-4 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)]">
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                <div class="flex items-end justify-between gap-3">
                    <div>
                        <div class="text-sm font-medium text-gray-950 dark:text-white">Комплектность документов</div>
                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">По обязательному списку для сотрудника</div>
                    </div>
                    <div class="text-2xl font-semibold text-gray-950 dark:text-white">{{ $percent }}%</div>
                </div>

                <div class="mt-4 h-2 rounded-full bg-gray-200 dark:bg-gray-800">
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

    <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900 sm:p-5">
        <div class="flex flex-col gap-1">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Чеклист</p>
            <h3 class="text-base font-semibold text-gray-950 dark:text-white">Обязательные и релевантные документы</h3>
        </div>

        <div class="mt-4 space-y-5">
            @foreach ($groups as $groupLabel => $categories)
                @php
                    $visibleCategories = collect($categories)
                        ->filter(fn (string $category): bool => $relevantCategories->contains($category))
                        ->values();
                @endphp

                @if ($visibleCategories->isNotEmpty())
                    <div>
                        <div class="mb-2 text-sm font-medium text-gray-950 dark:text-white">{{ $groupLabel }}</div>
                        <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                            @foreach ($visibleCategories as $category)
                                @php
                                    $document = $documentsByCategory->get($category);
                                    $isRequired = in_array($category, $required, true);
                                    $isMissing = $isRequired && ! $document;
                                @endphp

                                <div @class([
                                    'rounded-lg border p-3',
                                    'border-green-200 bg-green-50/70 dark:border-green-500/30 dark:bg-green-500/10' => $document,
                                    'border-red-200 bg-red-50/70 dark:border-red-500/30 dark:bg-red-500/10' => $isMissing,
                                    'border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5' => ! $document && ! $isMissing,
                                ])>
                                    <div class="flex items-start gap-2">
                                        <span @class([
                                            'mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full ring-1',
                                            'bg-green-100 text-green-700 ring-green-600/20 dark:bg-green-500/20 dark:text-green-300' => $document,
                                            'bg-red-100 text-red-700 ring-red-600/20 dark:bg-red-500/20 dark:text-red-300' => $isMissing,
                                            'bg-gray-100 text-gray-500 ring-gray-500/10 dark:bg-white/10 dark:text-gray-300' => ! $document && ! $isMissing,
                                        ])>
                                            @if ($document)
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.25 7.31a1 1 0 0 1-1.42.002L3.29 9.266a1 1 0 1 1 1.414-1.414l4.04 4.04 6.54-6.596a1 1 0 0 1 1.42-.006Z" clip-rule="evenodd" />
                                                </svg>
                                            @elseif ($isMissing)
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path fill-rule="evenodd" d="M10 8.586 14.95 3.636a1 1 0 1 1 1.414 1.414L11.414 10l4.95 4.95a1 1 0 0 1-1.414 1.414L10 11.414l-4.95 4.95a1 1 0 0 1-1.414-1.414L8.586 10l-4.95-4.95A1 1 0 0 1 5.05 3.636L10 8.586Z" clip-rule="evenodd" />
                                                </svg>
                                            @else
                                                <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                            @endif
                                        </span>

                                        <div class="min-w-0">
                                            <div class="text-sm font-medium text-gray-950 dark:text-white">{{ EmployeeRequiredDocuments::label($category) }}</div>
                                            <div class="mt-1 text-xs text-gray-600 dark:text-gray-400">
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

    <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900 sm:p-5">
        <div class="flex flex-col gap-1">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Контроль сроков</p>
            <h3 class="text-base font-semibold text-gray-950 dark:text-white">Документы с ближайшим истечением</h3>
        </div>

        @if ($expiring->isEmpty())
            <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600 dark:border-white/10 dark:bg-white/5 dark:text-gray-400">
                Нет документов с ближайшим истечением срока.
            </div>
        @else
            <div class="mt-4 grid gap-2 md:grid-cols-2">
                @foreach ($expiring as $document)
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-white/10 dark:bg-white/5">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <div class="text-sm font-medium text-gray-950 dark:text-white">{{ $document->getCategoryLabel() }}</div>
                                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $document->title }}</div>
                            </div>
                            <span class="inline-flex w-fit items-center rounded-md px-2 py-1 text-xs font-medium ring-1 {{ $expirationClasses($document) }}">
                                {{ $formatExpirationText($document) }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <section>
        <div class="mb-3">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Реестр документов</p>
            <h3 class="text-base font-semibold text-gray-950 dark:text-white">Загруженные файлы</h3>
        </div>
    </section>
</div>
