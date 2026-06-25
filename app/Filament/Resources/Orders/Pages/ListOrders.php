<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

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
