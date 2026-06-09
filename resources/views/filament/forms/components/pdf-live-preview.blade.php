<div
    x-data="{
        previewUrl: null,

        init() {
            const setupPreview = () => {

                /*
                 * Ищем поле выбора файла PDF.
                 *
                 * Filament генерирует его динамически,
                 * поэтому ищем после полной загрузки формы.
                 */
                const fileInput = document.querySelector(
                    'input[type=file]'
                );

                if (!fileInput) {
                    setTimeout(setupPreview, 500);
                    return;
                }

                /*
                 * При выборе файла создаём
                 * локальную ссылку браузера.
                 *
                 * Файл ещё не загружен на сервер.
                 */
                fileInput.addEventListener('change', (event) => {

                    const file = event.target.files?.[0];

                    if (!file) {
                        this.previewUrl = null;
                        return;
                    }

                    /*
                     * Разрешаем только PDF.
                     */
                    if (file.type !== 'application/pdf') {
                        this.previewUrl = null;
                        return;
                    }

                    /*
                     * Если уже был preview —
                     * освобождаем память.
                     */
                    if (this.previewUrl) {
                        URL.revokeObjectURL(this.previewUrl);
                    }

                    /*
                     * Создаём локальный URL.
                     *
                     * Например:
                     * blob:https://site.ru/xxxx
                     */
                    this.previewUrl = URL.createObjectURL(file);
                });
            };

            setupPreview();
        }
    }"
>
    <template x-if="previewUrl">

        <div
            style="
                margin-top: 12px;
            "
        >

            <div
                style="
                    margin-bottom: 10px;
                    font-weight: 600;
                    color: #111827;
                "
            >
                📄 Предпросмотр выбранного PDF
            </div>

            <div
                style="
                    border: 1px solid #d1d5db;
                    border-radius: 12px;
                    overflow: hidden;
                    background: white;
                    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
                "
            >
                <iframe
                    :src="previewUrl"
                    style="
                        width: 100%;
                        height: 600px;
                        border: none;
                    "
                ></iframe>
            </div>

            <div
                style="
                    margin-top: 8px;
                    font-size: 13px;
                    color: #6b7280;
                "
            >
                Файл ещё не сохранён на сервере.
                Это локальный предпросмотр.
            </div>

        </div>

    </template>
</div>