import { configDriver, AddButtonActions } from "../utils";
const store = Alpine.store("tourguide");

console.log("carregando student discipline tour");

export async function StartTourStudentDiscipline() {
    store.openMai = false;

    const driverObj = drivertour({
        ...configDriver,
        steps: [
            {
                element: "#discipline-card",
                popover: {
                    description:
                        "Vamos acessar a disciplina em que você foi cadastrado.",
                    side: "bottom",
                    align: "center",
                },
                onHighlighted: async (el) => {
                    await new Promise((r) => setTimeout(r, 1000));
                    el.click();
                    await new Promise((r) => setTimeout(r, 5000));
                    await driverObj.moveNext();
                },
            },
            {
                element: "#discipline-access-btn",
                popover: {
                    description:
                        "Vou clicar em 'Acessar' para entrar na disciplina e ver todas as aulas e atividades.",
                    side: "top",
                    align: "center",
                    onPopoverRender: (popover) => {
                        const actions = AddButtonActions(
                            popover,
                            "Finalizar Tour",
                        );
                        actions.addEventListener("click", async () => {
                            await driverObj.destroy();
                            store.openMai = true;
                        });
                    },
                },
                onHighlighted: async (el) => {
                    await new Promise((r) => setTimeout(r, 1000));
                    el.click();
                    await new Promise((r) => setTimeout(r, 2000));
                    await driverObj.destroy();
                },
            },
        ],
    });

    return await driverObj.drive();
}

export async function StartTourStudentDisciplineShow() {
    store.openMai = false;

    const driverObj = drivertour({
        ...configDriver,
        steps: [
            {
                element: "#discipline-show-modules-sidebar",
                popover: {
                    description:
                        "Esta é a lista de módulos/aulas. Clique em cada módulo para expandir e ver as aulas disponíveis.",
                    side: "right",
                    align: "center",
                },
            },
            {
                element: "#discipline-show-video-player",
                popover: {
                    description:
                        "Área do player de vídeo. Selecione uma aula na lista ao lado para assistir o conteúdo.",
                    side: "left",
                    align: "center",
                    onPopoverRender: (popover) => {
                        const actions = AddButtonActions(
                            popover,
                            "Finalizar Tour",
                        );
                        actions.addEventListener("click", async () => {
                            await driverObj.destroy();
                            store.openMai = true;
                        });
                    },
                },
            },
        ],
    });

    return await driverObj.drive();
}

export async function StartTourStudentDisciplineShowActivitie() {
    store.openMai = false;
    const sidebar = document.getElementById("discipline-show-modules-sidebar");
    if (!sidebar) return;
    const moduleWrappers = sidebar.querySelectorAll('[id^="module-"]');
    const modulesWithActivity = [];
    if (moduleWrappers.length === 1) {
        console.log("vai diretor");
        return;
    }
    const driveInLessons = drivertour({
        ...configDriver,
        steps: [
            {
                element: null,
                popover: {
                    description:
                        "Interagindo com a ferramenta por favor aguarde...",
                    side: "right",
                    showButtons: [""],
                },
                onHighlighted: async (el) => {
                    await new Promise((r) => setTimeout(r, 1500));
                    await driveInLessons.moveNext();
                },
            },
            {
                element: sidebar,
                popover: {
                    description: "Selecione a aula que deseja ver a atividade.",
                    side: "right",
                    showButtons: [""],
                },
                onHighlighted: async (el) => {
                    await new Promise((r) => setTimeout(r, 1000));
                    const lessonsContent = el.querySelectorAll(`.lesson-card`);
                    let lessonSelected = false;
                    if (lessonsContent.length === 1) {
                        console.log("vai diretor aqui mano");
                        return;
                    }

                    lessonsContent.forEach((mod) => {
                        mod.style.zIndex = "200000000";
                        mod.addEventListener("click", async (event) => {
                            if (lessonSelected) return;
                            event.preventDefault();
                            lessonSelected = true;
                            lessonsContent.forEach((otherLesson) => {
                                if (mod !== otherLesson) {
                                    otherLesson.style.zIndex = "0";
                                }
                            });

                            const moduleID = mod.getAttribute("data-tour");
                            // Destrói o tour atual
                            await driveInLessons.destroy();
                            let updatedDriverObj;
                            const secondStep = [
                                {
                                    element: `#accordion-module-${moduleID}`,
                                    disableActiveInteraction:true,
                                    popover: {
                                        description: `Interagindo com a ferramenta por favor aguarde...`,
                                        side: "bottom",
                                        align: "start",
                                        showButtons: [""],
                                    },
                                    onHighlighted: async (el) => {
                                        await new Promise((resolve) =>
                                            setTimeout(resolve, 1000),
                                        );
                                        el.style.zIndex = "0";
                                        await driveInLessons.moveNext();
                                    },
                                },
                                {
                                    element: `#open-accordion-block-${moduleID}`,
                                    disableActiveInteraction:true,
                                    popover: {
                                        description: `Interagindo com a ferramenta por favor aguarde...`,
                                        showButtons: [""],
                                    },
                                    onHighlighted: async (el) => {
                                        await new Promise((resolve) =>
                                            setTimeout(resolve, 1500),
                                        );
                                        await el.click();
                                        await new Promise((resolve) =>
                                            setTimeout(resolve, 2000),
                                        );
                                        const tasksLists = await document.querySelector(`#content-activitie-lesson-${moduleID}`);
                                        if (!tasksLists) {
                                            alert("Parece que essa aula ainda não possue nenhuma atividade");
                                            await el.click();
                                            await driveInLessons.destroy();
                                            store.openMai = true;
                                            return;
                                        }
                                        await driveInLessons.moveNext();
                                    },
                                },
                                {
                                    element: `#content-activitie-lesson-${moduleID}`,
                                    disableActiveInteraction:false,
                                    popover: {
                                        description: `Clique na atividade que deseja abrir`,
                                        showButtons: [""],
                                    },
                                    onHighlighted: async (el) => {
                                        await new Promise((resolve) =>
                                            setTimeout(resolve, 500),
                                        );

                                        console.log("ELE DEVE BATER BEM AQUI DRENTI", el);
                                        const taskOpen =
                                            document.querySelector(
                                                ".opening-task",
                                            );
                                        if (!taskOpen) {
                                            alert(
                                                "Opa parece que algo deu errado como servidor",
                                            );
                                            return;
                                        }
                                        await new Promise((resolve) =>
                                            setTimeout(resolve, 1500),
                                        );

                                        await updatedDriverObj.moveNext();
                                    },
                                },
                                {
                                    element: `.opening-task`,
                                    popover: {
                                        description: `Aqui é onde a atividade será exibida. Explore o conteúdo e interaja com a atividade proposta pelo professor.`,
                                        showButtons: [""],
                                    },
                                    onHighlighted: async (el) => {
                                        await new Promise((resolve) =>
                                            setTimeout(resolve, 2500),
                                        );
                                        await updatedDriverObj.destroy();
                                    },
                                },
                            ];
                            updatedDriverObj = drivertour({
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
    await driveInLessons.drive();
    return;
}
