import Resumable from "resumablejs";
document.addEventListener('alpine:init', () => {
    Alpine.data('chunkUpload', (moduleId) => ({
        resumable: null,
        file: null,
        isFileSelected: false,
        isUploading: false,
        isUploaded: false,
        showDurationError: false,

        init() {
            this.resumable = new Resumable({
                target: document.getElementById(`browser-file-${moduleId}`).dataset.url,
                chunkSize: 1 * 1024 * 1024,
                testMethod: 'POST',
                uploadMethod: 'POST',
                simultaneousUploads: 1,
                testChunks: false,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            // Debugging helpers
            // this.resumable.on('catchAll', (event) => {
            //     console.log('Resumable event:', event);
            // });

            this.resumable.assignBrowse(document.getElementById(`fileInputVideo-${moduleId}`));

            this.resumable.on('fileAdded', (file) => {
                console.log('File added:', file);
                this.file = file;
                this.validateVideoDuration(file.file);
            });

            this.resumable.on('fileProgress', (file) => {
                const progress = Math.floor(file.progress() * 100);
                this.updateProgressBar(progress);
            });

            this.resumable.on('complete', () => {
                console.log('Upload complete!');
                this.isUploading = false;
                this.isUploaded = true;

                document.getElementById(`success-message-${moduleId}`).style.display = 'flex';

                setTimeout(() => {
                    this.resetForm(moduleId);
                    this.resumable.removeFile(this.file);
                    this.resumable.files = [];
                    this.file = null
                     //window.location.reload()
                    Livewire.dispatch('open-new-block-to-module', {open: false});
                    //Livewire.dispatch('upload-block-close');
                    Livewire.dispatch('module-event', {type:'uploaded', msg:'Bloco enviado com sucesso'} );
                    //this.resetState();
                },1000)
            });

            this.resumable.on('error', (message, file) => {
                console.error('Upload error:', message, file);
                this.isUploading = false;
                alert('Erro no upload: ' + message);
            });
        },
        // Adicione este novo método
        resetForm(moduleId) {
            // Limpa campos
           // document.getElementById(`description-${moduleId}`).value = '';
            document.getElementById(`temp-video-preview-${moduleId}`).src = '';

            // Reseta estado
            this.file = null;
            this.isFileSelected = false;
            this.showDurationError = false;
            this.isUploaded = false;

            // Oculta mensagem de sucesso (se ainda visível)
            document.getElementById(`success-message-${moduleId}`).style.display = 'none';

            // Debug
            console.log('Formulário resetado com sucesso', this.file);
        },
        updateProgressBar(progress) {
            this.$nextTick(() => {
                const bar = document.getElementById(`progress-bar-${moduleId}`);
                const text = document.getElementById(`progress-percentage-${moduleId}`);

                if (bar && text) {
                    bar.style.width = `${progress}%`;
                    text.textContent = `${progress}%`;
                    console.log('Progress updated:', progress); // Debug
                }
            });
        },
        selectFile() {
            this.showDurationError = false; // Resetar erro
            document.getElementById(`fileInputVideo-${moduleId}`).click();
        },

        uploadStart() {
            if (!this.file) return;
            //const description = document.getElementById(`description-${moduleId}`).value;
            //this.resumable.opts.query = { description }; // Enviar descrição
            this.isFileSelected = false;
            this.isUploading = true; // Mostrar barra de progresso
            this.resumable.upload();
        },

        validateVideoDuration(file) {

            const video = document.getElementById(`temp-video-${moduleId}`);
            video.src = URL.createObjectURL(file);

            video.onloadedmetadata = () => {
                const duration = video.duration / 60; // Em minutos
                if (duration > 12) {
                    this.showDurationError = true;
                    this.file = null;
                    URL.revokeObjectURL(video.src);
                } else {
                    this.showDurationError = false;
                    this.isFileSelected = true; // Mostrar preview temporário
                    this.previewTempVideo();
                }
            };

            video.onerror = () => {
                console.error('Erro ao carregar metadados do vídeo');
                alert('Não foi possível verificar a duração do vídeo. Tente outro arquivo.');
                this.resetState();
                URL.revokeObjectURL(video.src);
            };
        },

        previewTempVideo() {
            const tempPreview = document.getElementById(`temp-video-preview-${moduleId}`);
            tempPreview.src = URL.createObjectURL(this.file.file);
        },

        previewFinalVideo() {
            const finalPreview = document.getElementById(`video-upload-preview-${moduleId}`);
            finalPreview.src = URL.createObjectURL(this.file.file);

        },

        cancelSelection() {
            this.file = null;
            this.isFileSelected = false;
            document.getElementById(`temp-video-preview-${moduleId}`).src = '';
        },

        removeVideo() {
            this.resumable.cancel();
            this.resetState();
        },

        resetState() {
            this.file = null;
            this.isFileSelected = false;
            this.isUploading = false;
            this.isUploaded = false;
            this.showDurationError = false;
            document.getElementById(`video-upload-preview-${moduleId}`).src = '';
            document.getElementById(`temp-video-preview-${moduleId}`).src = '';
            document.getElementById(`success-message-${moduleId}`).style.display = 'none';
            const progressBar = document.getElementById(`progress-bar-${moduleId}`);
            const progressText = document.getElementById(`progress-percentage-${moduleId}`);
            if (progressBar && progressText) {
                progressBar.style.width = '0%';
                progressText.textContent = '0%';
            }
        },
         // Adicione este método novo
         updateProgressBar(progress) {
            this.$nextTick(() => {
                const progressBar = document.getElementById(`progress-bar-${moduleId}`);
                const progressText = document.getElementById(`progress-percentage-${moduleId}`);

                if (progressBar && progressText) {
                    progressBar.style.width = `${progress}%`;
                    progressText.textContent = `${progress}%`;

                    // Forçar repaint (às vezes necessário)
                    progressBar.style.display = 'none';
                    progressBar.offsetHeight; // Trigger reflow
                    progressBar.style.display = 'block';
                }
            });
        },
    }));
});
