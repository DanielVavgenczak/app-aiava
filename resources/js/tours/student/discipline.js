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
                    description: "Vamos acessar a disciplina em que você foi cadastrado.",
                    side: "bottom",
                    align: "center",
                },
                onHighlighted: async (el) => {
                    await new Promise((r) => setTimeout(r, 1000));
                    el.click();
                    await new Promise((r) => setTimeout(r, 5000));
                    await driverObj.moveNext();
                }

            },
            {
                element: "#discipline-access-btn",
                popover: {
                    description: "Vou clicar em 'Acessar' para entrar na disciplina e ver todas as aulas e atividades.",
                    side: "top",
                    align: "center",
                    onPopoverRender: (popover) => {
                        const actions = AddButtonActions(popover, "Finalizar Tour");
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
                }
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
                    description: "Esta é a lista de módulos/aulas. Clique em cada módulo para expandir e ver as aulas disponíveis.",
                    side: "right",
                    align: "center",
                },
            },
            {
                element: "#discipline-show-video-player",
                popover: {
                    description: "Área do player de vídeo. Selecione uma aula na lista ao lado para assistir o conteúdo.",
                    side: "left",
                    align: "center",
                    onPopoverRender: (popover) => {
                        const actions = AddButtonActions(popover, "Finalizar Tour");
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

    moduleWrappers.forEach((mod) => {
        const buttons = mod.querySelectorAll('button');
        const hasActivity = Array.from(buttons).some(
            (btn) => btn.textContent.trim() === "Atividade"
        );
        if (hasActivity) modulesWithActivity.push(mod);
    });

    if (modulesWithActivity.length === 0) {
        startNoActivityTour();
        return;
    }

    async function startModuleActivityTour(index) {
        const mod = modulesWithActivity[index];
        const isLast = index === modulesWithActivity.length - 1;

        const heading = mod.querySelector(".collapse-title");
        const activityBtn = Array.from(mod.querySelectorAll("button")).find(
            (btn) => btn.textContent.trim() === "Atividade"
        );

        if (!heading || !activityBtn) {
            if (!isLast) {
                await startModuleActivityTour(index + 1);
            } else {
                startNoActivityTour();
            }
            return;
        }

        const checkbox = mod.querySelector('input[type="checkbox"]');
        if (checkbox && !checkbox.checked) {
            heading.click();
            await new Promise((r) => setTimeout(r, 600));
        }

        const innerDriver = drivertour({
            ...configDriver,
            steps: [
                {
                    element: heading,
                    popover: {
                        title: "Módulo com atividade",
                        description:
                            "Este módulo possui uma atividade disponível! Clique para expandi-lo.",
                        side: "right",
                        align: "center",
                    },
                    onHighlighted: async () => {
                        if (checkbox && !checkbox.checked) {
                            heading.click();
                            await new Promise((r) => setTimeout(r, 800));
                        }
                        await innerDriver.moveNext();
                    },
                },
                {
                    element: activityBtn,
                    popover: {
                        title: "Atividade encontrada!",
                        description:
                            'Clique no botão "Atividade" para abrir e responder às questões.',
                        side: "left",
                        align: "center",
                        onPopoverRender: (popover) => {
                            if (!isLast) {
                                const tryAnother = AddButtonActions(
                                    popover,
                                    "Tentar outro módulo"
                                );
                                tryAnother.addEventListener("click", async () => {
                                    await innerDriver.destroy();
                                    if (checkbox && checkbox.checked) {
                                        heading.click();
                                        await new Promise((r) => setTimeout(r, 400));
                                    }
                                    await startModuleActivityTour(index + 1);
                                });
                            }

                            const finishBtn = AddButtonActions(popover, "Finalizar");
                            finishBtn.addEventListener("click", async () => {
                                await innerDriver.destroy();
                                store.openMai = true;
                            });
                        },
                    },
                    onHighlighted: async (el) => {
                        await new Promise((r) => setTimeout(r, 1200));
                        el.click();
                        await new Promise((r) => setTimeout(r, 1500));
                        await innerDriver.destroy();
                        highlightOpenedDrawer();
                    },
                },
            ],
        });

        innerDriver.drive();
    }

    function highlightOpenedDrawer() {
        const drawer = document.getElementById("discipline-show-activity-drawer");
        if (!drawer) return;

        const finishDriver = drivertour({
            ...configDriver,
            steps: [
                {
                    element: drawer,
                    popover: {
                        title: "Atividade aberta!",
                        description:
                            "Agora você pode responder às questões. Selecione suas respostas e clique em 'Enviar' quando terminar.",
                        side: "left",
                        align: "center",
                        onPopoverRender: (popover) => {
                            const btn = AddButtonActions(popover, "Concluir");
                            btn.addEventListener("click", () => {
                                finishDriver.destroy();
                            });
                        },
                    },
                },
            ],
        });
        finishDriver.drive();
    }

    function startNoActivityTour() {
        const driverObj = drivertour({
            ...configDriver,
            steps: [
                {
                    element: sidebar,
                    popover: {
                        title: "Nenhuma atividade disponível",
                        description:
                            "No momento não há atividades cadastradas nesta disciplina. " +
                            "Assim que o professor adicionar uma atividade, você poderá acessá-la por aqui.",
                        side: "right",
                        align: "center",
                        onPopoverRender: (popover) => {
                            const btn = AddButtonActions(popover, "Finalizar");
                            btn.addEventListener("click", () => {
                                driverObj.destroy();
                                store.openMai = true;
                            });
                        },
                    },
                },
            ],
        });
        driverObj.drive();
    }

    await startModuleActivityTour(0);
}
