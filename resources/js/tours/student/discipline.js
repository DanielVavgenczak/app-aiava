import { configDriver, AddButtonActions, addTourFinishButton, waitForElement, sleep } from "../utils";
const store = Alpine.store("tourguide");

function finishButton(driverObj) {
    return (popover) => {
        addTourFinishButton(popover, driverObj, {
            label: "Encerrar tour",
            onFinish: async () => {
                store.openMai = true;
            },
        });
    };
}

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

/**
 * Tour do aluno respondendo uma atividade (drawer de responder).
 * Localiza o primeiro botão "Atividade" disponível, abre o drawer e
 * apresenta o enunciado, as opções e o botão de envio.
 */
export async function StartTourStudentAnswerActivity() {
    store.openMai = false;

    const activityBtn =
        document.querySelector(".tasks-open") ||
        document.querySelector('[id^="open-activity-"]');
    if (!activityBtn) return;

    const openDriver = drivertour({
        ...configDriver,
        steps: [
            {
                element: activityBtn,
                popover: {
                    description:
                        "Vamos abrir uma atividade para você responder. Clique em uma atividade disponível.",
                    side: "bottom",
                    align: "center",
                    showButtons: [""],
                },
                onHighlighted: async (el) => {
                    await sleep(1000);
                    el.click();
                    await sleep(2500);
                    await openDriver.destroy();
                    await startAnswerActivityMainTour();
                },
            },
        ],
    });

    await openDriver.drive();
}

async function startAnswerActivityMainTour() {
    let drawer;
    try {
        drawer = await waitForElement("#student-activity-drawer");
    } catch (error) {
        console.error(error);
        store.openMai = true;
        return;
    }

    const firstQuestion = drawer.querySelector('[data-tour="student-activity-question"]');
    const submitBtn = drawer.querySelector("#student-activity-submit");

    const steps = [
        {
            element: drawer,
            popover: {
                description:
                    "Este é o formulário de resposta da atividade. Responda todas as questões exibidas.",
                side: "left",
                align: "start",
                onPopoverRender: (popover) => finishButton(driverObj)(popover),
            },
        },
    ];

    if (firstQuestion) {
        steps.push({
            element: firstQuestion,
            popover: {
                description:
                    "Nas questões de múltipla escolha, selecione uma alternativa. Nas dissertativas, escreva sua resposta no campo de texto. Questões já corrigidas aparecem bloqueadas.",
                side: "top",
                align: "start",
                onPopoverRender: (popover) => finishButton(driverObj)(popover),
            },
        });
    }

    if (submitBtn) {
        steps.push({
            element: submitBtn,
            popover: {
                description:
                    'Quando terminar, clique em "Enviar". É obrigatório responder todas as questões antes de enviar.',
                side: "left",
                align: "end",
                onPopoverRender: (popover) => finishButton(driverObj)(popover),
            },
        });
    }

    const driverObj = drivertour({
        ...configDriver,
        steps,
    });

    await driverObj.drive();
}

/**
 * Tour do resultado da atividade para o aluno (nota/feedback e nota final).
 */
export async function StartTourStudentActivityResult() {
    store.openMai = false;

    const resultBtn = document.querySelector('[id^="open-activity-result-"]');
    if (!resultBtn) return;

    const openDriver = drivertour({
        ...configDriver,
        steps: [
            {
                element: resultBtn,
                popover: {
                    description:
                        'Clique em "Resultado" para ver como você se saiu na atividade.',
                    side: "bottom",
                    align: "center",
                    showButtons: [""],
                },
                onHighlighted: async (el) => {
                    await sleep(1000);
                    el.click();
                    await sleep(2000);
                    await openDriver.destroy();
                    await startStudentResultMainTour();
                },
            },
        ],
    });

    await openDriver.drive();
}

async function startStudentResultMainTour() {
    let drawer;
    try {
        drawer = await waitForElement("#student-result-answer");
    } catch (error) {
        console.error(error);
        store.openMai = true;
        return;
    }

    const finalGrade = document.querySelector("#student-result-final-grade");

    const steps = [
        {
            element: drawer,
            popover: {
                description:
                    "Aqui você vê cada questão respondida, com sua resposta, a nota e o feedback dado pelo professor.",
                side: "left",
                align: "start",
                onPopoverRender: (popover) => finishButton(driverObj)(popover),
            },
        },
    ];

    if (finalGrade) {
        steps.unshift({
            element: finalGrade,
            popover: {
                description:
                    "No topo aparece a nota final da atividade. Ela é definida pelo professor junto com um feedback geral.",
                side: "bottom",
                align: "start",
                onPopoverRender: (popover) => finishButton(driverObj)(popover),
            },
        });
    }

    const driverObj = drivertour({
        ...configDriver,
        steps,
    });

    await driverObj.drive();
}
