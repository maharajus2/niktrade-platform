@php
    use Illuminate\Support\Facades\Storage;

    /*
     * Получаем путь к PDF-файлу,
     * который хранится в поле file_path.
     */
    $filePath = $get('file_path');

    /*
     * Формируем публичную ссылку.
     *
     * Например:
     * storage/certificates/test.pdf
     */
    $fileUrl = $filePath
        ? Storage::disk('public')->url($filePath)
        : null;
@endphp

@if ($fileUrl)

    {{-- Блок предпросмотра PDF --}}

    <div
        style="
            border: 1px solid #d1d5db;
            border-radius: 12px;
            overflow: hidden;
            background: white;
        "
    >
        <iframe
            src="{{ $fileUrl }}"
            style="
                width: 100%;
                height: 500px;
                border: none;
            "
        ></iframe>
    </div>

    {{-- Ссылка на открытие файла отдельно --}}

    <div style="margin-top: 12px;">
        <a
            href="{{ $fileUrl }}"
            target="_blank"
            style="
                color: #2563eb;
                text-decoration: none;
                font-weight: 600;
            "
        >
            📄 Открыть PDF в новой вкладке
        </a>
    </div>

@endif