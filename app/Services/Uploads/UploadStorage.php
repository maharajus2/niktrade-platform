<?php

namespace App\Services\Uploads;

use Filament\Forms\Components\FileUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToWriteFile;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class UploadStorage
{
    public function store(UploadedFile $file, string $directory, string $disk, string $field): string
    {
        return $this->verified(
            fn () => $file->store($directory, $disk),
            $disk,
            $directory,
            $field,
        );
    }

    public function storeFilament(FileUpload $component, TemporaryUploadedFile $file): string
    {
        return $this->verified(
            fn () => $component->saveUploadedFile($file),
            $component->getDiskName(),
            $component->getDirectory() ?? '',
            $component->getStatePath(),
        );
    }

    private function verified(callable $write, string $disk, string $directory, string $field): string
    {
        try {
            $path = $write();

            if (! is_string($path) || $path === '' || ! Storage::disk($disk)->exists($path)) {
                throw UnableToWriteFile::atLocation($directory, 'Upload did not create a file.');
            }

            return $path;
        } catch (FilesystemException $exception) {
            report($exception);

            throw ValidationException::withMessages([
                $field => 'Не удалось сохранить файл. Повторите загрузку. Если ошибка повторится, обратитесь к администратору.',
            ]);
        }
    }
}
