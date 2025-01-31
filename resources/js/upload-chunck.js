import Resumable from "resumablejs";
document.addEventListener("alpine:init", ()=> {
    Alpine.data("chunckupload", (module_id)=> ({
        urldatatoken: null,
        browse: null,
        url: null,
        id:  module_id,
        resumable: null,
        init() {
            this.urldatatoken = document.querySelector('meta[name="csrf-token"]').getAttribute("content")
            this.browse = document.getElementById(`browser-file-${this.id}`);
            if (this.browse) {
                this.url = this.browse.getAttribute("data-url");
                this.resumable = new Resumable({
                    target: this.url,
                    simultaneousUploads: 3,
                    query: {
                        _token: this.urldatatoken,
                    }, // CSRF token
                    fileType: ["mp4"],
                    chunkSize: 1 * 1024 * 1024, // default is 1*1024*1024, this should be less than your maximum limit in php.ini
                    headers: {
                        Accept: "application/json",
                    },
                    testChunks: false,
                    throttleProgressCallbacks: 1,
                }),
                this.resumable.assignBrowse(document.getElementById(`browser-file-${this.id}`));
                this.resumable.assignDrop(document.getElementById(`browser-file-${this.id}`));

                // Eventos do Resumable.js
                this.resumable.on("fileAdded", (file) => {
                    console.log("Arquivo adicionado:", file.fileName);
                     // Valida a duração do vídeo antes de iniciar o upload
                     this.validateVideoDuration(file);
                    //this.resumable.upload(); // Inicia o upload automaticamente
                         // Mostra a barra de progresso
                    //document.getElementById(`progress-bar-container-${this.id}`).style.display = "block";

                });

                this.resumable.on("fileSuccess", (file, message) => {
                    console.log("Upload concluído:", file.fileName);
                    console.log("Resposta do servidor:", message);
                    // Oculta a barra de progresso após o upload
                    document.getElementById(`progress-bar-container-${this.id}`).style.display = "none";

                    const successMessage = document.getElementById(`success-message-${this.id}`);
                    successMessage.style.display = "flex";
                    // Exibe o vídeo enviado
                    const videoPreview = document.getElementById(`video-upload-preview-${this.id}`);
                    const videoContainer = document.getElementById(`video-preview-load-${this.id}`);
                    videoPreview.src = URL.createObjectURL(file.file); // Define a URL do vídeo
                    videoContainer.style.display = "flex"; // Exibe o contêiner do vídeo
                });

                this.resumable.on("fileError", (file, message) => {
                    console.error("Erro no upload:", file.fileName, message);
                            // Oculta a barra de progresso em caso de erro
                document.getElementById(`progress-bar-container-${this.id}`).style.display = "none";
                });

                this.resumable.on("progress", () => {
                    const progress = Math.floor(this.resumable.progress() * 100);
                    console.log("Progresso do upload:", progress + "%");
                    // Atualiza a barra de progresso
                const progressBar = document.getElementById(`progress-bar-${this.id}`);
                progressBar.style.width = `${progress}%`;
                progressBar.setAttribute("aria-valuenow", progress);
                });
            }
        },
        uploadStart()
        {
            console.log('clado aqui',  this.url)
            console.log('URL:',  this.id);
        },
        validateVideoDuration(file) {
            const tempVideo = document.getElementById(`temp-video-${this.id}`);
            const warningMessage = document.getElementById(`message-warning-duration-${this.id}`);

            // Carrega o vídeo no elemento <video> temporário
            tempVideo.src = URL.createObjectURL(file.file);

            // Quando os metadados do vídeo são carregados, verifica a duração
            tempVideo.onloadedmetadata = () => {
                const duration = tempVideo.duration; // Duração em segundos
                console.log("Duração do vídeo:", duration);

                if (duration > 720) { // 12 minutos = 720 segundos
                    // Exibe a mensagem de erro
                    warningMessage.style.display = "flex";

                    // Remove o arquivo do Resumable.js
                    this.resumable.removeFile(file);
                } else {
                    // Oculta a mensagem de erro (caso esteja visível)
                    warningMessage.style.display = "none";

                    // Inicia o upload
                    this.resumable.upload();

                    // Mostra a barra de progresso
                    document.getElementById(`progress-bar-container-${this.id}`).style.display = "block";
                }
            };
        }
    }))
});
// let urldata = browserFile.getAttribute("data-url");
// let urldatatoken = document.querySelector('meta[name="csrf-token"]').getAttribute("content");
// let inputVideo = document.querySelector("fileInputVideo");
// let messaWarningDuration = document.getElementById("message-warning-duration");
// let videoContent = document.getElementById("video-preview-load");
// let videoPreviewLoad = document.querySelector("#video-upload-preview");

// const resumable = new Resumable({
//     target: urldata,
//     simultaneousUploads: 3,
//     query: {
//         _token: urldatatoken,
//     }, // CSRF token
//     fileType: ["mp4"],
//     chunkSize: 1 * 1024 * 1024, // default is 1*1024*1024, this should be less than your maximum limit in php.ini
//     headers: {
//         Accept: "application/json",
//     },
//     testChunks: false,
//     throttleProgressCallbacks: 1,
// });


// document.addEventListener("alpine:init", () => {
//     Alpine.data("chunckupload", () => ({
//         resumable: new Resumable({
//             target: urldata,
//             query: {
//                 _token: urldatatoken,
//             }, // CSRF token
//             fileType: ["mp4"],
//             chunkSize: 1 * 1024 * 1024,
//             headers: {
//                 Accept: "application/json",
//             },
//             testChunks: false,
//             throttleProgressCallbacks: 1,
//         }),
//         startUpload()
//         {
//             if(!this.resumable.support){
//                 console.log('not supported por que não sei ')
//             }
//             this.resumable.assignBrowse(document.getElementById("browser-file"));
//             this.resumable.on("fileAdded", async (file, event) => {
//                 console.log('funciionaldldldlso', this.resumable.support)
//                     // trigger when file picked
//                     const video = document.createElement("video");
//                     video.preload = "metadata";
//                     video.onloadedmetadata = function () {
//                         window.URL.revokeObjectURL(video.src);

//                         const duration = video.duration; // Duração em segundos
//                         console.log("Duração do vídeo:", duration);

//                         if (duration > durationValidation) {
//                             // 600 segundos = 10 minutos
//                             messaWarningDuration.style.display = "block";
//                             //alert('O vídeo deve ter no máximo 10 minutos.');
//                             this.resumable.removeFile(file); // Remove o arquivo do Resumable
//                             resetFields(); // Limpa os campos relacionados ao upload
//                         } else {
//                             console.log("Vídeo aceito.");
//                             videoPreviewLoad.setAttribute(
//                                 "src",
//                                 URL.createObjectURL(file.file),
//                             );
//                             videoPreviewLoad.load();
//                             videoContent.classList.remove("hidden");
//                             showProgress();
//                             this.resumable.upload(); // Inicia o upload apenas se for válido
//                         }
//                     };

//                     video.onerror = function () {
//                         alert("Erro ao carregar o vídeo. Verifique se o arquivo é válido.");
//                        this.resumable.removeFile(file); // Remove o arquivo do Resumable
//                         resetFields(); // Limpa os campos relacionados ao upload
//                     };

//                     video.src = URL.createObjectURL(file.file); // Gera uma URL temporária para o arquivo
//                     // showProgress();
//                     // resumable.upload() // to actually start uploading.
//                 });

//                 this.resumable.on("fileProgress", function (file) {
//                     // trigger when file progress update
//                     updateProgress(Math.floor(file.progress() * 100));
//                 });

//                 this.resumable.on("fileSuccess", function (file, response) {
//                     // trigger when file upload complete
//                     response = JSON.parse(response);
//                     console.log("path", file.path, response);

//                     document.querySelector("#videoPreview").setAttribute("src", response.path);
//                     document.querySelector(".card-footer").style.display = "block";
//                 });

//                 this.resumable.on("fileError", function (file, response) {
//                     // trigger when there is any error

//                     console.log("No errro upload:", response, file);
//                     alert("file uploading error.");
//                 });
//         }
//     }));
// })
// let browserFile = document.getElementById("browser-file");
// let urldata = browserFile.getAttribute("data-url");
// let urldatatoken = document.querySelector('meta[name="csrf-token"]').getAttribute("content");
// let inputVideo = document.querySelector("fileInputVideo");
// let messaWarningDuration = document.getElementById("message-warning-duration");
// let videoContent = document.getElementById("video-preview-load");
// let videoPreviewLoad = document.querySelector("#video-upload-preview");
// // const durationValidation = 720;
// const resumable = new Resumable({
//     target: urldata,
//     simultaneousUploads: 3,
//     query: {
//         _token: urldatatoken,
//     }, // CSRF token
//     fileType: ["mp4"],
//     chunkSize: 1 * 1024 * 1024, // default is 1*1024*1024, this should be less than your maximum limit in php.ini
//     headers: {
//         Accept: "application/json",
//     },
//     testChunks: true,
//     throttleProgressCallbacks: 1,
// });

// if (!resumable.support) {
//     console.log('deu paiii')
// }
// resumable.assignBrowse(browserFile);

// resumable.on("fileAdded", function (file) {
//     console.log("Call")
//     // trigger when file picked
//     const video = document.createElement("video");
//     video.preload = "metadata";
//     video.onloadedmetadata = function () {
//         window.URL.revokeObjectURL(video.src);

//         const duration = video.duration; // Duração em segundos
//         console.log("Duração do vídeo:", duration);

//         if (duration > durationValidation) {
//             // 600 segundos = 10 minutos
//             messaWarningDuration.style.display = "block";
//             //alert('O vídeo deve ter no máximo 10 minutos.');
//             resumable.removeFile(file); // Remove o arquivo do Resumable
//             resetFields(); // Limpa os campos relacionados ao upload
//         } else {
//             console.log("Vídeo aceito.");
//             videoPreviewLoad.setAttribute(
//                 "src",
//                 URL.createObjectURL(file.file),
//             );
//             videoPreviewLoad.load();
//             videoContent.classList.remove("hidden");
//             showProgress();
//             resumable.upload(); // Inicia o upload apenas se for válido
//         }
//     };

//     video.onerror = function () {
//         alert("Erro ao carregar o vídeo. Verifique se o arquivo é válido.");
//         resumable.removeFile(file); // Remove o arquivo do Resumable
//         resetFields(); // Limpa os campos relacionados ao upload
//     };

//     video.src = URL.createObjectURL(file.file); // Gera uma URL temporária para o arquivo
//     // showProgress();
//     // resumable.upload() // to actually start uploading.
// });

// resumable.on("fileProgress", function (file) {
//     // trigger when file progress update
//     updateProgress(Math.floor(file.progress() * 100));
// });

// resumable.on("fileSuccess", function (file, response) {
//     // trigger when file upload complete
//     response = JSON.parse(response);
//     console.log("path", file.path, response);

//     document.querySelector("#videoPreview").setAttribute("src", response.path);
//     document.querySelector(".card-footer").style.display = "block";
// });

// resumable.on("fileError", function (file, response) {
//     // trigger when there is any error

//     console.log("No errro upload:", response, file);
//     alert("file uploading error.");
// });

// let progress = document.querySelector(".progress");

// function showProgress() {
//     if (!progress) {
//         console.log("elemtento progress nao encontrado");
//         return;
//     }
//     let progressBar = document.querySelector(".pregress-bar");

//     if (progressBar) {
//         progressBar.style.width = "0%"; // Define a largura como 0%
//         progressBar.textContent = "0%"; // Atualiza o texto dentro da barra
//         progressBar.classList.remove("bg-success");
//     }
//     progress.style.display = "block";
// }

// function updateProgress(value) {
//     if (!progress) {
//         console.error("Elemento '.progress' não encontrado.");
//         return;
//     }

//     let progressBar = progress.querySelector(".progress-bar"); // Busca a barra de progresso

//     if (progressBar) {
//         progressBar.style.width = `${value}%`; // Atualiza a largura da barra
//         progressBar.textContent = `${value}%`; // Atualiza o texto dentro da barra
//     }
// }

// function hideProgress() {
//     // progress.hide();
//     if (!progress) {
//         console.error("Elemento '.progress' não encontrado.");
//         return;
//     }
//     progress.style.display = "none"; // Oculta o contêiner de progresso
// }
// function resetFields() {
//     // Limpa o campo de seleção de arquivos
//     // let browseFile = document.getElementById("browseFile");
//     if (browseFile) {
//         browseFile.value = ""; // Para limpar o input de arquivos
//     }

//     // Limpa a barra de progresso
//     let progressBar = document.querySelector(".progress-bar");
//     if (progressBar) {
//         progressBar.style.width = "0%";
//         progressBar.textContent = "0%";
//         progressBar.classList.remove("bg-success");
//     }

//     // Oculta o progresso
//     let progress = document.querySelector(".progress");
//     if (progress) {
//         progress.style.display = "none";
//     }

//     // Oculta o vídeo preview
//     let videoPreview = document.querySelector("#videoPreview");
//     if (videoPreview) {
//         videoPreview.removeAttribute("src"); // Remove o atributo src
//         videoPreview.load(); // Redefine o elemento de vídeo
//     }

//     // Oculta qualquer mensagem de sucesso/erro
//     document.querySelector(".card-footer").style.display = "none";
// }

// document.addEventListener("alpine:init", () => {
//     Alpine.data("chuncks", () => ({
//         progress: 0,
//         videoPreviewUrl: null,
//         message: '',
//         name: 'daniel',
//         alertClass: '',
//         resumable: null,

//         init() {
//             // Inicializar Resumable.js ao iniciar o componente
//             this.resumable = new Resumable({
//                 target:'/upload', //"{{ route('media.upload') }}",
//                 query: { _token: '{{ csrf_token() }}' },
//                 simultaneousUploads: 1,
//                 chunkSize: 10 * 1024 * 1024, // 10MB chunks
//                 testChunks: false,
//                 throttleProgressCallbacks: 1,
//             });

//             // Associar eventos do Resumable.js
//             this.resumable.assignBrowse(document.getElementById('browseFile'));
//             this.setupResumableEvents();

//             // Associar elemento de navegação (botão)
//         },

//         setupResumableEvents() {
//             this.resumable.on('fileAdded', (file) => {
//                 console.log('Arquivo adicionado:', file);
//                 this.previewVideo(file);
//                 this.message = 'Carregando...';
//                 this.alertClass = 'alert-info';
//                 this.resumable.upload(); // Iniciar upload
//             });

//             this.resumable.on('fileProgress', (file) => {
//                 this.progress = Math.floor(file.progress() * 100);
//             });

//             this.resumable.on('fileSuccess', (file, response) => {
//                 this.message = 'Upload concluído com sucesso!';
//                 this.alertClass = 'alert-success';
//                 this.progress = 100; // Forçar 100% no final
//             });

//             this.resumable.on('fileError', (file, response) => {
//                 this.message = 'Erro no upload. Por favor, tente novamente.';
//                 this.alertClass = 'alert-danger';
//                 this.progress = 0;
//             });
//         },

//         previewVideo(file) {
//             this.videoPreviewUrl = URL.createObjectURL(file.file);
//         },

//         resetMessages() {
//             this.message = '';
//             this.alertClass = '';
//             this.progress = 0;
//         },
//     }));
// });
