<?php

namespace App\Filament\Resources\Certificates\Pages;

use App\Filament\Resources\Certificates\CertificateResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditCertificate extends EditRecord
{
    protected static string $resource = CertificateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            /*
             * Кнопка удаления PDF-файла.
             *
             * Она:
             * 1. Проверяет, есть ли файл.
             * 2. Удаляет файл из storage/app/public.
             * 3. Очищает поле file_path в базе.
             * 4. Показывает уведомление.
             */
            Action::make('deletePdf')
                ->label('Удалить PDF')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (): bool => filled($this->record->file_path))
                ->action(function (): void {
                    if ($this->record->file_path) {
                        Storage::disk('public')->delete($this->record->file_path);

                        $this->record->update([
                            'file_path' => null,
                        ]);
                    }

                    Notification::make()
                        ->title('PDF-файл удалён')
                        ->success()
                        ->send();

                    $this->redirect($this->getResource()::getUrl('edit', [
                        'record' => $this->record,
                    ]));
                }),

            /*
             * Стандартная кнопка удаления всей записи сертификата.
             */
            DeleteAction::make(),
        ];
    }
}