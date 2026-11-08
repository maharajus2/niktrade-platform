<?php

namespace App\Filament\Resources\Contractors;

use App\Filament\Resources\Concerns\UsesResourcePermissions;
use App\Filament\Resources\Contractors\Pages\CreateContractor;
use App\Filament\Resources\Contractors\Pages\EditContractor;
use App\Filament\Resources\Contractors\Pages\ListContractors;
use App\Filament\Resources\Contractors\Schemas\ContractorForm;
use App\Filament\Resources\Contractors\Tables\ContractorsTable;
use App\Models\Contractor;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ContractorResource extends Resource
{
    use UsesResourcePermissions;

    protected static string $permissionPrefix = 'contractors';

    protected static ?string $model = Contractor::class;

    protected static ?string $navigationLabel = 'Контрагенты';

    protected static ?string $modelLabel = 'Контрагент';

    protected static ?string $pluralModelLabel = 'Контрагенты';

    protected static string|\UnitEnum|null $navigationGroup = 'Продажи';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice;

    public static function form(Schema $schema): Schema
    {
        return ContractorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContractorsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('manager');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContractors::route('/'),
            'create' => CreateContractor::route('/create'),
            'edit' => EditContractor::route('/{record}/edit'),
        ];
    }
}
