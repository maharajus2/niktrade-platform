<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected string $view = 'filament.resources.orders.pages.view-order';

    protected Width|string|null $maxContentWidth = Width::Full;

    public function getTitle(): string|Htmlable
    {
        return 'Заказ ' . $this->record->order_number;
    }

    public function getHeading(): string|Htmlable|null
    {
        return null;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function archiveOrder(): void
    {
        if (! $this->record instanceof Order || ! $this->record->canBeArchived()) {
            Notification::make()->title('Заказ нельзя отправить в архив.')->danger()->send();

            return;
        }

        $this->record->update(['archived_at' => now()]);
        $this->record->refresh();

        Notification::make()->title('Заказ отправлен в архив.')->success()->send();
    }

    public function unarchiveOrder(): void
    {
        if (! $this->record instanceof Order || ! $this->record->isArchived()) {
            Notification::make()->title('Заказ не находится в архиве.')->danger()->send();

            return;
        }

        $this->record->update(['archived_at' => null]);
        $this->record->refresh();

        Notification::make()->title('Заказ возвращён из архива.')->success()->send();
    }
}
