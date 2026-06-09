<?php

namespace App\Filament\Resources\Certificates;

use App\Filament\Resources\Certificates\Pages\CreateCertificate;
use App\Filament\Resources\Certificates\Pages\EditCertificate;
use App\Filament\Resources\Certificates\Pages\ListCertificates;
use App\Filament\Resources\Certificates\Schemas\CertificateForm;
use App\Filament\Resources\Certificates\Tables\CertificatesTable;
use App\Models\Certificate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CertificateResource extends Resource
{
    /*
     * Модель, с которой работает этот ресурс.
     *
     * Filament понимает:
     * этот раздел админки управляет таблицей certificates
     * через модель App\Models\Certificate.
     */
    protected static ?string $model = Certificate::class;

    /*
     * Название пункта в левом меню админки.
     */
    protected static ?string $navigationLabel = 'Сертификаты';

    /*
     * Название одной записи.
     *
     * Используется на страницах создания/редактирования.
     */
    protected static ?string $modelLabel = 'Сертификат';

    /*
     * Название нескольких записей.
     */
    protected static ?string $pluralModelLabel = 'Сертификаты';

    /*
     * Порядок пункта меню.
     *
     * Чем меньше число — тем выше пункт в меню.
     */
    protected static ?int $navigationSort = 5;

    /*
     * Иконка в меню Filament.
     */
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    /*
     * Форма создания и редактирования сертификата.
     */
    public static function form(Schema $schema): Schema
    {
        return CertificateForm::configure($schema);
    }

    /*
     * Таблица списка сертификатов.
     */
    public static function table(Table $table): Table
    {
        return CertificatesTable::configure($table);
    }

    /*
     * Связанные сущности.
     *
     * Пока не используем.
     * Позже сюда можно будет добавить связь с товарами.
     */
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /*
     * Страницы ресурса:
     * список, создание, редактирование.
     */
    public static function getPages(): array
    {
        return [
            'index' => ListCertificates::route('/'),
            'create' => CreateCertificate::route('/create'),
            'edit' => EditCertificate::route('/{record}/edit'),
        ];
    }
}