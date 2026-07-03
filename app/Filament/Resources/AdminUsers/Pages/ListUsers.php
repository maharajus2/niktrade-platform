<?php

namespace App\Filament\Resources\AdminUsers\Pages;

use App\Filament\Resources\AdminUsers\UserResource;
use App\Models\Department;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Components\View as SchemaView;
use Filament\Schemas\Schema;
use Filament\View\PanelsRenderHook;

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

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                SchemaView::make('filament.resources.admin-users.components.employee-department-navigation')
                    ->viewData(fn (): array => [
                        'departments' => Department::query()
                            ->where('is_active', true)
                            ->orderBy('sort_order')
                            ->orderBy('name')
                            ->get(['id', 'name']),
                        'selectedDepartmentId' => $this->tableFilters['department_id']['value'] ?? null,
                        'managementSelected' => (bool) ($this->tableFilters['management']['isActive'] ?? false),
                        'baseUrl' => UserResource::getUrl('index'),
                    ])
                    ->columnSpanFull(),

                $this->getTabsContentComponent(),
                RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE),
                EmbeddedTable::make(),
                RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_AFTER),
            ]);
    }
}
