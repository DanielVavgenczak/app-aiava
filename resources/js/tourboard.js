import { driver } from "driver.js";
import "driver.js/dist/driver.css";

document.addEventListener("alpine:init", () => {
    Alpine.store("tourguide", {
        helperInit: false,
        openMai: false,
        showOptionsPageContent: false,
        popoverContentCreate: false,
        driverObj: null, // Adicionamos o driverObj como propriedade do store

        driverConfig: {
            showProgress: false,
            animate: true,
            opacity: 0.75,
            padding: 10,
            allowClose: true,
            doneBtnText: 'Concluir',
            closeBtnText: 'Fechar',
            nextBtnText: 'Próximo',
            prevBtnText: 'Anterior',
        },

        closeTour() {
            this.openMai = false;
            this.showOptionsPageContent = false;
            this.popoverContentCreate = false;
        },

        toggleTourguide() {
            this.openMai = true;
            const currentPath = window.location.pathname;
            switch (currentPath) {
                case "/app/teacher/content":
                    this.helperInit = true;
                    this.popoverContentPage = true;
                    this.startContentPageTour()
                    break;
                case "/app/teacher/content/create":
                    this.popoverContentCreate = true;
                    this.startCreatePageTour();
                    break;
                default:
                    console.log("Nenhum tour definido para esta página.");
            }
        },

        startContentPageTour() {
            this.showOptionsPageContent = true
            //this.contenSearchDiscipline();
        },

        startCreatePageTour() {
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
        startDriverTour(steps) {
            this.driverObj = driver({
                ...this.driverConfig,
                steps: steps,
                onDestroyed: () => {
                    this.resetTourState(); // Reinicia o estado ao concluir o tour
                },
            });
            this.driverObj.drive();
        },

        resetTourState() {
            this.openMai = false;
            this.popoverContentPage = false;
            this.popoverContentCreate = false;
            this.helperInit = false;
        },
    });
});


