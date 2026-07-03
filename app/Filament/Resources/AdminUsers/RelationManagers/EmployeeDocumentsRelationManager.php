<?php

namespace App\Filament\Resources\AdminUsers\RelationManagers;

use App\Filament\Resources\AdminUsers\UserResource;
use App\Models\EmployeeDocument;
use App\Models\User;
use App\Support\EmployeeRequiredDocuments;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class EmployeeDocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $title = 'Документы';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof User && UserResource::canViewEmployeeDocuments($ownerRecord);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components(self::documentForm($this->getOwnerRecord()));
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->latest())
            ->emptyState(view('filament.resources.admin-users.components.employee-documents-empty-state'))
            ->columns([
                TextColumn::make('category')
                    ->label('Категория')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => EmployeeRequiredDocuments::label($state))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('title')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('document_number')
                    ->label('Номер')
                    ->placeholder('—')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('issued_at')
                    ->label('Выдан')
                    ->date('d.m.Y')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('expires_at')
                    ->label('Действует до')
                    ->date('d.m.Y')
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('expiration')
                    ->label('Статус срока')
                    ->badge()
                    ->state(fn (EmployeeDocument $record): string => $record->getExpirationLabel())
                    ->color(fn (EmployeeDocument $record): string => $record->expirationColor()),

                TextColumn::make('uploadedBy.name')
                    ->label('Загрузил')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Загружен')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('archived')
                    ->label('Показывать архив')
                    ->placeholder('Активные')
                    ->trueLabel('Все документы')
                    ->falseLabel('Только активные')
                    ->default(false)
                    ->queries(
                        true: fn (Builder $query): Builder => $query,
                        false: fn (Builder $query): Builder => self::activeOnly($query),
                        blank: fn (Builder $query): Builder => self::activeOnly($query),
                    ),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Загрузить документ')
                    ->icon(Heroicon::OutlinedArrowUpTray)
                    ->visible(fn (): bool => UserResource::canUploadEmployeeDocuments($this->getOwnerRecord()))
                    ->mutateFormDataUsing(fn (array $data): array => self::prepareDocumentData($data)),
            ])
            ->recordActions([
                Action::make('download')
                    ->label('Скачать')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->url(fn (EmployeeDocument $record): string => route('admin.employee-documents.download', [
                        'employee' => $this->getOwnerRecord(),
                        'document' => $record,
                    ]))
                    ->openUrlInNewTab(),

                Action::make('replace')
                    ->label('Заменить')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->visible(fn (): bool => UserResource::canUploadEmployeeDocuments($this->getOwnerRecord()))
                    ->form(fn (EmployeeDocument $record): array => self::documentForm($this->getOwnerRecord(), $record))
                    ->modalSubmitActionLabel('Заменить')
                    ->action(function (array $data, EmployeeDocument $record): void {
                        $newDocument = $record->employee->documents()->create(self::prepareDocumentData($data));

                        $record->update([
                            'replaced_by_id' => $newDocument->getKey(),
                            'archived_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Документ заменён.')
                            ->success()
                            ->send();
                    }),

                Action::make('archive')
                    ->label('Архивировать')
                    ->icon(Heroicon::OutlinedArchiveBox)
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (EmployeeDocument $record): bool => $record->archived_at === null && UserResource::canArchiveEmployeeDocuments($this->getOwnerRecord()))
                    ->action(function (EmployeeDocument $record): void {
                        $record->update(['archived_at' => now()]);

                        Notification::make()
                            ->title('Документ перемещён в архив.')
                            ->success()
                            ->send();
                    }),

                DeleteAction::make()
                    ->visible(fn (): bool => UserResource::canDeleteEmployeeDocuments($this->getOwnerRecord())),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => UserResource::canDeleteEmployeeDocuments($this->getOwnerRecord())),
                ]),
            ]);
    }

    private static function documentForm(User $employee, ?EmployeeDocument $document = null): array
    {
        return [
            Select::make('category')
                ->label('Категория')
                ->options(EmployeeRequiredDocuments::categories())
                ->default($document?->category)
                ->searchable()
                ->required()
                ->live(),

            TextInput::make('title')
                ->label('Название')
                ->default($document?->title)
                ->required()
                ->maxLength(255),

            TextInput::make('document_number')
                ->label('Номер документа')
                ->default($document?->document_number)
                ->maxLength(255),

            DatePicker::make('issued_at')
                ->label('Дата выдачи')
                ->default($document?->issued_at)
                ->native(false),

            DatePicker::make('expires_at')
                ->label('Действует до')
                ->default($document?->expires_at)
                ->native(false)
                ->helperText(fn (callable $get): ?string => EmployeeRequiredDocuments::requiresExpirationControl((string) $get('category'))
                    ? 'Для этой категории обычно требуется контроль срока действия.'
                    : null),

            TextInput::make('issued_by')
                ->label('Кем выдан')
                ->default($document?->issued_by)
                ->maxLength(255),

            FileUpload::make('file_path')
                ->label('Файл')
                ->disk('employee_documents')
                ->directory('employees/'.$employee->getKey())
                ->storeFileNamesIn('original_filename')
                ->acceptedFileTypes([
                    'application/pdf',
                    'image/jpeg',
                    'image/png',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                ])
                ->maxSize(10240)
                ->required(),

            Hidden::make('original_filename'),

            Textarea::make('comment')
                ->label('Комментарий')
                ->default($document?->comment)
                ->rows(3)
                ->columnSpanFull(),
        ];
    }

    private static function prepareDocumentData(array $data): array
    {
        $filePath = is_array($data['file_path'] ?? null)
            ? reset($data['file_path'])
            : ($data['file_path'] ?? null);

        $originalFilename = is_array($data['original_filename'] ?? null)
            ? reset($data['original_filename'])
            : ($data['original_filename'] ?? null);

        $data['file_path'] = $filePath;
        $data['original_filename'] = $originalFilename;
        $data['uploaded_by'] = auth()->id();

        if ($filePath !== null) {
            $disk = Storage::disk('employee_documents');
            $data['mime_type'] = $disk->mimeType($filePath) ?: null;
            $data['size_bytes'] = $disk->size($filePath) ?: null;
        }

        return $data;
    }

    private static function activeOnly(Builder $query): Builder
    {
        return $query
            ->whereNull('archived_at')
            ->whereNull('replaced_by_id');
    }
}
