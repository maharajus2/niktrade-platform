<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('createFromExisting')
                ->label('Создать на основе')
                ->icon(Heroicon::OutlinedDocumentDuplicate)
                ->url(fn (): string => ProductResource::getUrl('create', ['source' => $this->record]))
                ->visible(fn (): bool => ProductResource::canCreate()),
        ];
    }
}
