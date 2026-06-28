<?php

namespace App\Filament\Resources\AdminUsers;

use App\Filament\Resources\AdminUsers\Pages\CreateUser;
use App\Filament\Resources\AdminUsers\Pages\EditUser;
use App\Filament\Resources\AdminUsers\Pages\ListUsers;
use App\Filament\Resources\AdminUsers\Pages\ViewUser;
use App\Filament\Resources\AdminUsers\Schemas\UserForm;
use App\Filament\Resources\AdminUsers\Tables\UsersTable;
use App\Models\User;
use App\Support\AdminRoles;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationLabel = 'Сотрудники';

    protected static ?string $modelLabel = 'Сотрудник';

    protected static ?string $pluralModelLabel = 'Сотрудники';

    protected static string|\UnitEnum|null $navigationGroup = 'Система';

    protected static ?int $navigationSort = 100;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Основная информация')
                    ->schema([
                        ImageEntry::make('avatar_path')
                            ->label('Фото')
                            ->disk('public')
                            ->circular(),

                        TextEntry::make('name')
                            ->label('ФИО'),

                        TextEntry::make('email')
                            ->label('Email'),

                        TextEntry::make('phone')
                            ->label('Телефон')
                            ->placeholder('—'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('Роль и доступ')
                    ->icon(fn (User $record): Heroicon => AdminRoles::primaryIcon($record))
                    ->iconColor(fn (User $record): string => AdminRoles::primaryColor($record))
                    ->description(fn (User $record): string => AdminRoles::primaryDescription($record))
                    ->schema([
                        TextEntry::make('roles.name')
                            ->label('Назначенные роли')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => AdminRoles::label($state))
                            ->icon(fn (string $state): Heroicon => AdminRoles::icon($state))
                            ->color(fn (string $state): string => AdminRoles::color($state))
                            ->placeholder('Роль не назначена'),

                        TextEntry::make('manager.name')
                            ->label('Руководитель')
                            ->placeholder('—'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('HR')
                    ->visible(fn (): bool => static::canUpdateHrProfile())
                    ->schema([
                        TextEntry::make('employment_type')
                            ->label('Тип трудоустройства')
                            ->badge()
                            ->formatStateUsing(fn (?string $state, User $record): string => $record->getEmploymentTypeLabel()),

                        TextEntry::make('employment_status')
                            ->label('Статус')
                            ->badge()
                            ->formatStateUsing(fn (?string $state, User $record): string => $record->getEmploymentStatusLabel())
                            ->color(fn (?string $state, User $record): string => User::employeeStatusColor($record->employment_status ?? $record->employee_status)),

                        TextEntry::make('hire_date')
                            ->label('Дата найма')
                            ->date('d.m.Y')
                            ->placeholder('—'),

                        TextEntry::make('dismissal_date')
                            ->label('Дата увольнения')
                            ->date('d.m.Y')
                            ->placeholder('—')
                            ->visible(fn (User $record): bool => ($record->employment_status ?? $record->employee_status) === User::STATUS_DISMISSED),

                        TextEntry::make('schedule_type')
                            ->label('График')
                            ->formatStateUsing(fn (?string $state, User $record): string => $record->getScheduleTypeLabel()),

                        TextEntry::make('tenure')
                            ->label('Стаж')
                            ->state(fn (User $record): string => $record->getTenureLabel()),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),

                Section::make('Гражданство и миграционный статус')
                    ->visible(fn (User $record): bool => static::canViewCitizenshipProfile($record))
                    ->schema([
                        TextEntry::make('citizenship_type')
                            ->label('Гражданство')
                            ->badge()
                            ->formatStateUsing(fn (?string $state, User $record): string => $record->getCitizenshipTypeLabel()),

                        TextEntry::make('citizenship_country')
                            ->label('Страна гражданства')
                            ->placeholder('—')
                            ->visible(fn (User $record): bool => $record->requiresMigrationProfile()),

                        TextEntry::make('arrival_country')
                            ->label('Страна прибытия')
                            ->placeholder('—')
                            ->visible(fn (User $record): bool => $record->requiresMigrationProfile()),

                        TextEntry::make('arrived_at')
                            ->label('Дата прибытия в РФ')
                            ->date('d.m.Y')
                            ->placeholder('—')
                            ->visible(fn (User $record): bool => $record->requiresMigrationProfile()),

                        TextEntry::make('foreign_legal_status')
                            ->label('Миграционный статус')
                            ->badge()
                            ->formatStateUsing(fn (?string $state, User $record): string => $record->getForeignLegalStatusLabel())
                            ->visible(fn (User $record): bool => $record->requiresMigrationProfile()),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('Испытательный срок')
                    ->visible(fn (): bool => static::canManageProbation())
                    ->schema([
                        TextEntry::make('probation')
                            ->label('Статус')
                            ->badge()
                            ->color(fn (User $record): string => $record->isOnProbation() ? 'warning' : 'gray')
                            ->state(fn (User $record): string => $record->getProbationLabel()),

                        TextEntry::make('probation_started_at')
                            ->label('Дата начала')
                            ->date('d.m.Y')
                            ->placeholder('—'),

                        TextEntry::make('probation_ends_at')
                            ->label('Дата окончания')
                            ->date('d.m.Y')
                            ->placeholder('—'),

                        TextEntry::make('probationCancelledBy.name')
                            ->label('Кем отменён')
                            ->placeholder('—'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('Оклад')
                    ->visible(fn (User $record): bool => static::canViewSalary($record))
                    ->schema([
                        TextEntry::make('salary_amount')
                            ->label('Оклад')
                            ->money(fn (User $record): string => $record->salary_currency ?: 'RUB')
                            ->placeholder('—'),

                        TextEntry::make('salary_currency')
                            ->label('Валюта')
                            ->placeholder('RUB'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('Контроль документов')
                    ->visible(fn (User $record): bool => static::canViewCitizenshipProfile($record))
                    ->schema([
                        TextEntry::make('document_control_placeholder')
                            ->hiddenLabel()
                            ->state('Контроль сроков документов будет доступен после подключения модуля документов.'),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canManageAdminUsers();
    }

    public static function canAccess(): bool
    {
        return static::canManageAdminUsers();
    }

    public static function canViewAny(): bool
    {
        return static::canUseAnyPermission(['users.view_any', 'employees.view_any']);
    }

    public static function canCreate(): bool
    {
        return static::canUseAnyPermission(['users.create', 'employees.create']);
    }

    public static function canEdit(Model $record): bool
    {
        return static::canUseAnyPermission(['users.update', 'employees.update']);
    }

    public static function canView(Model $record): bool
    {
        return static::canUseAnyPermission(['users.view', 'employees.view']);
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canUpdateHrProfile(): bool
    {
        return static::canUseAdminPermission('employees.hr.update');
    }

    public static function canViewCitizenshipProfile(User $record): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        return $record->is($user) || static::canUseAnyPermission(['employees.hr.view', 'employees.hr.update']);
    }

    public static function canManageProbation(): bool
    {
        return static::canUseAdminPermission('employees.probation.manage');
    }

    public static function canUpdateSalary(): bool
    {
        return static::canUseAdminPermission('employees.salary.update');
    }

    public static function canViewSalary(?User $record): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        if ($user->hasRole('super_admin') || $user->hasRole('hr') || $user->can('employees.salary.view')) {
            return true;
        }

        if ($record === null) {
            return static::canUpdateSalary();
        }

        return $record->is($user) || (int) $record->manager_id === (int) $user->getKey();
    }

    public static function canArchiveEmployee(User $record): bool
    {
        return $record->canBeArchived() && static::canUseAdminPermission('employees.archive');
    }

    public static function archiveAction(): Action
    {
        return Action::make('archive')
            ->label('Архивировать сотрудника')
            ->icon(Heroicon::OutlinedArchiveBox)
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading('Архивировать сотрудника?')
            ->modalDescription('Сотрудник будет скрыт из списка по умолчанию. Вход в админку на этом этапе не блокируется.')
            ->modalSubmitActionLabel('Архивировать')
            ->visible(fn (User $record): bool => static::canArchiveEmployee($record))
            ->action(function (User $record): void {
                $record->update([
                    'archived_at' => now(),
                    'employment_status' => User::STATUS_ARCHIVED,
                    'employee_status' => User::STATUS_ARCHIVED,
                ]);

                Notification::make()
                    ->title('Сотрудник архивирован.')
                    ->success()
                    ->send();
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'view' => ViewUser::route('/{record}'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    public static function canManageAdminUsers(): bool
    {
        return static::canUseAnyPermission(['users.view_any', 'employees.view_any']);
    }

    public static function canUseAdminPermission(string $permission): bool
    {
        return static::canUseAnyPermission([$permission]);
    }

    public static function canUseAnyPermission(array $permissions): bool
    {
        try {
            $user = auth()->user();

            return $user !== null
                && ($user->hasRole('super_admin') || collect($permissions)->contains(fn (string $permission): bool => $user->can($permission)));
        } catch (Throwable) {
            return false;
        }
    }
}
