<?php

namespace App\Filament\Resources\AdminUsers\Schemas;

use App\Filament\Resources\AdminUsers\UserResource;
use App\Models\Department;
use App\Models\User;
use App\Support\AdminRoles;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View as SchemaView;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Основная информация')
                    ->schema([
                        self::employeePhotoUpload()
                            ->visible(fn (?User $record): bool => $record === null || blank($record->avatar_path))
                            ->dehydrated(fn (?User $record): bool => $record === null || blank($record->avatar_path)),

                        SchemaView::make('filament.resources.admin-users.components.employee-personnel-card')
                            ->viewData(fn (?User $record): array => ['employee' => $record])
                            ->columnSpanFull(),

                        Actions::make([
                            Action::make('replaceEmployeePhoto')
                                ->label('Заменить фото')
                                ->icon(Heroicon::OutlinedArrowUpTray)
                                ->color('primary')
                                ->visible(fn (?User $record): bool => filled($record?->avatar_path))
                                ->modalHeading('Заменить фотографию сотрудника')
                                ->modalSubmitActionLabel('Заменить фото')
                                ->form([
                                    self::employeePhotoUpload()
                                        ->required(),
                                ])
                                ->action(function (array $data, User $record): void {
                                    $oldPath = $record->avatar_path;
                                    $uploadedPhoto = $data['avatar_path'] ?? null;
                                    $newPath = is_array($uploadedPhoto)
                                        ? reset($uploadedPhoto)
                                        : $uploadedPhoto;

                                    if (blank($newPath)) {
                                        return;
                                    }

                                    $record->update(['avatar_path' => $newPath]);

                                    self::deleteUnusedEmployeePhoto($oldPath, $record);

                                    Notification::make()
                                        ->title('Фотография сотрудника обновлена.')
                                        ->success()
                                        ->send();
                                }),

                            Action::make('deleteEmployeePhoto')
                                ->label('Удалить фото')
                                ->icon(Heroicon::OutlinedTrash)
                                ->color('danger')
                                ->outlined()
                                ->visible(fn (?User $record): bool => filled($record?->avatar_path))
                                ->requiresConfirmation()
                                ->modalHeading('Удалить фотографию сотрудника?')
                                ->modalSubmitActionLabel('Удалить')
                                ->modalCancelActionLabel('Отмена')
                                ->action(function (User $record): void {
                                    $oldPath = $record->avatar_path;

                                    $record->update(['avatar_path' => null]);

                                    self::deleteUnusedEmployeePhoto($oldPath, $record);

                                    Notification::make()
                                        ->title('Фотография сотрудника удалена.')
                                        ->success()
                                        ->send();
                                }),
                        ])
                            ->visible(fn (?User $record): bool => filled($record?->avatar_path))
                            ->columnSpanFull(),

                        TextInput::make('name')
                            ->label('ФИО')
                            ->required()
                            ->maxLength(255),

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

                        TextInput::make('password')
                            ->label('Пароль')
                            ->password()
                            ->revealable()
                            ->required(fn (?string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->maxLength(255),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('Роль и доступ')
                    ->icon(fn (?User $record): Heroicon => AdminRoles::primaryIcon($record))
                    ->iconColor(fn (?User $record): string => AdminRoles::primaryColor($record))
                    ->description(fn (?User $record): string => AdminRoles::primaryDescription($record))
                    ->schema([
                        Select::make('roles')
                            ->label('Роли')
                            ->relationship('roles', 'name')
                            ->getOptionLabelFromRecordUsing(fn (Role $record): string => AdminRoles::label($record->name))
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->visible(fn (): bool => UserResource::canManageEmployeeRoles())
                            ->columnSpanFull(),

                        Select::make('manager_id')
                            ->label('Руководитель')
                            ->relationship('manager', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        Select::make('department_id')
                            ->label('Отдел')
                            ->default(fn (): ?int => request()->integer('department_id') ?: null)
                            ->relationship('department', 'name', modifyQueryUsing: fn (Builder $query): Builder => $query
                                ->where('is_active', true)
                                ->orderBy('sort_order')
                                ->orderBy('name'))
                            ->getOptionLabelFromRecordUsing(fn (Department $record): string => $record->name)
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        TextInput::make('position')
                            ->label('Должность')
                            ->maxLength(255),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('HR')
                    ->visible(fn (): bool => UserResource::canUpdateHrProfile())
                    ->schema([
                        Select::make('employment_type')
                            ->label('Тип трудоустройства')
                            ->options(User::employmentTypeOptions())
                            ->nullable(),

                        Select::make('employment_status')
                            ->label('Статус')
                            ->options(User::employeeStatusOptions())
                            ->default(User::STATUS_WORKING)
                            ->required()
                            ->live(),

                        DatePicker::make('hire_date')
                            ->label('Дата найма')
                            ->native(false),

                        DatePicker::make('dismissal_date')
                            ->label('Дата увольнения')
                            ->native(false)
                            ->visible(fn (callable $get): bool => $get('employment_status') === User::STATUS_DISMISSED),

                        Select::make('schedule_type')
                            ->label('Тип графика')
                            ->options(User::scheduleTypeOptions())
                            ->nullable(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('Гражданство и миграционный статус')
                    ->visible(fn (): bool => UserResource::canUpdateHrProfile())
                    ->schema([
                        Select::make('citizenship_type')
                            ->label('Гражданство')
                            ->options(User::citizenshipTypeOptions())
                            ->default(User::CITIZENSHIP_RUSSIAN)
                            ->required()
                            ->live(),

                        TextInput::make('citizenship_country')
                            ->label('Страна гражданства')
                            ->maxLength(255)
                            ->visible(fn (callable $get): bool => $get('citizenship_type') !== User::CITIZENSHIP_RUSSIAN),

                        TextInput::make('arrival_country')
                            ->label('Страна прибытия')
                            ->maxLength(255)
                            ->visible(fn (callable $get): bool => $get('citizenship_type') !== User::CITIZENSHIP_RUSSIAN),

                        DatePicker::make('arrived_at')
                            ->label('Дата прибытия в РФ')
                            ->native(false)
                            ->visible(fn (callable $get): bool => $get('citizenship_type') !== User::CITIZENSHIP_RUSSIAN),

                        Select::make('foreign_legal_status')
                            ->label('Миграционный статус')
                            ->options(User::foreignLegalStatusOptions())
                            ->nullable()
                            ->visible(fn (callable $get): bool => $get('citizenship_type') !== User::CITIZENSHIP_RUSSIAN),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                SchemaView::make('filament.resources.admin-users.components.employee-schedule-section')
                    ->visible(fn (?User $record): bool => $record !== null && UserResource::canViewEmployeeSchedule($record))
                    ->viewData(fn (User $record): array => ['employee' => $record])
                    ->columnSpanFull(),

                Section::make('Испытательный срок')
                    ->visible(fn (): bool => UserResource::canManageProbation())
                    ->headerActions([
                        Action::make('cancelProbation')
                            ->label('Отменить испытательный срок')
                            ->color('gray')
                            ->requiresConfirmation()
                            ->visible(fn (?User $record): bool => $record?->probation_enabled && $record->probation_cancelled_at === null)
                            ->action(function (User $record): void {
                                $record->update([
                                    'probation_enabled' => false,
                                    'probation_cancelled_at' => now(),
                                    'probation_cancelled_by' => auth()->id(),
                                ]);

                                Notification::make()
                                    ->title('Испытательный срок отменён.')
                                    ->success()
                                    ->send();
                            }),
                    ])
                    ->schema([
                        Toggle::make('probation_enabled')
                            ->label('Испытательный срок')
                            ->default(false)
                            ->live(),

                        DatePicker::make('probation_started_at')
                            ->label('Дата начала')
                            ->native(false),

                        DatePicker::make('probation_ends_at')
                            ->label('Дата окончания')
                            ->native(false),

                        DatePicker::make('probation_cancelled_at')
                            ->label('Отменён')
                            ->disabled()
                            ->dehydrated(false)
                            ->native(false),

                        Select::make('probation_cancelled_by')
                            ->label('Кем отменён')
                            ->relationship('probationCancelledBy', 'name')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('Оклад')
                    ->visible(fn (?User $record): bool => UserResource::canViewSalary($record))
                    ->disabled(fn (): bool => ! UserResource::canUpdateSalary())
                    ->schema([
                        TextInput::make('salary_amount')
                            ->label('Оклад')
                            ->numeric()
                            ->prefix('₽')
                            ->minValue(0),

                        TextInput::make('salary_currency')
                            ->label('Валюта')
                            ->default('RUB')
                            ->maxLength(3),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                SchemaView::make('filament.resources.admin-users.components.employee-documents-dashboard')
                    ->visible(fn (?User $record): bool => $record !== null && UserResource::canViewEmployeeDocuments($record))
                    ->viewData(fn (User $record): array => UserResource::employeeDocumentsDashboardData($record))
                    ->columnSpanFull(),
            ]);
    }

    private static function employeePhotoUpload(string $name = 'avatar_path'): FileUpload
    {
        return FileUpload::make($name)
            ->label('Фото')
            ->disk('public')
            ->directory('admin-employees/avatars')
            ->image()
            ->imageEditor()
            ->imageAspectRatio('35:45')
            ->automaticallyCropImagesToAspectRatio()
            ->imageEditorAspectRatioOptions(['35:45'])
            ->imagePreviewHeight('155')
            ->maxSize(3072);
    }

    private static function deleteUnusedEmployeePhoto(?string $path, User $record): void
    {
        if (blank($path)) {
            return;
        }

        $isStillUsed = User::query()
            ->whereKeyNot($record->getKey())
            ->where('avatar_path', $path)
            ->exists();

        if (! $isStillUsed) {
            Storage::disk('public')->delete($path);
        }
    }
}
