<?php

namespace App\Filament\Widgets\Employee;

use App\Models\EmployeeDocument;
use App\Models\User;
use Filament\Widgets\Widget;

class EmployeeDocumentsWidget extends Widget
{
    protected string $view = 'filament.widgets.employee.documents-widget';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'xl' => 2,
    ];

    public static function canView(): bool
    {
        return auth()->user() instanceof User;
    }

    protected function getViewData(): array
    {
        /** @var User $employee */
        $employee = auth()->user();
        $expiring = $employee->expiringDocuments();

        return [
            'completeness' => $employee->documentCompletenessPercent(),
            'missingCount' => count($employee->missingRequiredDocuments()),
            'expiringCount' => $expiring->filter(fn (EmployeeDocument $document): bool => ! $document->isExpired())->count(),
            'expiredCount' => $expiring->filter(fn (EmployeeDocument $document): bool => $document->isExpired())->count(),
        ];
    }
}
