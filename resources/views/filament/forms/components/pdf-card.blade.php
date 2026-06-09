@php
    use Illuminate\Support\Facades\Storage;

    /*
     * Путь к PDF-файлу из поля file_path.
     */
    $filePath = $get('file_path');

    /*
     * Публичная ссылка на файл.
     */
    $fileUrl = $filePath
        ? Storage::disk('public')->url($filePath)
        : null;

    /*
     * Имя файла без папки.
     */
    $fileName = $filePath
        ? basename($filePath)
        : 'Файл сертификата';

    /*
     * Размер файла.
     */
    $fileSize = null;

    if ($filePath && Storage::disk('public')->exists($filePath)) {
        $bytes = Storage::disk('public')->size($filePath);

        $fileSize = $bytes >= 1024 * 1024
            ? round($bytes / 1024 / 1024, 2) . ' МБ'
            : round($bytes / 1024, 0) . ' КБ';
    }
@endphp

@if ($fileUrl)
    <div
        style="
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 16px 18px;
            border: 1px solid #d1d5db;
            border-radius: 14px;
            background: #ffffff;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
        "
    >
        <div style="display: flex; align-items: center; gap: 14px;">
            <div
                style="
                    width: 44px;
                    height: 44px;
                    border-radius: 12px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    background: #e0f2fe;
                    font-size: 24px;
                "
            >
                📄
            </div>

            <div>
                <div style="font-weight: 700; color: #111827;">
                    {{ $fileName }}
                </div>

                <div style="margin-top: 4px; color: #6b7280; font-size: 13px;">
                    PDF-файл сертификата
                    @if ($fileSize)
                        · {{ $fileSize }}
                    @endif
                </div>
            </div>
        </div>

        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a
                href="{{ $fileUrl }}"
                target="_blank"
                style="
                    padding: 8px 12px;
                    border-radius: 10px;
                    background: #0284c7;
                    color: #ffffff;
                    text-decoration: none;
                    font-weight: 600;
                    font-size: 14px;
                "
            >
                Открыть
            </a>

            <a
                href="{{ $fileUrl }}"
                download
                style="
                    padding: 8px 12px;
                    border-radius: 10px;
                    background: #f3f4f6;
                    color: #111827;
                    text-decoration: none;
                    font-weight: 600;
                    font-size: 14px;
                "
            >
                Скачать
            </a>
        </div>
    </div>
@endif