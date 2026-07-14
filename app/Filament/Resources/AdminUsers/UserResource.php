<?php

namespace App\Filament\Resources\AdminUsers;

use App\Filament\Resources\AdminUsers\Pages\CreateUser;
use App\Filament\Resources\AdminUsers\Pages\EditUser;
use App\Filament\Resources\AdminUsers\Pages\ListUsers;
use App\Filament\Resources\AdminUsers\Pages\ViewUser;
use App\Filament\Resources\AdminUsers\RelationManagers\EmployeeDocumentsRelationManager;
use App\Filament\Resources\AdminUsers\Schemas\UserForm;
use App\Filament\Resources\AdminUsers\Tables\UsersTable;
use App\Filament\Resources\Departments\DepartmentResource;
use App\Models\EmployeeDocument;
use App\Models\User;
use App\Support\AdminRoles;
use App\Support\EmployeeRequiredDocuments;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View as SchemaView;
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

    protected static string|\UnitEnum|null $navigationGroup = '🏢 Организация';

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
                        SchemaView::make('filament.resources.admin-users.components.employee-personnel-card')
                            ->viewData(fn (User $record): array => ['employee' => $record])
                            ->columnSpanFull(),

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
                            ->url(fn (User $record): ?string => $record->manager
                                ? static::getUrl('view', ['record' => $record->manager])
                                : null)
                            ->placeholder('—'),

                        TextEntry::make('department.name')
                            ->label('Отдел')
                            ->url(fn (User $record): ?string => $record->department
                                ? DepartmentResource::getUrl('view', ['record' => $record->department])
                                : null)
                            ->placeholder('—'),

                        TextEntry::make('position')
                            ->label('Должность')
                            ->placeholder('—'),

                        TextEntry::make('department.manager.name')
                            ->label('Руководитель отдела')
                            ->url(fn (User $record): ?string => $record->department?->manager
                                ? static::getUrl('view', ['record' => $record->department->manager])
                                : null)
                            ->placeholder('—'),

                        TextEntry::make('department.actingManager.name')
                            ->label('ВРиО руководителя')
                            ->url(fn (User $record): ?string => $record->department?->actingManager
                                ? static::getUrl('view', ['record' => $record->department->actingManager])
                                : null)
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

                SchemaView::make('filament.resources.admin-users.components.employee-schedule-section')
                    ->visible(fn (User $record): bool => static::canViewEmployeeSchedule($record))
                    ->viewData(fn (User $record): array => ['employee' => $record])
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

                SchemaView::make('filament.resources.admin-users.components.employee-documents-dashboard')
                    ->visible(fn (User $record): bool => static::canViewEmployeeDocuments($record))
                    ->viewData(fn (User $record): array => static::employeeDocumentsDashboardData($record))
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

    public static function canManageEmployeeRoles(): bool
    {
        return static::canUseAdminPermission('users.update');
    }

    public static function canViewCitizenshipProfile(User $record): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        return $record->is($user) || static::canUseAnyPermission(['employees.hr.view', 'employees.hr.update']);
    }

    public static function canViewEmployeeDocuments(?User $record = null): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        if ($user->hasRole('super_admin') || $user->can('employees.documents.view')) {
            return true;
        }

        if ($record === null) {
            return false;
        }

        return $record->is($user) || (int) $record->manager_id === (int) $user->getKey();
    }

    public static function canUploadEmployeeDocuments(?User $record = null): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->hasRole('super_admin') || $user->can('employees.documents.upload'));
    }

    public static function canArchiveEmployeeDocuments(?User $record = null): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->hasRole('super_admin') || $user->can('employees.documents.archive'));
    }

    public static function canDeleteEmployeeDocuments(?User $record = null): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->hasRole('super_admin') || $user->can('employees.documents.delete'));
    }

    public static function canViewEmployeeSchedule(?User $record = null): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        if ($user->hasRole('super_admin') || $user->can('employees.schedule.view')) {
            return true;
        }

        if ($record === null) {
            return false;
        }

        return $record->is($user) || (int) $record->manager_id === (int) $user->getKey();
    }

    public static function canUpdateEmployeeSchedule(?User $record = null): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        if ($user->hasRole('super_admin') || $user->can('employees.schedule.update')) {
            return true;
        }

        if ($record === null) {
            return false;
        }

        return (int) $record->manager_id === (int) $user->getKey();
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

        if ($user->hasRole('super_admin') || $user->can('employees.salary.view')) {
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

    public static function documentStatusLabel(User $record): string
    {
        if ($record->missingRequiredDocuments() !== []) {
            return 'Не хватает';
        }

        $expiring = $record->expiringDocuments();

        if ($expiring->contains(fn ($document): bool => $document->isExpired())) {
            return 'Просрочены';
        }

        if ($expiring->isNotEmpty()) {
            return 'Истекают';
        }

        return 'Документы ОК';
    }

    public static function documentStatusColor(User $record): string
    {
        return match (static::documentStatusLabel($record)) {
            'Документы ОК' => 'success',
            'Истекают' => 'warning',
            default => 'danger',
        };
    }

    public static function employeeDocumentsDashboardData(User $record): array
    {
        $activeDocuments = $record->activeDocuments()->latest()->get();
        $documentsByCategory = $activeDocuments->keyBy('category');
        $required = EmployeeRequiredDocuments::requiredFor($record);
        $optional = EmployeeRequiredDocuments::optionalFor($record);
        $missing = $record->missingRequiredDocuments();
        $expiringDocuments = $record->expiringDocuments();

        $groups = [
            'Основные' => ['passport', 'snils', 'inn', 'personal_data_consent'],
            'Кадровые' => ['employment_record', 'employment_contract', 'education_document'],
            'Медицинские' => ['medical_book', 'voluntary_medical_insurance'],
            'Миграционные' => [
                'foreign_passport',
                'passport_translation',
                'migration_card',
                'migration_registration',
                'patent',
                'work_permit',
                'visa',
                'temporary_residence_permit',
                'residence_permit',
                'tax_payment_receipt',
                'foreign_employment_notice',
                'foreign_dismissal_notice',
            ],
            'Прочие' => ['driver_license', 'other'],
        ];

        $relevantCategories = collect([...$required, ...$optional, ...$activeDocuments->pluck('category')->all()])
            ->unique()
            ->values();

        $checklistGroups = collect($groups)
            ->map(fn (array $categories, string $label): array => [
                'label' => $label,
                'items' => collect($categories)
                    ->filter(fn (string $category): bool => $relevantCategories->contains($category))
                    ->map(function (string $category) use ($documentsByCategory, $required): array {
                        /** @var EmployeeDocument|null $document */
                        $document = $documentsByCategory->get($category);
                        $isRequired = in_array($category, $required, true);

                        return [
                            'label' => EmployeeRequiredDocuments::label($category),
                            'state' => match (true) {
                                $document?->isExpired() => 'expired',
                                $document?->expiresSoon() => 'warning',
                                $document !== null => 'uploaded',
                                $isRequired => 'missing',
                                default => 'optional',
                            },
                            'description' => match (true) {
                                $document?->isExpired() => $document->getExpirationLabel(),
                                $document?->expiresSoon() => $document->getExpirationLabel(),
                                $document !== null => 'Загружен',
                                $isRequired => 'Не загружен',
                                default => 'Не обязателен',
                            },
                        ];
                    })
                    ->values()
                    ->all(),
            ])
            ->filter(fn (array $group): bool => $group['items'] !== [])
            ->values()
            ->all();

        return [
            'employee' => $record,
            'completenessPercent' => $record->documentCompletenessPercent(),
            'loadedCount' => $activeDocuments->count(),
            'missingCount' => count($missing),
            'expiringCount' => $expiringDocuments->count(),
            'expiredCount' => $expiringDocuments->filter(fn (EmployeeDocument $document): bool => $document->isExpired())->count(),
            'missingDocumentLabels' => collect($missing)
                ->map(fn (string $category): string => EmployeeRequiredDocuments::label($category))
                ->values()
                ->all(),
            'expiringDocuments' => $expiringDocuments,
            'checklistGroups' => $checklistGroups,
        ];
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

    public static function getRelations(): array
    {
        return [
            EmployeeDocumentsRelationManager::class,
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

            if (! $user instanceof User) {
                return false;
            }

            if ($user->hasRole('super_admin')) {
                return true;
            }

            if ($user->hasRole('admin') && static::isViewPermissionSet($permissions)) {
                return true;
            }

            return collect($permissions)->contains(fn (string $permission): bool => $user->can($permission));
        } catch (Throwable) {
            return false;
        }
    }

    private static function isViewPermissionSet(array $permissions): bool
    {
        return collect($permissions)->every(
            fn (string $permission): bool => str_ends_with($permission, '.view') || str_ends_with($permission, '.view_any'),
        );
    }
}
