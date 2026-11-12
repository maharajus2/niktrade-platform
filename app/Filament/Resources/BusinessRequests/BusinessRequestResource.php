<?php

namespace App\Filament\Resources\BusinessRequests;

use App\Enums\BusinessRequestStatus;
use App\Filament\Resources\BusinessRequests\Pages\EditBusinessRequest;
use App\Filament\Resources\BusinessRequests\Pages\ListBusinessRequests;
use App\Filament\Resources\BusinessRequests\Pages\ViewBusinessRequest;
use App\Filament\Resources\Concerns\UsesResourcePermissions;
use App\Models\BusinessRequest;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class BusinessRequestResource extends Resource
{
    use UsesResourcePermissions;

    protected static string $permissionPrefix = 'business_requests';

    protected static ?string $model = BusinessRequest::class;

    protected static ?string $navigationLabel = 'Заявки бизнеса';

    protected static ?string $modelLabel = 'Заявка бизнеса';

    protected static ?string $pluralModelLabel = 'Заявки бизнеса';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|\UnitEnum|null $navigationGroup = 'Продажи';

    protected static ?int $navigationSort = 4;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['contractor', 'manager', 'items', 'statusLogs.user']);
    }

    private static function details(): array
    {
        return [
            Section::make('Заявка и контрагент')->schema([
                TextEntry::make('number')->label('Номер'),
                TextEntry::make('contractor.company_name')->label('Компания'),
                TextEntry::make('contractor.inn')->label('ИНН'),
                TextEntry::make('contractor.kpp')->label('КПП')->placeholder('—'),
                TextEntry::make('contractor.contact_name')->label('Контактное лицо'),
                TextEntry::make('contractor.phone')->label('Телефон'),
                TextEntry::make('contractor.email')->label('Email'),
                TextEntry::make('subtotal')->label('Без скидки')->money('RUB'),
                TextEntry::make('discount_percent')->label('Скидка')->suffix('%'),
                TextEntry::make('total')->label('Итого')->money('RUB'),
                TextEntry::make('comment')->label('Комментарий клиента')->placeholder('—'),
            ])->columns(2)->columnSpanFull(),
            Section::make('Позиции')->schema([
                RepeatableEntry::make('items')->hiddenLabel()->schema([
                    TextEntry::make('name')->label('Товар'), TextEntry::make('article')->label('Артикул'),
                    TextEntry::make('quantity')->label('Количество'),
                    TextEntry::make('list_price')->label('Бизнес-цена')->money('RUB'),
                    TextEntry::make('unit_price')->label('Цена со скидкой')->money('RUB'),
                    TextEntry::make('line_total')->label('Сумма')->money('RUB'),
                ])->columns(3),
            ])->columnSpanFull(),
        ];
    }

    private static function history(): Section
    {
        return Section::make('История статусов')->schema([
            RepeatableEntry::make('statusLogs')->hiddenLabel()->schema([
                TextEntry::make('created_at')->label('Дата')->dateTime('d.m.Y H:i'),
                TextEntry::make('from_status')->label('Было')->badge()->placeholder('—'),
                TextEntry::make('to_status')->label('Стало')->badge(),
                TextEntry::make('user.name')->label('Сотрудник')->placeholder('Система'),
                TextEntry::make('note')->label('Комментарий')->placeholder('—'),
            ])->columns(3),
        ])->columnSpanFull();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            ...self::details(),
            Section::make('Обработка заявки')->schema([
                Select::make('status')->label('Статус')->options(BusinessRequestStatus::class)->required()->live(),
                Select::make('manager_id')->label('Менеджер')->relationship('manager', 'name')->searchable()->preload(),
                Textarea::make('manager_comment')->label('Комментарий менеджера')->columnSpanFull(),
                TextInput::make('invoice_number')->label('Номер счёта')->maxLength(255)
                    ->required(fn (Get $get): bool => in_array($get('status'), ['invoiced', BusinessRequestStatus::Invoiced], true)),
                FileUpload::make('invoice_path')->label('PDF счёта')->disk('local')->directory('business-invoices')
                    ->visibility('private')->acceptedFileTypes(['application/pdf'])->maxSize(10240)
                    ->previewable(false)->openable(false)->downloadable(false)->preventFilePathTampering()
                    ->getUploadedFileUsing(fn (string $file): ?array => Storage::disk('local')->exists($file)
                        ? ['name' => basename($file), 'size' => Storage::disk('local')->size($file), 'type' => 'application/pdf', 'url' => null] : null)
                    ->required(fn (Get $get): bool => in_array($get('status'), ['invoiced', BusinessRequestStatus::Invoiced], true)),
                TextEntry::make('invoice_issued_at')->label('Первый счёт загружен')->dateTime('d.m.Y H:i')->placeholder('—'),
            ])->columns(2)->columnSpanFull(),
            self::history(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            ...self::details(),
            Section::make('Обработка заявки')->schema([
                TextEntry::make('status')->label('Статус')->badge(),
                TextEntry::make('manager.name')->label('Менеджер')->placeholder('Не назначен'),
                TextEntry::make('manager_comment')->label('Комментарий менеджера')->placeholder('—'),
                TextEntry::make('invoice_number')->label('Номер счёта')->placeholder('—'),
                TextEntry::make('invoice_issued_at')->label('Счёт загружен')->dateTime('d.m.Y H:i')->placeholder('—'),
            ])->columnSpanFull(),
            self::history(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('number')->label('Номер')->searchable()->sortable(),
            TextColumn::make('contractor.company_name')->label('Контрагент')->searchable(),
            TextColumn::make('status')->label('Статус')->badge(),
            TextColumn::make('total')->label('Сумма')->money('RUB')->sortable(),
            TextColumn::make('items_count')->label('Позиций'),
            TextColumn::make('created_at')->label('Дата')->dateTime('d.m.Y H:i')->sortable(),
        ])->filters([SelectFilter::make('status')->label('Статус')->options(BusinessRequestStatus::class)])
            ->defaultSort('created_at', 'desc')->recordActions([ViewAction::make(), EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListBusinessRequests::route('/'), 'view' => ViewBusinessRequest::route('/{record}'), 'edit' => EditBusinessRequest::route('/{record}/edit')];
    }
}
