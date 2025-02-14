import { driver } from "driver.js";
import "driver.js/dist/driver.css";

document.addEventListener("alpine:init", () => {
    Alpine.store("tourguide", {
        helperInit: false,
        openMai: false,
        showOptionsPageContent: false,
        showOptionsPageContentCreate: false,
        showOptionsPageContentDetails: false,
        driverObj: null, // Adicionamos o driverObj como propriedade do stor
        driverConfig: {
            showProgress: false,
            animate: true,
            opacity: 0.75,
            padding: 60,
            allowClose: true,
            // doneBtnText: 'Concluir',
            // closeBtnText: 'Fechar',
            // nextBtnText: 'Próximo',
            // prevBtnText: 'Anterior',
        },

        closeTour() {
            this.openMai = false;
            this.showOptionsPageContent = false;
            this.showOptionsPageContentCreate = false;
            this.showOptionsPageContentDetails = false;
        },
        toggleTourguide() {
            this.openMai = true;
            const currentPath = window.location.pathname.trim();
            console.log("Current Path:", currentPath);

            const baseContentPath = "/app/teacher/content/";
            const contentWithUuidPattern = /^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/;

            if (currentPath === "/app/teacher/content") {
                console.log("Caso 1: /app/teacher/content");
                this.helperInit = true;
                this.popoverContentPage = true;
                this.startContentPageTour();
            } else if (currentPath === "/app/teacher/content/create") {
                console.log("Caso 2: /app/teacher/content/create");
                this.helperInit = true;
                this.startContentCreate();
            } else if (currentPath.startsWith(baseContentPath) && contentWithUuidPattern.test(currentPath.substring(baseContentPath.length))) {
                console.log("Caso 3: UUID detectado");
                this.showOptionsPageContentDetails = true;
                //this.helperInit = true;
                //this.startContentDetailTour();
            } else {
                console.log("Nenhum tour definido para esta página.");
            }
        },

        async startCreateLesson() {
            this.showOptionsPageContentDetails = false;
            //Define os passos iniciais do tour
            const initialSteps = [
                {
                    element: "#content-lesson-create",
                    popover: {
                        description: "Vou clicar aqui para criar uma nova aula.",
                        side: "right",
                        align: "center",
                    },

                },
            ];
            // Inicia o tour com os passos iniciais
            await this.startDriverTour(initialSteps);
            // Simula o clique no botão após o primeiro passo
            setTimeout(async () => {
                const clickedButton = document.querySelector(".open-new-lesson");
                if (clickedButton) {
                    clickedButton.click(); // Clica no botão automaticamente
                    console.log("Clique automático realizado!");
                    // Aguarda um pequeno delay para garantir que o modal foi aberto
                    await new Promise((resolve) => setTimeout(resolve, 500));
                    // Adiciona o próximo passo ao tour
                    const nextSteps = [
                        {
                            element: "#module-input-title", // Exemplo de um campo dentro do modal
                            popover: {
                                description: "<h3><strong>Você </strong> preencha o título da aula aqui.</h3>",
                                side: "top",
                                align: "start",
                                onNextClick: async () => {
                                    const titleInput = document.querySelector("#module-input-title input");
                                    if (titleInput.value.trim() === "") {
                                        return; // Impede o avanço se o campo estiver vazio
                                    }
                                    // Avança para o próximo passo
                                    this.driverObj.moveNext();
                                    console.error("driverObj não foi inicializado.");
                                },
                                onPopoverRender: (popover, { config, state }) => {
                                    const otheroptions = document.createElement("button");
                                    otheroptions.innerText = "outras opções";
                                    otheroptions.style.float = "left"; // Alinha o botão à esquerda
                                    // Adiciona o botão ao início do footer do popover
                                    if (popover.footerButtons.firstChild) {
                                        popover.footerButtons.insertBefore(otheroptions, popover.footerButtons.firstChild);
                                    } else {
                                        popover.footerButtons.appendChild(otheroptions);
                                    }
                                    otheroptions.addEventListener("click", () => {
                                        this.driverObj.destroy();
                                        this.showOptionsPageContentDetails = true;
                                        Livewire.dispatch('open-modal-module', {open:false})
                                    })
                                },
                            },
                        },
                        {
                            element: "#module-input-send", // Botão de envio
                            popover: {
                                description: "Clique aqui para salvar a aula.",
                                side: "bottom",
                                align: "center",
                                onPopoverRender: (popover, { config, state }) => {
                                    const otheroptions = document.createElement("button");
                                    otheroptions.innerText = "outras opções";
                                    otheroptions.style.float = "left"; // Alinha o botão à esquerda

                                    // Adiciona o botão ao início do footer do popover
                                    if (popover.footerButtons.firstChild) {
                                        popover.footerButtons.insertBefore(otheroptions, popover.footerButtons.firstChild);
                                    } else {
                                        popover.footerButtons.appendChild(otheroptions);
                                    }
                                    otheroptions.addEventListener("click", () => {
                                        this.driverObj.destroy();
                                        this.showOptionsPageContentDetails = true;
                                        Livewire.dispatch('open-modal-module', {open:false})
                                    })
                                },
                            },
                            onHighlightStarted: async () => {
                               console.log('salvar não é para ser automatico')

                                const sendButton = document.querySelector("button#module-input-send");
                                if (sendButton) {
                                    // Adiciona um event listener para detectar o clique no botão "Salvar"
                                    sendButton.addEventListener("click", () => {
                                        console.log("Botão 'Salvar' foi clicado pelo usuário.");
                                        // Fecha o tour após o clique no botão "Salvar"
                                        if (this.driverObj) {
                                            this.driverObj.destroy(); // Finaliza o tour
                                            console.log("Tour encerrado após salvar.");
                                        } else {
                                            console.error("driverObj não foi inicializado.");
                                        }
                                    });
                                } else {
                                    console.error("Botão 'Salvar' não encontrado.");
                                }
                            },
                        },
                    ];
                    // Inicia o tour novamente com os próximos passos
                    await this.startDriverTour(nextSteps,['next']);
                    console.log("Passamos para outro passo! bem aqui");
                } else {
                    console.error("Botão .open-new-lesson não encontrado.");
                }
            }, 1000); // Espera 1 segundo antes de clicar
        },
        // Função para aguardar a presença de um elemento no DOM
        async startAddNewBlock() {
            this.showOptionsPageContentDetails = false;

            // Define os passos iniciais do tour
            const initialSteps = [
                {
                    element: "#tourguid-open-accordion-blocks",
                    popover: {
                        description: "Vou clicar aqui para criar um novo bloco.",
                        side: "right",
                        align: "center",
                    },
                },
            ];

            try {
                // Inicia o tour com os passos iniciais
                await this.startDriverTour(initialSteps);

                // Aguarda um pequeno delay para garantir que o botão esteja visível
                await new Promise((resolve) => setTimeout(resolve, 500));

                // Clica automaticamente no botão para abrir o modal
                const clickedButton = document.querySelector(".open-new-block-lesson");
                if (!clickedButton) {
                    throw new Error("Botão .open-new-block-lesson não encontrado.");
                }

                clickedButton.click();
                console.log("Clique automático realizado!");

                // Aguarda um pequeno delay para garantir que o modal foi aberto
                await new Promise((resolve) => setTimeout(resolve, 500));

                // Define os próximos passos do tour
                const nextSteps = [
                    {
                        element: "#block-open-new-block", // Exemplo de um campo dentro do modal
                        popover: {
                            description: "Vou clicar aqui para abrir a opção de adicionar um novo bloco!",
                            side: "top",
                            align: "start",
                        },
                    },
                ];

                // Inicia o tour novamente com os próximos passos
                await this.startDriverTour(nextSteps);

                // Clica automaticamente no botão dentro do modal
                const buttToClick = document.querySelector(".tour-open-new-block-to-click");
                if (!buttToClick) {
                    throw new Error("Botão .tour-open-new-block-to-click não encontrado.");
                }

                await new Promise((resolve) => setTimeout(resolve, 1000));
                buttToClick.click();
                console.log("Clique automático no botão dentro do modal realizado!");
                // Define os passos finais do tour
                const stepOpenModalNewBlock = [
                    {
                        element: ".tour-guide-new-block", // Exemplo de um campo dentro do modal
                        popover: {
                            description: "Clique aqui para escolher seu vídeo.",
                            side: "top",
                            align: "start",
                            onPopoverRender: (popover, { config, state }) => {
                                console.log("para adicionar um button")
                                const buttonOptions = document.createElement("button");
                                buttonOptions.innerText = "outras opções";
                                buttonOptions.style.float = "left"; // Alinha o botão à esquerda

                                // Adiciona o botão ao início do footer do popover
                                if (popover.footerButtons.firstChild) {
                                    popover.footerButtons.insertBefore(buttonOptions, popover.footerButtons.firstChild);
                                } else {
                                    popover.footerButtons.appendChild(buttonOptions);
                                }
                                buttonOptions.addEventListener("click", () => {
                                    this.driverObj.destroy();
                                    this.showOptionsPageContentDetails = true;
                                    Livewire.dispatch('open-new-block-to-module', {open:false})
                                })
                            },
                        },
                    },
                ];

                // Inicia o tour novamente com os passos finais
                await this.startDriverTour(stepOpenModalNewBlock,['next']);
                console.log("Tour concluído com sucesso!");
            } catch (error) {
                console.error("Erro durante o tour:", error.message);
            }
        },
        startCrudLesson() {
            this.showOptionsPageContentDetails = false;
            let isDropdownClicked = false;
             // Seleciona o botão que abre o dropdown
            const btnDropdown = document.querySelector("button.btn-set-dropdown");

            if (btnDropdown) {
                const details = document.querySelector(".accordion > div > details.dropdown");
                // Adiciona um evento de clique ao botão
                /** Sobrepondo o z-index apenas com o tour ativo */
                btnDropdown.addEventListener("click", (event) => {
                    if (details) {
                        details.style.zIndex = "20000"
                        isDropdownClicked = true
                    }
                });
            }
            this.startDriverTour([
                {
                    element: "#content-lesson-crud",
                    popover: {
                        description: "Clique no ícone para abrir as opções.",
                        side: "right",
                        align: "center",
                        onNextClick: () => {
                            const drivertxt = document.querySelector(".driver-popover > .driver-popover-description");
                            const nextButton = document.querySelector(".driver-popover-navigation-btns button+button");
                            if(!isDropdownClicked) {
                                if (nextButton) {
                                    nextButton.style.backgroundColor = "#ccc";
                                    nextButton.style.cursor = "not-allowed";
                                }
                                if(drivertxt) {
                                    drivertxt.innerHTML = "<p>clique nos 3 pontos</p>"
                                }
                                return;
                            }
                            const details = document.querySelector(".accordion > div > details.dropdown");
                            nextButton.style.backgroundColor = ""; // Remove a cor de fundo
                            nextButton.style.cursor = ""; // Remove o cursor not-allowed
                            this.driverObj.moveNext();
                            details.style.zIndex = "2"
                        }
                    }
                },
                {
                    element: "#content-lesson-delete-btn",
                    popover: {
                        description: "Clique para 'Deletar'",
                        side: "bottom",
                        align: "center",
                    }
                },
            ]);
        },
        // Edit aqui
        startEditLesson() {
            this.showOptionsPageContentDetails = false;
            let isDropdownClicked = false;
             // Seleciona o botão que abre o dropdown
            const btnDropdown = document.querySelector("button.btn-set-dropdown");

            if (btnDropdown) {
                const details = document.querySelector(".accordion > div > details.dropdown");
                // Adiciona um evento de clique ao botão
                /** Sobrepondo o z-index apenas com o tour ativo */
                btnDropdown.addEventListener("click", (event) => {
                    if (details) {
                        details.style.zIndex = "20000"
                        isDropdownClicked = true
                    }
                });
            }
            this.startDriverTour([
                {
                    element: "#content-lesson-crud",
                    popover: {
                        description: "Clique no ícone para abrir as opções.",
                        side: "right",
                        align: "center",
                        onNextClick: () => {
                            const drivertxt = document.querySelector(".driver-popover > .driver-popover-description");
                            const nextButton = document.querySelector(".driver-popover-navigation-btns button+button");
                            if(!isDropdownClicked) {
                                if (nextButton) {
                                    nextButton.style.backgroundColor = "#ccc";
                                    nextButton.style.cursor = "not-allowed";
                                }
                                if(drivertxt) {
                                    drivertxt.innerHTML = "<p>clique nos 3 pontos</p>"
                                }
                                return;
                            }
                            const details = document.querySelector(".accordion > div > details.dropdown");
                            nextButton.style.backgroundColor = ""; // Remove a cor de fundo
                            nextButton.style.cursor = ""; // Remove o cursor not-allowed
                            this.driverObj.moveNext();
                            details.style.zIndex = "2"
                        }
                    }
                },
                {
                    element: "#content-lesson-edit-btn",
                    popover: {
                        description: "Clique para 'Editar'",
                        side: "bottom",
                        align: "center",
                    }
                },
            ]);
        },
        startCreateTaskLesson(){
            this.showOptionsPageContentDetails = false;
            let isDropdownClicked = false;
             // Seleciona o botão que abre o dropdown
            const btnDropdown = document.querySelector("button.btn-set-dropdown");

            if (btnDropdown) {
                const details = document.querySelector(".accordion > div > details.dropdown");
                // Adiciona um evento de clique ao botão
                /** Sobrepondo o z-index apenas com o tour ativo */
                btnDropdown.addEventListener("click", (event) => {
                    if (details) {
                        details.style.zIndex = "20000"
                        isDropdownClicked = true
                    }
                });
            }
            this.startDriverTour([
                {
                    element: "#content-lesson-crud",
                    popover: {
                        description: "Clique no ícone para abrir as opções.",
                        side: "right",
                        align: "center",
                        onNextClick: () => {
                            const drivertxt = document.querySelector(".driver-popover > .driver-popover-description");
                            const nextButton = document.querySelector(".driver-popover-navigation-btns button+button");
                            if(!isDropdownClicked) {
                                if (nextButton) {
                                    nextButton.style.backgroundColor = "#ccc";
                                    nextButton.style.cursor = "not-allowed";
                                }
                                if(drivertxt) {
                                    drivertxt.innerHTML = "<p>clique nos 3 pontos</p>"
                                }
                                return;
                            }
                            const details = document.querySelector(".accordion > div > details.dropdown");
                            nextButton.style.backgroundColor = ""; // Remove a cor de fundo
                            nextButton.style.cursor = ""; // Remove o cursor not-allowed
                            this.driverObj.moveNext();
                            details.style.zIndex = "2"
                        }
                    }
                },
                {
                    element: "#content-lesson-createtask-btn",
                    popover: {
                        description: "Clique para 'criar uma tarefa'",
                        side: "bottom",
                        align: "center",
                    }
                },
            ]);
        },
        startAddBlockToLesson(){
            this.showOptionsPageContentDetails = false;
            let isCollapseOpen = false;
            const btnOpenCollapse = document.querySelector("div.open-collapse-btn");

            if (btnOpenCollapse) {
                // Adiciona um evento de clique ao botão
                /** Sobrepondo o z-index apenas com o tour ativo */
                btnOpenCollapse.addEventListener("click", (event) => {
                    isCollapseOpen = true;
                });
            }
            this.startDriverTour([
                {
                    element: "#content-lesson-add-new-block",
                    popover: {
                        description: "Clique no titulo ou no icone.",
                        side: "right",
                        align: "center",
                        onNextClick: () => {
                            const drivertxt = document.querySelector(".driver-popover > .driver-popover-description");
                            const nextButton = document.querySelector(".driver-popover-navigation-btns button+button");
                            const btnNewBlock = document.querySelector("div > button.newblock")
                            if(!isCollapseOpen) {
                                if (nextButton) {
                                        nextButton.style.backgroundColor = "#ccc";
                                        nextButton.style.cursor = "not-allowed";
                                }
                                if(drivertxt) {
                                    drivertxt.innerHTML = "<p>Por favor clique no titulo ou no icone</p>"
                                }
                                return;
                            }
                            if(btnNewBlock) {
                                console.log('acahamds ele')
                                btnNewBlock.style.zIndex = "20000"
                            }

                            // const details = document.querySelector(".accordion > div > details.dropdown");
                            nextButton.style.backgroundColor = ""; // Remove a cor de fundo
                            nextButton.style.cursor = ""; // Remove o cursor not-allowed
                            this.driverObj.moveNext();
                            btnNewBlock.style.zIndex = "0"
                        }
                    }
                },
                {
                    element: "#content-lesson-new-block-modal",
                    popover: {
                        description: "Clique para 'criar uma tarefa'",
                        side: "bottom",
                        align: "center",
                    }
                },
            ]);
        },
        startContentPageTour() {
            this.showOptionsPageContent = true
        },

        startContentCreate(){
            this.showOptionsPageContentCreate = true;
        },
        startTourCreateContent() {
            this.showOptionsPageContentCreate = false;
            this.startDriverTour([
                {
                    element: "#content-input-title",
                    popover: {
                        title: "Título da Disciplina",
                        description: "Insira o título da disciplina aqui.",
                        side: "bottom",
                        align: "start",
                        onNextClick: () => {
                            const titleInput = document.querySelector("#content-input-title input");
                            if (titleInput.value !=='') {
                                this.intective = true
                                this.driverObj.moveNext(); // Avança para o próximo passo
                            }
                            const nextButton = document.querySelector(".driver-popover-navigation-btns button+button");
                            nextButton.style.backgroundColor = "#ccc";
                            nextButton.style.cursor = "not-allowed";

                        }
                    },
                },
                {
                    element: "#content-input-description",
                    popover: {
                        title: "Descrição da Disciplina",
                        description: "Agora, crie uma breve descrição para a disciplina.",
                        side: "bottom",
                        align: "start",
                        onNextClick: () => {
                            const descriptionInput = document.querySelector("#content-input-description textarea");
                            if (descriptionInput.value !=='') {
                                this.driverObj.moveNext(); // Avança para o próximo passo
                            }
                            const nextButton = document.querySelector(".driver-popover-navigation-btns button+button");
                            nextButton.style.backgroundColor = "#ccc";
                            nextButton.style.cursor = "not-allowed";
                            console.log('usuario não interagiu')
                        }
                    },
                },
                {
                    element: "#content-input-save",
                    popover: {
                        title: "Salvar Disciplina",
                        description: "Clique aqui para salvar a disciplina.",
                        side: "bottom",
                        align: "start",
                    },
                },
            ]);
        },

        contenSearchDiscipline(){
            this.showOptionsPageContent = false;
            this.openMai = false;
            this.startDriverTour([
                {
                    element: "#content-input-search",
                    popover: {
                        description: "Insira o título da disciplina que deseja encontrar.",
                        side: "bottom",
                        align: "start",
                        onNextClick: () => {
                            const searchInput = document.querySelector("#content-input-search");
                            if (searchInput.value !=='') {
                                this.driverObj.moveNext(); // Avança para o próximo passo
                            }
                            const nextButton = document.querySelector(".driver-popover-navigation-btns button+button");
                            nextButton.style.backgroundColor = "#ccc";
                            nextButton.style.cursor = "not-allowed";
                        }
                    },
                },
            ]);
        },
        contenManagerDiscipline(){
            this.showOptionsPageContent = false;
            this.openMai = false;
            this.startDriverTour([
                {
                    element: "#content-card-discipline",
                    popover: {
                        description: "Click aqui e sera redirecionado para a disciplina!",
                        side: "bottom",
                        align: "start",
                        onNextClick: () => {
                            const searchInput = document.querySelector("#content-input-search");
                            if (searchInput.value !=='') {
                                this.driverObj.moveNext(); // Avança para o próximo passo
                            }
                            const nextButton = document.querySelector(".driver-popover-navigation-btns button+button");
                            nextButton.style.backgroundColor = "#ccc";
                            nextButton.style.cursor = "not-allowed";
                        }
                    },
                },
            ]);
        },
        contenCreateDiscipline(){
            this.showOptionsPageContent = false;
            this.openMai = false;
            this.startDriverTour([
                {
                    element: "#content-create-discipline",
                    popover: {
                        description: "Click aqui para criar uma nova disciplina!",
                        side: "right",
                        align: "start",
                        onNextClick: () => {
                            const searchInput = document.querySelector("#content-input-search");
                            if (searchInput.value !=='') {
                                this.driverObj.moveNext(); // Avança para o próximo passo
                            }
                            const nextButton = document.querySelector(".driver-popover-navigation-btns button+button");
                            nextButton.style.backgroundColor = "#ccc";
                            nextButton.style.cursor = "not-allowed";
                        }
                    },
                },
            ]);
        },
        contenDeleteEditDiscipline(){
            this.showOptionsPageContent = false;
            this.openMai = false;
            this.startDriverTour([
                {
                    element: "#content-card-delete-edit",
                    popover: {
                        description: "Click aqui para editar / excluir",
                        side: "right",
                        align: "start",
                        onNextClick: () => {
                            const searchInput = document.querySelector("#content-input-search");
                            if (searchInput.value !=='') {
                                this.driverObj.moveNext(); // Avança para o próximo passo
                            }
                            const nextButton = document.querySelector(".driver-popover-navigation-btns button+button");
                            nextButton.style.backgroundColor = "#ccc";
                            nextButton.style.cursor = "not-allowed";
                        }
                    },
                },
            ]);
        },
        async startDriverTour(steps, btn_actions = [],) {
            let defaultClose = ['close'];
            this.driverObj = driver({
                ...this.driverConfig,
                showButtons:Array.isArray(btn_actions)
                ? [...defaultClose, ...btn_actions] // Combina "close" com os botões fornecidos
                : defaultClose,
                steps: steps,
                onDestroyed: () => {
                    this.resetTourState(); // Reinicia o estado ao concluir o tour
                },
            });
            this.driverObj.drive();
        },

        // discipline show features
        contenDiscipline(){
            this.showOptionsPageContent = false;
            this.openMai = false;
            this.startDriverTour([
                {
                    element: "#content-card-delete-edit",
                    popover: {
                        description: "Click aqui para editar / excluir",
                        side: "right",
                        align: "start",
                        onNextClick: () => {
                            const searchInput = document.querySelector("#content-input-search");
                            if (searchInput.value !=='') {
                                this.driverObj.moveNext(); // Avança para o próximo passo
                            }
                            const nextButton = document.querySelector(".driver-popover-navigation-btns button+button");
                            nextButton.style.backgroundColor = "#ccc";
                            nextButton.style.cursor = "not-allowed";
                        }
                    },
                },
            ]);
        },
        resetTourState() {
            this.openMai = false;
            this.popoverContentPage = false;
            this.popoverContentCreate = false;
            this.helperInit = false;
            this.showOptionsPageContentDetails = false;
        },
    });
});


