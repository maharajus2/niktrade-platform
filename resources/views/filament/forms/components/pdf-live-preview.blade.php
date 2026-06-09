<div
    x-data="{
        previewUrl: null,

        init() {
            const updatePreview = () => {
                const filePondRoot = document.querySelector('.filepond--root');

                if (!filePondRoot || !filePondRoot._pond) {
                    setTimeout(updatePreview, 500);
                    return;
                }

                const files = filePondRoot._pond.getFiles();

                if (!files.length) {
                    this.previewUrl = null;
                    setTimeout(updatePreview, 500);
                    return;
                }

                const file = files[0].file;

                if (!file || file.type !== 'application/pdf') {
                    this.previewUrl = null;
                    setTimeout(updatePreview, 500);
                    return;
                }

                if (this.previewUrl) {
                    URL.revokeObjectURL(this.previewUrl);
                }

                this.previewUrl = URL.createObjectURL(file);

                filePondRoot._pond.on('removefile', () => {
                    this.previewUrl = null;
                });
            };

            setTimeout(updatePreview, 500);
        }
    }"
>
    <template x-if="previewUrl">
        <div style="margin-top: 12px;">
            <div style="margin-bottom: 10px; font-weight: 600;">
                📄 Предпросмотр выбранного PDF до сохранения
            </div>

            <div style="border: 1px solid #d1d5db; border-radius: 12px; overflow: hidden; background: white;">
                <iframe
                    :src="previewUrl"
                    style="width: 100%; height: 600px; border: none;"
                ></iframe>
            </div>

            <div style="margin-top: 8px; color: #6b7280; font-size: 13px;">
                Файл ещё не сохранён на сервере. Это локальный предпросмотр.
            </div>
        </div>
    </template>
</div>