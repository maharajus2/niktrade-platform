@php
    use App\Models\EmployeeDocument;

    $formatDays = function (int $days): string {
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

    $expirationText = function (EmployeeDocument $document) use ($formatDays): string {
        if (! $document->expires_at) {
            return 'Без срока';
        }

        $today = today();
        $expiresAt = $document->expires_at->copy()->startOfDay();

        if ($expiresAt->isBefore($today)) {
            $days = (int) $expiresAt->diffInDays($today);

            return 'Просрочен на '.$days.' '.$formatDays($days);
        }

        $days = (int) $today->diffInDays($expiresAt);

        if ($days === 0) {
            return 'Истекает сегодня';
        }

        return $days <= 30
            ? 'Истекает через '.$days.' '.$formatDays($days)
            : 'Действует';
    };
@endphp

<div style="display: grid; gap: 16px; max-width: 100%;">
    <section style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05); padding: 16px;">
        <h3 style="color: #111827; font-size: 16px; font-weight: 600; line-height: 1.4; margin: 0;">Контроль документов</h3>

        <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; margin-top: 14px; padding: 14px;">
            <div style="align-items: center; display: flex; gap: 12px; justify-content: space-between;">
                <div style="color: #111827; font-size: 14px; font-weight: 500;">Комплектность</div>
                <div style="color: #111827; font-size: 20px; font-weight: 700;">{{ $completenessPercent }}%</div>
            </div>

            <div style="background: #e5e7eb; border-radius: 999px; height: 8px; margin-top: 12px; overflow: hidden;">
                <div style="background: {{ $completenessPercent >= 100 ? '#22c55e' : ($completenessPercent > 0 ? '#eab308' : '#ef4444') }}; border-radius: 999px; height: 8px; width: {{ $completenessPercent }}%;"></div>
            </div>
        </div>

        <div style="display: grid; gap: 10px; grid-template-columns: repeat(2, minmax(0, 1fr)); margin-top: 12px;">
            @foreach ([
                ['label' => 'Загружено', 'value' => $loadedCount, 'color' => '#15803d'],
                ['label' => 'Не хватает', 'value' => $missingCount, 'color' => $missingCount > 0 ? '#b91c1c' : '#374151'],
                ['label' => 'Истекают', 'value' => $expiringCount, 'color' => $expiringCount > 0 ? '#a16207' : '#374151'],
                ['label' => 'Просрочены', 'value' => $expiredCount, 'color' => $expiredCount > 0 ? '#b91c1c' : '#374151'],
            ] as $stat)
                <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; min-width: 0; padding: 12px;">
                    <div style="color: #6b7280; font-size: 12px; line-height: 1.3;">{{ $stat['label'] }}</div>
                    <div style="color: {{ $stat['color'] }}; font-size: 20px; font-weight: 700; line-height: 1.2; margin-top: 4px;">{{ $stat['value'] }}</div>
                </div>
            @endforeach
        </div>
    </section>

    @if ($missingDocumentLabels !== [])
        <section style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05); padding: 16px;">
            <h3 style="color: #111827; font-size: 14px; font-weight: 600; margin: 0;">Не хватает документов</h3>

            <div style="display: grid; gap: 10px; margin-top: 12px;">
                @foreach ($missingDocumentLabels as $label)
                    <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; min-width: 0; padding: 12px;">
                        <div style="color: #7f1d1d; font-size: 14px; font-weight: 600; line-height: 1.35;">{{ $label }}</div>
                        <div style="color: #b91c1c; font-size: 12px; line-height: 1.3; margin-top: 4px;">Не загружен</div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if ($checklistGroups !== [])
        <section style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05); padding: 16px;">
            <h3 style="color: #111827; font-size: 14px; font-weight: 600; margin: 0;">Чеклист документов</h3>

            <div style="display: grid; gap: 16px; margin-top: 12px;">
                @foreach ($checklistGroups as $group)
                    @if ($group['items'] !== [])
                        <div>
                            <div style="color: #6b7280; font-size: 12px; font-weight: 600; letter-spacing: .04em; margin-bottom: 8px; text-transform: uppercase;">{{ $group['label'] }}</div>

                            <div style="display: grid; gap: 8px;">
                                @foreach ($group['items'] as $item)
                                    @php
                                        $isBad = in_array($item['state'], ['missing', 'expired'], true);
                                        $isWarning = $item['state'] === 'warning';
                                        $isGood = $item['state'] === 'uploaded';
                                        $border = $isBad ? '#fecaca' : ($isWarning ? '#fde68a' : ($isGood ? '#bbf7d0' : '#e5e7eb'));
                                        $background = $isBad ? '#fef2f2' : ($isWarning ? '#fffbeb' : ($isGood ? '#f0fdf4' : '#f9fafb'));
                                        $textColor = $isBad ? '#7f1d1d' : ($isWarning ? '#78350f' : ($isGood ? '#14532d' : '#111827'));
                                    @endphp

                                    <div style="background: {{ $background }}; border: 1px solid {{ $border }}; border-radius: 10px; min-width: 0; padding: 12px;">
                                        <div style="color: {{ $textColor }}; font-size: 14px; font-weight: 600; line-height: 1.35;">{{ $item['label'] }}</div>
                                        <div style="color: {{ $textColor }}; font-size: 12px; line-height: 1.3; margin-top: 4px; opacity: .75;">{{ $item['description'] }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </section>
    @endif

    <section style="background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05); padding: 16px;">
        <h3 style="color: #111827; font-size: 14px; font-weight: 600; margin: 0;">Контроль сроков</h3>

        @if ($expiringDocuments->isEmpty())
            <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; color: #4b5563; font-size: 14px; line-height: 1.4; margin-top: 12px; padding: 12px;">
                Нет документов с ближайшим истечением срока.
            </div>
        @else
            <div style="display: grid; gap: 10px; margin-top: 12px;">
                @foreach ($expiringDocuments as $document)
                    <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; min-width: 0; padding: 12px;">
                        <div style="color: #111827; font-size: 14px; font-weight: 600; line-height: 1.35;">{{ $document->getCategoryLabel() }}</div>
                        <div style="color: #6b7280; font-size: 12px; line-height: 1.3; margin-top: 4px;">{{ $document->expires_at?->format('d.m.Y') }}</div>
                        <div style="color: {{ $document->isExpired() ? '#b91c1c' : '#a16207' }}; font-size: 12px; font-weight: 600; line-height: 1.3; margin-top: 6px;">{{ $expirationText($document) }}</div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</div>
