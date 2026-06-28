<?php

namespace App\Filament\Resources\Departments;

use App\Filament\Resources\Departments\Pages\CreateDepartment;
use App\Filament\Resources\Departments\Pages\EditDepartment;
use App\Filament\Resources\Departments\Pages\ListDepartments;
use App\Filament\Resources\Departments\Schemas\DepartmentForm;
use App\Filament\Resources\Departments\Tables\DepartmentsTable;
use App\Models\Department;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;

    protected static ?string $navigationLabel = 'Отделы';

    protected static ?string $modelLabel = 'Отдел';

    protected static ?string $pluralModelLabel = 'Отделы';

    protected static string|\UnitEnum|null $navigationGroup = 'Система';

    protected static ?int $navigationSort = 110;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    public static function form(Schema $schema): Schema
    {
        return DepartmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DepartmentsTable::configure($table);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function canAccess(): bool
    {
        return static::canViewAny();
    }

    public static function canViewAny(): bool
    {
        return static::canUseDepartmentPermission('departments.view_any');
    }

    public static function canCreate(): bool
    {
        return static::canUseDepartmentPermission('departments.create');
    }

    public static function canEdit(Model $record): bool
    {
        return static::canUseDepartmentPermission('departments.update');
    }

    public static function canDelete(Model $record): bool
    {
        return static::canUseDepartmentPermission('departments.delete');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDepartments::route('/'),
            'create' => CreateDepartment::route('/create'),
            'edit' => EditDepartment::route('/{record}/edit'),
        ];
    }

    public static function canUseDepartmentPermission(string $permission): bool
    {
        try {
            $user = auth()->user();

            return $user !== null && ($user->hasRole('super_admin') || $user->can($permission));
        } catch (Throwable) {
            return false;
        }
    }
}
