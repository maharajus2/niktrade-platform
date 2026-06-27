<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('kanban')
                ->label('Доска заказов')
                ->url(OrderResource::getUrl('kanban')),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'active';
    }

    public function getTabs(): array
    {
        return [
            'active' => Tab::make('Активные')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereNull('archived_at')),
            'archive' => Tab::make('Архив')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereNotNull('archived_at')),
            'all' => Tab::make('Все'),
        ];
    }
}
