import { driver } from "driver.js";
import "driver.js/dist/driver.css";

/**
 * equanto o usuario não interagir com a pagina ele não vai para frente
 *
 *
 */
document.addEventListener("alpine:init", () => {
    Alpine.store("tourguide", {
        openMai: false,
        popoverContent: false,
        popoverContentCreate: false,
        helperContent: false,
        helperContentInit: false,

        // Configurações globais do Driver.js
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

        // Inicia o tour com base na página atual
        toggleTourguide() {
            this.openMai = true;

            // Verifica a página atual e inicia o tour correspondente
            const currentPath = window.location.pathname;
            switch (currentPath) {
                case "/app/teacher/content":
                    this.startContentPageTour();
                    break;
                case "/app/teacher/content/create":
                    this.startCreatePageTour();
                    break;
                default:
                    console.log("Nenhum tour definido para esta página.");
            }
        },

        // Tour para a página de conteúdo
        startContentPageTour() {
            this.resetTourState();

        },

        // Tour para a página de criação de conteúdo
        startCreatePageTour() {
            this.resetTourState();
            this.startDriverTour([
                {
                    element: "#content-input-title",
                    popover: {
                        title: "Título da Disciplina",
                        description: "Insira o título da disciplina aqui.",
                        side: "bottom",
                        align: "start",
                    },
                },
                {
                    element: "#content-input-description",
                    popover: {
                        title: "Descrição da Disciplina",
                        description: "Agora, crie uma breve descrição para a disciplina.",
                        side: "bottom",
                        align: "start",
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

        // Efeito de digitação para as mensagens
        typeWriter(txt) {
            const tourMessage = document.getElementById("tour-message");
            const tempDiv = document.createElement("div");
            let charIndex = 0;
            tourMessage.innerHTML = "";

            const typingInterval = setInterval(() => {
                if (charIndex < txt.length) {
                    tempDiv.innerHTML += txt[charIndex];
                    charIndex++;
                    tourMessage.innerHTML = tempDiv.innerHTML;
                } else {
                    clearInterval(typingInterval);
                }
            }, 50);
        },

        // Função para iniciar o Driver.js com os passos fornecidos
        startDriverTour(steps) {
            const driverObj = driver({
                ...this.driverConfig,
                steps: steps,
                onNext: (step) => {
                    console.log("Próximo passo:", step);
                },
                onPrevious: (step) => {
                    console.log("Passo anterior:", step);
                },
                onDestroyed: () => {
                    console.log("Tour concluído!");
                    this.resetTourState();
                },
            });
            driverObj.drive();
        },

        // Reinicia o estado do tour
        resetTourState() {
            this.popoverContent = false;
            this.helperContent = false;
            this.helperContentInit = true;
            this.openMai = false;
        },
    });
});
