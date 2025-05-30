import { AddButtonActions, configDriver, startDriverTour } from "./utils";
Livewire.on("to-teste-continue-created", async () => {
    try {
        // 1. Espera inicial
        await new Promise((resolve) => setTimeout(resolve, 3000));
        // 2. Executa o primeiro tour e ESPERA sua conclusão
        const firstTourResult = await StartTourDisciplineCreateContent();
        if (!firstTourResult) {
            throw new Error("Primeiro tour não retornou resultado válido");
        }
        await new Promise((resolve) => setTimeout(resolve, 7000));
        // 3. Só então executa o segundo tour
        const uploadBloc =await StartTourDisciplineCreateContentUploadBlock();
        if (!uploadBloc) {
            throw new Error("Segundo tour não retornou resultado válido");
        }
    } catch (error) {
        console.error("Erro na sequência:", error);
        // Adicione aqui qualquer tratamento de erro adicional
    }
});
export async function StartTourDisciplineCreateContent() {
    let store = Alpine.store("tourguide");
    store.openMai = false;
    return new Promise(async (resolve) => {
        //const disciplieID = document.getAttribute("data-tour");
        let disciplineID = null;
        const btnCreateLesson = document.querySelector(
            "#content-lesson-create",
        );

        if (!btnCreateLesson) {
            return;
        }

        //if (btnCreateLesson) {
            const driverObj = drivertour({
                ...configDriver,
                steps: [
                    {
                        element: `#${btnCreateLesson.id}`,
                        popover: {
                            description: `Vou clicar no botão "+ Aula"`,
                            showButtons: [""],
                        },
                        onHighlighted: async (el) => {
                            await new Promise((resolve) =>
                                setTimeout(resolve, 3000),
                            );
                            disciplineID = el.getAttribute("data-tour");
                            el.click();
                            await new Promise((resolve) =>
                                setTimeout(resolve, 1000),
                            );
                            await driverObj.moveNext();
                        },
                    },
                    {
                        element: `#lesson-title`,
                        popover: {
                            description: "Escreva o Titulo da aula",
                            onNextClick: async () => {
                                const title = document.querySelector(
                                    `#lesson-input-title`,
                                )
                                if(title.value.trim() === "") {
                                    alert("por favor preencha o campo de texto");
                                    return;
                                }
                                await driverObj.moveNext();
                            },
                            onPopoverRender: (popover, { config, state }) => {
                                const actions = AddButtonActions(
                                    popover,
                                    "voltar ao inicio",
                                );
                                actions.addEventListener("click", () => {
                                    Livewire.dispatch("open-modal-module", {
                                        open: false,
                                    });
                                    driverObj.destroy();
                                    store.openMai = true;
                                });
                            },
                        },
                    },
                    {
                        element: `#lesson-actions-buttons`,
                        popover: {
                            description:
                                "Clique em 'Cadastrar' para salvar a aula ou em 'Cancelar' para cancelar o cadastro.",
                            onPopoverRender: (popover, { config, state }) => {
                                const actions = AddButtonActions(
                                    popover,
                                    "voltar ao inicio",
                                );
                                actions.addEventListener("click", () => {
                                    Livewire.dispatch("open-modal-module", {
                                        open: false,
                                    });
                                    driverObj.destroy();
                                    store.openMai = true;
                                });
                            },
                        },
                        onHighlighted: async (el) => {
                            const toCloseActions = () => {
                                Livewire.dispatch("open-modal-module", {
                                    open: false,
                                });
                                store.openMai = false;
                                driverObj.destroy();
                            };
                            const btnCancel = el.querySelector(`#module-input-cancel`);
                            const btnCreate = el.querySelector(`#module-input-create`);
                            if (!btnCreate && !btnCancel) {
                                console.error("element not found");
                                return;
                            }
                            btnCancel.addEventListener("click", toCloseActions);
                            btnCreate.addEventListener("click", async () => {
                                Livewire.dispatch("to-teste-register", {
                                    open: false,
                                });
                                store.openMai = false;
                                resolve(true);
                                await driverObj.destroy();
                            });
                        },
                    },
                ],
            });
            await driverObj.drive();
    });
}
export async function StartTourDisciplineCreateContentUploadBlock() {
    let store = Alpine.store("tourguide");
    store.openMai = false;
    console.log('inicio upload bloco ...')
    return new Promise(async (resolve) => {
        const qtMpdules = document.querySelectorAll("div.module-card").length;
        const moduleText =
            qtMpdules > 1
                ? `Em qual aula deseja adicionar um bloco?`
                : `Clique na aula para adicinar um bloco`;
        const driverObj = drivertour({
            ...configDriver,
            steps: [
                {
                    element: `#module-list-all`,
                    popover: {
                        description: moduleText,
                        showButtons: [""],
                    },
                    onHighlighted: async (el) => {
                        const modules = el.querySelectorAll(`div.module-card`);
                        let moduleSelected = false;
                        if (!modules || modules.length === 0) return;
                        modules.forEach((module) => {
                            module.style.zIndex = "200000000";
                            module.addEventListener("click", async (event) => {
                                if (moduleSelected) return;
                                event.preventDefault();
                                moduleSelected = true;
                                // Remove z-index das disciplinas não selecionadas
                                modules.forEach((otherModule) => {
                                    if (module !== otherModule) {
                                        otherModule.style.zIndex = "0";
                                    }
                                });
                               // module.style.bac;
                                //const moduleId = module.getAttribute('id');
                                const dataId = module.getAttribute("data-tour");
                               let testeDoId = dataId;
                                // const tourOperations = `tour-operations-${dataId}`
                                // const disciplinaInputEditTitle = `discipline-edit-form-title-${dataId}`
                                // const disciplineInputEditDescription = `discipline-edit-form-description-${dataId}`
                                // const disciplineFormActions = `discipline-edit-form-actions-${dataId}`
                                // Destrói o tour atual
                                await driverObj.destroy();
                                // Cria um novo tour com os passos atualizados
                                const secondStep = [
                                    {
                                        element: `#accordion-module-${dataId}`,
                                        popover: {
                                            description: `Vamos abrir as opções na aula selecionada`,
                                            side: "bottom",
                                            align: "start",
                                            showButtons: [""],
                                        },
                                        onHighlighted: async (el) => {
                                            await new Promise((resolve) =>
                                                setTimeout(resolve, 3000),
                                            );
                                            await driverObj.moveNext();
                                        },
                                    },
                                    {
                                        element: `#open-accordion-block-${dataId}`, // Seleciona o elemento com base no ID
                                        popover: {
                                            description: `Vou clicar e abrir para listar os blocos`,
                                            side: "bottom",
                                            align: "start",
                                            showButtons: [""],
                                        },
                                        onHighlighted: async (el) => {
                                            await new Promise((resolve) =>
                                                setTimeout(resolve, 3000),
                                            );
                                            const btnOpenAccordion =
                                                document.querySelector(
                                                    `#open-accordion-block-${dataId}`,
                                                );
                                            await btnOpenAccordion.click();
                                            await driverObj.moveNext();
                                        },
                                    },
                                    {
                                        element: `#block-open-new-block-${dataId}`,
                                        popover: {
                                            description: `Vou clicar aqui para abrir a opção de adiconar um bloco`,
                                            side: "right",
                                            align: "center",
                                            showButtons: [""],
                                        },
                                        onHighlighted: async (el) => {
                                            await new Promise((resolve) =>
                                                setTimeout(resolve, 3500),
                                            );
                                            await el.click();
                                            // el.click(); // Simula o clique no botão de edição
                                            await driverObj.moveNext();
                                        },
                                    },
                                    // {
                                    //     element: `#open-module-input-content-description-${dataId}`, // Seleciona o elemento com base no ID
                                    //     popover: {
                                    //         description: `
                                    //             <div class="p-4"><p class="text-base">
                                    //             Caso queira insira uma descrição para o bloco ou clique 'Proximo'
                                    //             </div></p>`,
                                    //         side: "top",
                                    //         align: "start",
                                    //         onPopoverRender: (popover, { config, state }) => {
                                    //             const actions =  AddButtonActions(popover, "voltar ao inicio")
                                    //             actions.addEventListener('click', async () => {
                                    //                // Livewire.dispatch('to-close')
                                    //                 await driverObj.destroy();
                                    //                 store.openMai = true;
                                    //             })
                                    //         }
                                    //     },
                                    //     // onHighlighted: async(el) => {
                                    //     //     await new Promise((resolve) => setTimeout(resolve, 3500));
                                    //     //    // el.classList.add('driver-active-element')
                                    //     //     //await driverObj.moveNext();
                                    //     // }
                                    // },
                                    {
                                        element: `#browser-file-${dataId}`, // Seleciona o elemento com base no ID
                                        popover: {
                                            description: `Clique no icone para adiconar um novo video`,
                                            side: "top",
                                            align: "start",
                                            // onPopoverRender: (popover, { config, state }) => {
                                            //     const actions =  AddButtonActions(popover, "voltar ao inicio")
                                            //     actions.addEventListener('click', async () => {
                                            //         Livewire.dispatch('to-close')
                                            //         await driverObj.destroy();
                                            //         store.openMai = true;
                                            //     })
                                            // }
                                        },
                                        onHighlighted: async (el) => {
                                            //await new Promise((resolve) => setTimeout(resolve, 3000));
                                            const videoElement =
                                                document.querySelector(
                                                    `#temp-video-preview-${dataId}`,
                                                );
                                            if (!videoElement) {
                                                console.error(
                                                    "Elemento de vídeo não encontrado",
                                                );
                                                return;
                                            }

                                            // Verifica se já tem src definido
                                            if (
                                                videoElement.src &&
                                                videoElement.src !== ""
                                            ) {
                                                console.log(
                                                    "Vídeo já selecionado - avançando",
                                                );
                                                await driverObj.moveNext();
                                                return;
                                            }

                                            // Cria um observador para detectar quando o src for alterado
                                            const observer =
                                                new MutationObserver(
                                                    async (mutations) => {
                                                        for (const mutation of mutations) {
                                                            if (
                                                                mutation.type ===
                                                                    "attributes" &&
                                                                mutation.attributeName ===
                                                                    "src"
                                                            ) {
                                                                if (
                                                                    videoElement.src &&
                                                                    videoElement.src !==
                                                                        ""
                                                                ) {
                                                                    console.log(
                                                                        "Vídeo selecionado detectado - avançando",
                                                                    );
                                                                    observer.disconnect(); // Para de observar
                                                                    await driverObj.moveNext();
                                                                }
                                                            }
                                                        }
                                                    },
                                                );

                                            // Configura o observador
                                            observer.observe(videoElement, {
                                                attributes: true, // Observa mudanças em atributos
                                                attributeFilter: ["src"], // Apenas para o atributo src
                                            });

                                            // Timeout de segurança (opcional)
                                            setTimeout(() => {
                                                observer.disconnect();
                                                console.log(
                                                    "Timeout - observador removido",
                                                );
                                            }, 30000); // 30 segundos
                                        },
                                    },
                                    {
                                        element: `#temp-video-preview-${dataId}`, // Seleciona o elemento com base no ID
                                        popover: {
                                            description: `Verificar se está ok, clique em 'Proximo' para continuar o upload`,
                                            side: "top",
                                            align: "start",
                                            onPopoverRender: (
                                                popover,
                                                { config, state },
                                            ) => {
                                                const actions =
                                                    AddButtonActions(
                                                        popover,
                                                        "Voltar ao inicio",
                                                    );
                                                actions.addEventListener(
                                                    "click",
                                                    async () => {
                                                        //Livewire.dispatch('to-close')
                                                        await driverObj.destroy();
                                                        store.openMai = true;
                                                    },
                                                );
                                            },
                                        },
                                        onNextClick: async () => {
                                            console.log("click para p roi");
                                        },
                                        // onHighlighted: async(el) => {
                                        //     await new Promise((resolve) => setTimeout(resolve, 3000));
                                        //     console.log('Ue', el)
                                        //     return;
                                        //     await driverObj.moveNext();
                                        // }
                                    },
                                    {
                                        element: `#temp-preview-actions-${dataId}`, // Seleciona o elemento com base no ID
                                        popover: {
                                            description: `Clique em 'Enviar' para enviar o video`,
                                            side: "top",
                                            align: "start",
                                            onPopoverRender: (
                                                popover,
                                                { config, state },
                                            ) => {
                                                const actions =
                                                    AddButtonActions(
                                                        popover,
                                                        "Voltar ao inicio",
                                                    );
                                                actions.addEventListener(
                                                    "click",
                                                    async () => {
                                                        //Livewire.dispatch('to-close')
                                                        await driverObj.destroy();
                                                        store.openMai = true;
                                                    },
                                                );
                                            },
                                        },
                                        onHighlighted: async (el) => {
                                            //await new Promise((resolve) => setTimeout(resolve, 3000));
                                            const btnSendVideoBlock =
                                                el.querySelector(
                                                    `#lesson-send-video-block-content-${dataId}`,
                                                );
                                            if (!btnSendVideoBlock) {
                                                console.log("fudeo");
                                                return;
                                            }
                                            btnSendVideoBlock.addEventListener(
                                                "click",
                                                async () => {
                                                    await driverObj.moveNext();
                                                },
                                            );
                                            //console.log('Bateu para terminar')
                                        },
                                    },
                                    {
                                        element: `#progress-bar-container-${dataId}`, // Seleciona o elemento com base no ID
                                        popover: {
                                            description:"Aguarde enquanto o video e enviado",
                                            side: "top",
                                            align: "start",
                                            // onPopoverRender: (popover, { config, state }) => {
                                            //     const actions =  AddButtonActions(popover, "voltar  ao inicio")
                                            //     actions.addEventListener('click', async () => {
                                            //         //Livewire.dispatch('to-close')
                                            //         await driverObj.destroy();
                                            //         store.openMai = true;
                                            //     })
                                        },
                                        onHighlighted: async (el) => {
                                            await new Promise((resolve) =>
                                                setTimeout(resolve, 1000),
                                            );
                                            //console.log('Bateu para terminar')
                                            resolve(true);
                                            await driverObj.destroy();
                                            console.log("travamos here");
                                        },
                                    },
                                ];

                                // Cria um novo tour com os passos atualizados
                                const updatedDriverObj = drivertour({
                                    ...configDriver,
                                    steps: secondStep,
                                });

                                // Inicia o novo tour
                                await updatedDriverObj.drive();
                            });
                        });
                    },
                },
            ],
        });
        await driverObj.drive();
    });
}

export function StartTourCreateActivitTask() {
    let store = Alpine.store("tourguide");
    store.openMai = false;
    const createTask = document.querySelector("#create-activity-task");
    if (!createTask) return;
    const disciplieID = createTask.getAttribute("data-tour");

    return new Promise(async (resolve) => {
        const driverObj = drivertour({
            ...configDriver,
            steps: [
                {
                    element: `#create-title-question-${disciplieID}`,
                    popover: {
                        description: "Descreva aqui a primeira questão",
                        //showButtons: [""],
                        onNextClick: async () => {
                            const titleOfQuestion =
                                document.querySelector(`#id_titleofquestion`);
                            if (!titleOfQuestion) return;
                            if (!titleOfQuestion.value.trim()) {
                                alert("por favor preencha o campo de texto");
                                return;
                            }
                            await driverObj.moveNext();
                        },
                    },
                },
                {
                    element: `#question-select-types-${disciplieID}`,
                    popover: {
                        description:
                            "Agora escolha o tipo da questão que deseja criar",
                    },
                    onHighlighted: async (el) => {
                        await new Promise((resolve) =>
                            setTimeout(resolve, 100),
                        );
                        const buttonTypeRadio = el.querySelector(
                            `#question-select-radio-${disciplieID}`,
                        );
                        const buttonTypeText = el.querySelector(
                            `#question-select-text-${disciplieID}`,
                        );
                        if (!buttonTypeRadio || !buttonTypeText) {
                            return;
                        }
                        buttonTypeRadio.addEventListener("click", async () => {
                            if (driverObj) {
                                await new Promise((resolve) =>
                                    setTimeout(resolve, 100),
                                );
                                await driverObj.destroy();
                            }
                            await new Promise((resolve) =>
                                setTimeout(resolve, 1000),
                            );
                            const updateDriveToRadioQuestion = drivertour({
                                ...configDriver,
                                steps: [
                                    {
                                        element: `#create-question-option-${disciplieID}`,
                                        popover: {
                                            description:
                                                "Digite a opção da questão que deseja criar",

                                        },

                                    },
                                    {
                                        element: `#create-question-option-add-${disciplieID}`,
                                        popover:{
                                            description: "Clique no botão '+' para adicionar"
                                        }
                                    },
                                    {
                                        element: `#question-options-list-${disciplieID}`,
                                        popover: {
                                            description:
                                                "Aqui esta a lista das opções criadas, crie no minimo 3 opções",
                                            //showButtons: [""],
                                            onPopoverRender: (
                                                popover,
                                                { config, state },
                                            ) => {
                                                const addMore =
                                                    AddButtonActions(
                                                        popover,
                                                        "+ Opção",
                                                    );
                                                addMore.addEventListener(
                                                    "click",
                                                    async () => {
                                                        await updateDriveToRadioQuestion.moveNext([1]);
                                                    },
                                                );
                                            },
                                        },
                                    },
                                    // {
                                    //     element: `#create-question-associate-module-${disciplieID}`,
                                    //     popover: {
                                    //         description:
                                    //             "Selecione a disciplina que deseja vincular essa Atividade ou 'Voltar ao inicio'",
                                    //         onPopoverRender: (
                                    //             popover,
                                    //             { config, state },
                                    //         ) => {
                                    //             const actions =
                                    //                 AddButtonActions(
                                    //                     popover,
                                    //                     "Voltar ao inicio",
                                    //                 );
                                    //             actions.addEventListener(
                                    //                 "click",
                                    //                 async () => {
                                    //                     await updateDriveToTextQuestion.destroy();
                                    //                     store.openMai = true;
                                    //                 },
                                    //             );
                                    //         },
                                    //     },
                                    // },
                                ],
                            });
                            await updateDriveToRadioQuestion.drive();
                            //return;
                        });
                        buttonTypeText.addEventListener("click", async () => {
                            if (driverObj) {
                                await new Promise((resolve) =>
                                    setTimeout(resolve, 100),
                                );
                                await driverObj.destroy();
                            }
                            await new Promise((resolve) =>
                                setTimeout(resolve, 1000),
                            );
                            const updateDriveToTextQuestion = drivertour({
                                ...configDriver,
                                steps: [
                                    {
                                        element: `#create-question-button-text-${disciplieID}`,
                                        popover: {
                                            description:
                                                "Vamos criar uma questão de texto",
                                        },
                                        onHighlighted: async (el) => {
                                            await new Promise((resolve) =>
                                                setTimeout(resolve, 1000),
                                            );
                                            el.click();
                                            await new Promise((resolve) =>
                                                setTimeout(resolve, 1000),
                                            );
                                            await driverObj.moveNext();
                                        },
                                    },
                                    {
                                        element: `#question-created-${disciplieID}`,
                                        popover: {
                                            description:
                                                "Aqui a questão que você acabou de criar",
                                            showButtons: [""],
                                        },
                                        onHighlighted: async (el) => {
                                            await new Promise((resolve) =>
                                                setTimeout(resolve, 3000),
                                            );
                                            await updateDriveToTextQuestion.moveNext();
                                        },
                                    },
                                    {
                                        element: `#create-question-associate-module-${disciplieID}`,
                                        popover: {
                                            description:
                                                "Selecione a disciplina que deseja vincular essa Atividade ou 'Voltar ao inicio'",
                                            onPopoverRender: (
                                                popover,
                                                { config, state },
                                            ) => {
                                                const actions =
                                                    AddButtonActions(
                                                        popover,
                                                        "Voltar ao inicio",
                                                    );
                                                actions.addEventListener(
                                                    "click",
                                                    async () => {
                                                        await updateDriveToTextQuestion.destroy();
                                                        store.openMai = true;
                                                    },
                                                );
                                            },
                                        },
                                    },
                                ],
                            });
                            await updateDriveToTextQuestion.drive();
                        });
                    },
                },
            ],
        });

        await driverObj.drive();
    });
}
