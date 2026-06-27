<?php

namespace App\Filament\Resources\AdminUsers\Pages;

use App\Filament\Resources\AdminUsers\UserResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected static ?string $title = 'Просмотр сотрудника';

    protected static ?string $breadcrumb = 'Просмотр сотрудника';

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn (): bool => UserResource::canEdit($this->record)),
        ];
    }
}
