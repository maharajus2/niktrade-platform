<?php

namespace App\Filament\Resources\AdminUsers\Pages;

use App\Filament\Resources\AdminUsers\UserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected static ?string $title = 'Сотрудники';

    protected static ?string $breadcrumb = 'Сотрудники';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn (): bool => UserResource::canCreate()),
        ];
    }
}
