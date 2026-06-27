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
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
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

                        TextEntry::make('date_of_birth')
                            ->label('Дата рождения')
                            ->date('d.m.Y')
                            ->placeholder('—'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('Контакты')
                    ->schema([
                        TextEntry::make('phone')
                            ->label('Телефон')
                            ->placeholder('—'),

                        TextEntry::make('telegram_username')
                            ->label('Telegram')
                            ->prefix('@')
                            ->placeholder('—'),

                        TextEntry::make('emergency_contact')
                            ->label('Экстренный контакт')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('Работа')
                    ->schema([
                        TextEntry::make('employee_status')
                            ->label('Статус сотрудника')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => User::employeeStatusOptions()[$state] ?? 'Не указан')
                            ->color(fn (?string $state): string => User::employeeStatusColor($state)),

                        TextEntry::make('hire_date')
                            ->label('Дата найма')
                            ->date('d.m.Y')
                            ->placeholder('—'),

                        TextEntry::make('dismissal_date')
                            ->label('Дата увольнения')
                            ->date('d.m.Y')
                            ->placeholder('—')
                            ->visible(fn (User $record): bool => $record->employee_status === User::STATUS_DISMISSED),

                        TextEntry::make('schedule_type')
                            ->label('График')
                            ->formatStateUsing(fn (?string $state, User $record): string => self::scheduleDescription($record)),

                        TextEntry::make('archived_at')
                            ->label('Архив')
                            ->badge()
                            ->color('gray')
                            ->formatStateUsing(fn (): string => 'Архивирован')
                            ->visible(fn (User $record): bool => $record->isArchived()),
                    ])
                    ->columns(3)
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
                    ])
                    ->columnSpanFull(),

                Section::make('Документы')
                    ->schema([
                        RepeatableEntry::make('adminDocuments')
                            ->label('Документы')
                            ->schema([
                                TextEntry::make('title')
                                    ->label('Название'),

                                TextEntry::make('category')
                                    ->label('Категория')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => \App\Models\AdminUserDocument::categoryOptions()[$state] ?? 'Другое'),

                                TextEntry::make('file_path')
                                    ->label('Файл')
                                    ->formatStateUsing(fn (?string $state): string => $state ? basename($state) : '—')
                                    ->url(fn (\App\Models\AdminUserDocument $record): ?string => $record->file_path
                                        ? Storage::disk('public')->url($record->file_path)
                                        : null,
                                        shouldOpenInNewTab: true),

                                TextEntry::make('uploaded_at')
                                    ->label('Дата загрузки')
                                    ->dateTime('d.m.Y H:i')
                                    ->placeholder('—'),

                                TextEntry::make('comment')
                                    ->label('Комментарий')
                                    ->placeholder('—')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                Section::make('История активности')
                    ->schema([
                        TextEntry::make('activity_placeholder')
                            ->hiddenLabel()
                            ->state('История действий сотрудника будет доступна в следующем обновлении.'),
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
        return static::canManageAdminUsers();
    }

    public static function canCreate(): bool
    {
        return static::canUseAdminPermission('users.create');
    }

    public static function canEdit(Model $record): bool
    {
        return static::canUseAdminPermission('users.update');
    }

    public static function canView(Model $record): bool
    {
        return static::canUseAdminPermission('users.view');
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function archiveAction(): Action
    {
        return Action::make('archive')
            ->label('Архивировать сотрудника')
            ->icon(Heroicon::OutlinedArchiveBox)
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading('Архивировать сотрудника?')
            ->modalDescription('Сотрудник будет скрыт из списка по умолчанию и не сможет войти в админку.')
            ->modalSubmitActionLabel('Архивировать')
            ->visible(fn (User $record): bool => $record->canBeArchived() && static::canEdit($record))
            ->action(function (User $record): void {
                $record->update(['archived_at' => now()]);

                Notification::make()
                    ->title('Сотрудник архивирован.')
                    ->success()
                    ->send();
            });
    }

    public static function scheduleDescription(User $record): string
    {
        $schedule = User::scheduleTypeOptions()[$record->schedule_type] ?? 'Не указан';

        if ($record->schedule_type !== User::SCHEDULE_INDIVIDUAL) {
            return $schedule;
        }

        $days = collect($record->working_days ?? [])
            ->map(fn (string $day): string => User::workingDayOptions()[$day] ?? $day)
            ->implode(', ');

        $hours = trim(implode('–', array_filter([
            $record->work_starts_at,
            $record->work_ends_at,
        ])));

        return trim($schedule . ': ' . ($days ?: 'дни не указаны') . ($hours ? ", {$hours}" : ''));
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
        return static::canUseAdminPermission('users.view_any');
    }

    public static function canUseAdminPermission(string $permission): bool
    {
        try {
            $user = auth()->user();

            return $user !== null
                && ($user->hasRole('super_admin') || $user->can($permission));
        } catch (Throwable) {
            return false;
        }
    }
}
