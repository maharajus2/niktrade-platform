<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Удалить заказ')
                ->requiresConfirmation()
                ->modalDescription('Вы уверены, что хотите удалить этот заказ? Это действие нельзя отменить.'),
        ];
    }
}
