@php
    use Illuminate\Support\Facades\Storage;

    $filePath = $get('instruction_file_path');
    $fileUrl = null;

    if ($filePath
        && str_starts_with($filePath, 'product-instructions/')
        && Storage::disk('public')->exists($filePath)) {
        $fileUrl = Storage::disk('public')->url($filePath);
    }
@endphp

@if ($fileUrl)
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

    <div style="margin-top: 12px;">
        <a
            href="{{ $fileUrl }}"
            target="_blank"
            rel="noopener noreferrer"
            style="
                color: #2563eb;
                text-decoration: none;
                font-weight: 600;
            "
        >
            📄 Открыть инструкцию в новой вкладке
        </a>
    </div>
@endif
