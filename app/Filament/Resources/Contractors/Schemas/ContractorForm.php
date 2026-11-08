<?php

namespace App\Filament\Resources\Contractors\Schemas;

use App\Models\Contractor;
use App\Rules\UniqueContractorIdentity;
use App\Rules\ValidInn;
use App\Rules\ValidKpp;
use App\Support\ContractorIdentity;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ContractorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Компания')->schema([
                TextInput::make('company_name')->label('Название компании')->required()->maxLength(255),
                TextInput::make('inn')->label('ИНН')->required()->maxLength(12)->live(onBlur: true)
                    ->mutateStateForValidationUsing(fn ($state) => ContractorIdentity::inn($state))
                    ->dehydrateStateUsing(fn ($state) => ContractorIdentity::inn($state))
                    ->rules(fn (Get $get, ?Contractor $record): array => [
                        new ValidInn,
                        new UniqueContractorIdentity($get('kpp'), $record?->id),
                    ]),
                TextInput::make('kpp')->label('КПП')->maxLength(9)
                    ->required(fn (Get $get): bool => strlen((string) ContractorIdentity::inn($get('inn'))) === 10)
                    ->disabled(fn (Get $get): bool => strlen((string) ContractorIdentity::inn($get('inn'))) === 12)
                    ->mutateStateForValidationUsing(fn ($state, Get $get) => strlen((string) ContractorIdentity::inn($get('inn'))) === 12 ? null : ContractorIdentity::kpp($state))
                    ->dehydrateStateUsing(fn ($state, Get $get) => strlen((string) ContractorIdentity::inn($get('inn'))) === 12 ? null : ContractorIdentity::kpp($state))
                    ->dehydrated()
                    ->rules([new ValidKpp]),
                TextInput::make('ogrn')->label('ОГРН / ОГРНИП')->maxLength(15),
                Textarea::make('legal_address')->label('Юридический адрес')->rows(3)->columnSpanFull(),
                TextInput::make('director')->label('Руководитель')->maxLength(255),
            ])->columns(2),
            Section::make('Контакты')->schema([
                TextInput::make('contact_name')->label('Контактное лицо')->required()->maxLength(255),
                TextInput::make('phone')->label('Телефон')->tel()->required()->maxLength(50),
                TextInput::make('email')->label('Email')->email()->required()->maxLength(255)->unique(ignoreRecord: true)
                    ->mutateStateForValidationUsing(fn ($state) => is_string($state) ? strtolower(trim($state)) : $state)
                    ->dehydrateStateUsing(fn ($state) => is_string($state) ? strtolower(trim($state)) : $state),
            ])->columns(2),
            Section::make('Условия')->schema([
                TextInput::make('discount_percent')->label('Скидка')->numeric()->minValue(0)->maxValue(100)->suffix('%')->default(0)->required(),
                Select::make('manager_id')->label('Менеджер')->relationship('manager', 'name')->searchable()->preload(),
                TextInput::make('external_code')->label('Код в 1С')->maxLength(255),
                Toggle::make('is_active')->label('Активен')->default(true),
                Textarea::make('comment')->label('Комментарий')->rows(4)->columnSpanFull(),
            ])->columns(2),
            Section::make('Пароль')->schema([
                TextInput::make('password')->label('Новый пароль')->password()->minLength(8)
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->afterStateHydrated(fn (TextInput $component) => $component->state(null))
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->helperText('При редактировании оставьте пустым, чтобы сохранить текущий пароль.'),
            ]),
        ]);
    }
}
