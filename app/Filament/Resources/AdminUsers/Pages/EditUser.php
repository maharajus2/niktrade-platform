<?php

namespace App\Filament\Resources\AdminUsers\Pages;

use App\Filament\Resources\AdminUsers\UserResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

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
}
