<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('archive')
                ->label('Отправить в архив')
                ->requiresConfirmation()
                ->modalDescription('Отправить заказ в архив?')
                ->visible(fn (): bool => $this->record instanceof Order && $this->record->canBeArchived())
                ->action(fn (): bool => $this->record->update(['archived_at' => now()])),
            Action::make('unarchive')
                ->label('Вернуть из архива')
                ->requiresConfirmation()
                ->modalDescription('Вернуть заказ из архива?')
                ->visible(fn (): bool => $this->record instanceof Order && $this->record->isArchived())
                ->action(fn (): bool => $this->record->update(['archived_at' => null])),
            DeleteAction::make()
                ->label('Удалить заказ')
                ->requiresConfirmation()
                ->modalDescription('Вы уверены, что хотите удалить этот заказ? Это действие нельзя отменить.'),
        ];
    }
}
