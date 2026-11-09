<?php

namespace App\Filament\Resources\BusinessRequests\Pages;

use App\Filament\Resources\BusinessRequests\BusinessRequestResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewBusinessRequest extends ViewRecord
{
    protected static string $resource = BusinessRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make()];
    }
}
