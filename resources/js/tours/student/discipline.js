import { configDriver, AddButtonActions, addTourFinishButton, waitForElement, sleep } from "../utils.js";
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
    if (!sidebar) {
        store.openMai = true;
        return;
    }

    const lessonCards = Array.from(sidebar.querySelectorAll(".lesson-card"));
    if (lessonCards.length === 0) {
        store.openMai = true;
        return;
    }

    let lessonSelected = false;
    let driveInLessons;
    let updatedDriverObj;
    const originalZIndexes = new Map(
        lessonCards.map((card) => [card, card.style.zIndex]),
    );

    const cleanupLessonCards = () => {
        lessonCards.forEach((card) => {
            card.style.zIndex = originalZIndexes.get(card) ?? "";
        });
    };

    const handleLessonClick = async (event) => {
        if (lessonSelected) return;

        const mod = event.currentTarget;
        const moduleID = mod.getAttribute("data-tour");
        if (!moduleID) return;

        event.preventDefault();
        lessonSelected = true;
        lessonCards.forEach((otherLesson) => {
            if (otherLesson !== mod) {
                otherLesson.style.zIndex = "0";
            }
        });

        await driveInLessons.destroy();
        store.openMai = false;
        await startModuleTour(moduleID);
    };

    const startModuleTour = async (moduleID) => {
        updatedDriverObj = drivertour({
            ...configDriver,
            onDestroyed: () => {
                cleanupLessonCards();
                store.openMai = true;
            },
            steps: [
                {
                    element: `#accordion-module-${moduleID}`,
                    disableActiveInteraction: true,
                    popover: {
                        description: "Interagindo com a ferramenta por favor aguarde...",
                        side: "bottom",
                        align: "start",
                        showButtons: [""],
                    },
                    onHighlighted: async (el) => {
                        await new Promise((resolve) => setTimeout(resolve, 1000));
                        el.style.zIndex = "0";
                        await updatedDriverObj.moveNext();
                    },
                },
                {
                    element: `#open-accordion-block-${moduleID}`,
                    disableActiveInteraction: true,
                    popover: {
                        description: "Interagindo com a ferramenta por favor aguarde...",
                        showButtons: [""],
                    },
                    onHighlighted: async (el) => {
                        await new Promise((resolve) => setTimeout(resolve, 1500));
                        await el.click();
                        await new Promise((resolve) => setTimeout(resolve, 2000));
                        const tasksLists = document.querySelector(`#content-activitie-lesson-${moduleID}`);
                        if (!tasksLists) {
                            alert("Parece que essa aula ainda não possui nenhuma atividade");
                            await el.click();
                            await updatedDriverObj.destroy();
                            return;
                        }
                        await updatedDriverObj.moveNext();
                    },
                },
                {
                    element: `#content-activitie-lesson-${moduleID}`,
                    disableActiveInteraction: false,
                    popover: {
                        description: "Clique na atividade que deseja abrir",
                        showButtons: [""],
                    },
                    onHighlighted: async (el) => {
                        await new Promise((resolve) => setTimeout(resolve, 500));

                        const taskOpen =
                            el.querySelector(".tasks-open:not([disabled])") ||
                            el.querySelector('[id^="open-activity-"]:not([disabled])');
                        if (!taskOpen) {
                            alert("Não há atividades disponíveis nesta aula no momento.");
                            await updatedDriverObj.destroy();
                            return;
                        }

                        taskOpen.click();
                        try {
                            await waitForElement("#student-activity-drawer", { timeout: 5000, visible: false });
                        } catch (error) {
                            alert("Não foi possível abrir a atividade.");
                            await updatedDriverObj.destroy();
                            return;
                        }

                        await updatedDriverObj.moveNext();
                    },
                },
                {
                    element: "#student-activity-drawer",
                    popover: {
                        description: "Aqui é onde a atividade será exibida. Explore o conteúdo e interaja com a atividade proposta pelo professor.",
                        showButtons: [""],
                    },
                    onHighlighted: async () => {
                        await new Promise((resolve) => setTimeout(resolve, 2500));
                        await updatedDriverObj.destroy();
                    },
                },
            ],
        });

        await updatedDriverObj.drive();
    };

    lessonCards.forEach((mod) => {
        mod.style.zIndex = "200000000";
        mod.addEventListener("click", handleLessonClick);
    });

    driveInLessons = drivertour({
        ...configDriver,
        onDestroyed: () => {
            lessonCards.forEach((mod) => mod.removeEventListener("click", handleLessonClick));
            if (!lessonSelected) {
                cleanupLessonCards();
                store.openMai = true;
            }
        },
        steps: [
            {
                element: null,
                popover: {
                    description: "Interagindo com a ferramenta por favor aguarde...",
                    side: "right",
                    showButtons: [""],
                },
                onHighlighted: async () => {
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
                onHighlighted: async () => {
                    await new Promise((r) => setTimeout(r, 1000));

                    if (lessonCards.length === 1) {
                        const moduleID = lessonCards[0].getAttribute("data-tour");
                        if (!moduleID) {
                            await driveInLessons.destroy();
                            return;
                        }
                        lessonSelected = true;
                        await driveInLessons.destroy();
                        store.openMai = false;
                        await startModuleTour(moduleID);
                    }
                },
            },
        ],
    });

    await driveInLessons.drive();
}

/**
 * Tour do aluno respondendo uma atividade (drawer de responder).
 * Localiza o primeiro botão "Atividade" disponível, abre o drawer e
 * apresenta o enunciado, as opções e o botão de envio.
 */
export async function StartTourStudentAnswerActivity() {
    store.openMai = false;

    const activityBtn =
        document.querySelector(".tasks-open:not([disabled])") ||
        document.querySelector('[id^="open-activity-"]:not([disabled])');
    if (!activityBtn) {
        store.openMai = true;
        return;
    }

    let continuingToMainTour = false;

    const openDriver = drivertour({
        ...configDriver,
        onDestroyed: () => {
            if (!continuingToMainTour) {
                store.openMai = true;
            }
        },
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
                    continuingToMainTour = true;
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
        drawer = await waitForElement("#student-activity-drawer", { timeout: 5000, visible: false });
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
        onDestroyed: () => {
            store.openMai = true;
        },
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
    if (!resultBtn) {
        store.openMai = true;
        return;
    }

    let continuingToMainTour = false;

    const openDriver = drivertour({
        ...configDriver,
        onDestroyed: () => {
            if (!continuingToMainTour) {
                store.openMai = true;
            }
        },
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
                    continuingToMainTour = true;
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
        drawer = await waitForElement("#student-result-drawer", { timeout: 5000, visible: false });
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
        onDestroyed: () => {
            store.openMai = true;
        },
        steps,
    });

    await driverObj.drive();
}
