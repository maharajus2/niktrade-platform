<?php

namespace App\Filament\Resources\Departments\RelationManagers;

use App\Filament\Resources\AdminUsers\UserResource;
use App\Filament\Resources\Departments\DepartmentResource;
use App\Models\Department;
use App\Models\User;
use App\Support\AdminRoles;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class EmployeesRelationManager extends RelationManager
{
    protected static string $relationship = 'employees';

    protected static ?string $title = 'Сотрудники отдела';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof Department && DepartmentResource::canUseDepartmentPermission('departments.view');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('last_name')
                    ->label('Фамилия')
                    ->required()
                    ->validationMessages([
                        'required' => 'Укажите фамилию.',
                    ])
                    ->maxLength(255),

                TextInput::make('first_name')
                    ->label('Имя')
                    ->required()
                    ->validationMessages([
                        'required' => 'Укажите имя.',
                    ])
                    ->maxLength(255),

                TextInput::make('patronymic')
                    ->label('Отчество')
                    ->disabled(fn (callable $get): bool => (bool) $get('no_patronymic'))
                    ->dehydrated()
                    ->maxLength(255),

                Toggle::make('no_patronymic')
                    ->label('Без отчества')
                    ->live()
                    ->afterStateUpdated(function (bool $state, callable $set): void {
                        if ($state) {
                            $set('patronymic', null);
                        }
                    }),

                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                TextInput::make('phone')
                    ->label('Телефон')
                    ->tel()
                    ->maxLength(255),

                TextInput::make('position')
                    ->label('Должность')
                    ->maxLength(255),

                TextInput::make('password')
                    ->label('Пароль')
                    ->password()
                    ->revealable()
                    ->required()
                    ->maxLength(255),

                Select::make('roles')
                    ->label('Роли')
                    ->relationship('roles', 'name')
                    ->getOptionLabelFromRecordUsing(fn (Role $record): string => AdminRoles::label($record->name))
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->columnSpanFull(),

                Select::make('employment_status')
                    ->label('Статус')
                    ->options(User::employeeStatusOptions())
                    ->default(User::STATUS_WORKING)
                    ->required(),

                Select::make('employment_type')
                    ->label('Тип трудоустройства')
                    ->options(User::employmentTypeOptions())
                    ->nullable(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with(['roles', 'department'])
                ->orderByRaw('position asc nulls last')
                ->orderBy('name'))
            ->emptyStateHeading('В этом отделе пока нет сотрудников.')
            ->emptyStateDescription('Добавьте сотрудника, чтобы он появился в структуре отдела.')
            ->columns([
                ImageColumn::make('avatar_path')
                    ->label('Фото')
                    ->disk('public')
                    ->imageWidth(44)
                    ->imageHeight(44)
                    ->extraImgAttributes([
                        'style' => 'background: linear-gradient(145deg, rgba(232, 241, 255, .95), rgba(255, 255, 255, .86)); border: 1px solid rgba(255, 255, 255, .86); border-radius: 15px; box-shadow: 0 12px 24px rgba(31, 52, 86, .12), inset 0 1px 0 rgba(255, 255, 255, .82); object-fit: cover;',
                    ]),

                TextColumn::make('name')
                    ->label('ФИО')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('position')
                    ->label('Должность')
                    ->placeholder('—')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('roles.name')
                    ->label('Основная роль')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => AdminRoles::label($state))
                    ->icon(fn (string $state): Heroicon => AdminRoles::icon($state))
                    ->color(fn (string $state): string => AdminRoles::color($state))
                    ->separator(', '),

                TextColumn::make('employment_status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn (?string $state, User $record): string => $record->getEmploymentStatusLabel())
                    ->color(fn (?string $state, User $record): string => User::employeeStatusColor($record->employment_status ?? $record->employee_status))
                    ->sortable(),

                TextColumn::make('employment_type')
                    ->label('Тип')
                    ->badge()
                    ->formatStateUsing(fn (?string $state, User $record): string => $record->getEmploymentTypeLabel())
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('phone')
                    ->label('Телефон')
                    ->placeholder('—')
                    ->searchable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Добавить сотрудника')
                    ->icon(Heroicon::OutlinedUserPlus)
                    ->visible(fn (): bool => UserResource::canCreate())
                    ->mutateFormDataUsing(fn (array $data): array => $data + [
                        'department_id' => $this->getOwnerRecord()->getKey(),
                    ]),
            ])
            ->recordActions([
                Action::make('viewEmployee')
                    ->label('Просмотр')
                    ->icon(Heroicon::OutlinedEye)
                    ->url(fn (User $record): string => UserResource::getUrl('view', ['record' => $record]))
                    ->visible(fn (User $record): bool => UserResource::canView($record)),

                Action::make('editEmployee')
                    ->label('Редактировать')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->url(fn (User $record): string => UserResource::getUrl('edit', ['record' => $record]))
                    ->visible(fn (User $record): bool => UserResource::canEdit($record)),
            ]);
    }
}
