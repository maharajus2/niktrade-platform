<?php

namespace App\Filament\Resources\AdminUsers\Schemas;

use App\Models\User;
use App\Support\AdminRoles;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;
use App\Models\AdminUserDocument;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Основная информация')
                    ->schema([
                        FileUpload::make('avatar_path')
                            ->label('Фото')
                            ->disk('public')
                            ->directory('admin-employees/avatars')
                            ->avatar()
                            ->imageEditor()
                            ->circleCropper()
                            ->maxSize(3072)
                            ->columnSpan(1),

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

                        DatePicker::make('date_of_birth')
                            ->label('Дата рождения')
                            ->native(false),

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

                Section::make('Контакты')
                    ->schema([
                        TextInput::make('phone')
                            ->label('Телефон')
                            ->tel()
                            ->maxLength(255),

                        TextInput::make('telegram_username')
                            ->label('Telegram')
                            ->prefix('@')
                            ->maxLength(255),

                        TextInput::make('emergency_contact')
                            ->label('Экстренный контакт')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('Работа')
                    ->schema([
                        Select::make('employee_status')
                            ->label('Статус сотрудника')
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
                            ->visible(fn (callable $get): bool => $get('employee_status') === User::STATUS_DISMISSED),

                        Select::make('schedule_type')
                            ->label('Тип графика')
                            ->options(User::scheduleTypeOptions())
                            ->default(User::SCHEDULE_FIVE_TWO)
                            ->required()
                            ->live(),

                        CheckboxList::make('working_days')
                            ->label('Рабочие дни')
                            ->options(User::workingDayOptions())
                            ->columns(2)
                            ->visible(fn (callable $get): bool => $get('schedule_type') === User::SCHEDULE_INDIVIDUAL)
                            ->columnSpanFull(),

                        TimePicker::make('work_starts_at')
                            ->label('Начало')
                            ->seconds(false)
                            ->visible(fn (callable $get): bool => $get('schedule_type') === User::SCHEDULE_INDIVIDUAL),

                        TimePicker::make('work_ends_at')
                            ->label('Окончание')
                            ->seconds(false)
                            ->visible(fn (callable $get): bool => $get('schedule_type') === User::SCHEDULE_INDIVIDUAL),
                    ])
                    ->columns(3)
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
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                Section::make('Документы')
                    ->schema([
                        Repeater::make('adminDocuments')
                            ->label('Документы')
                            ->relationship()
                            ->schema([
                                TextInput::make('title')
                                    ->label('Название')
                                    ->required()
                                    ->maxLength(255),

                                Select::make('category')
                                    ->label('Категория')
                                    ->options(AdminUserDocument::categoryOptions())
                                    ->required(),

                                FileUpload::make('file_path')
                                    ->label('Файл')
                                    ->disk('public')
                                    ->directory('admin-employees/documents')
                                    ->acceptedFileTypes([
                                        'application/pdf',
                                        'image/jpeg',
                                        'image/png',
                                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                    ])
                                    ->required()
                                    ->columnSpanFull(),

                                Textarea::make('comment')
                                    ->label('Комментарий')
                                    ->rows(2)
                                    ->columnSpanFull(),

                                DatePicker::make('uploaded_at')
                                    ->label('Дата загрузки')
                                    ->default(fn (): Carbon => now())
                                    ->native(false),
                            ])
                            ->columns(2)
                            ->collapsible()
                            ->defaultItems(0)
                            ->addActionLabel('Добавить документ')
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
}
