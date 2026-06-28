<?php

namespace App\Filament\Resources\AdminUsers\Pages;

use App\Filament\Resources\AdminUsers\UserResource;
use App\Models\User;
use App\Support\EmployeeRequiredDocuments;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected static ?string $title = 'Редактирование сотрудника';

    protected static ?string $breadcrumb = 'Редактирование сотрудника';

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            UserResource::archiveAction(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (
            ($data['employment_status'] ?? null) === User::STATUS_WORKING
            && ($this->record->employment_status ?? $this->record->employee_status) !== User::STATUS_WORKING
        ) {
            $missing = $this->record->missingRequiredDocuments();

            if ($missing !== []) {
                throw ValidationException::withMessages([
                    'data.employment_status' => "Невозможно перевести сотрудника в статус \"Работает\".\nОтсутствуют обязательные документы:\n".collect($missing)
                        ->map(fn (string $category): string => '• '.EmployeeRequiredDocuments::label($category))
                        ->implode("\n"),
                ]);
            }
        }

        return $data;
    }
}
