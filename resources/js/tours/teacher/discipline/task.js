import {
    configDriver,
    addTourFinishButton,
    waitForElement,
    sleep,
} from "../../utils";

const store = Alpine.store("tourguide");

function finishButton(driverObj, popover) {
    return addTourFinishButton(popover, driverObj, {
        label: "Encerrar tour",
        onFinish: async () => {
            store.openMai = true;
        },
    });
}

/**
 * Tour do gerenciador de questões de uma atividade (drawer show-task já aberto):
 * header, abas Atividade/Entregas e ações por questão.
 */
export async function StartTourManageActivityTask() {
    store.openMai = false;

    let header;
    try {
        header = await waitForElement("#teacher-activity-header");
    } catch (error) {
        console.error(error);
        store.openMai = true;
        return;
    }

    const driverObj = drivertour({
        ...configDriver,
        steps: [
            {
                element: header,
                popover: {
                    description:
                        "Este é o painel da atividade. No cabeçalho você vê o título, se ela é avaliativa e quanto vale, o período de disponibilidade e quantos alunos já responderam.",
                    side: "bottom",
                    align: "start",
                    onPopoverRender: (popover) => finishButton(driverObj, popover),
                },
            },
            {
                element: "#teacher-activity-tabs",
                popover: {
                    description:
                        'Use as abas para alternar entre "Atividade" (as questões) e "Entregas" (as respostas dos alunos e a correção).',
                    side: "bottom",
                    align: "start",
                    onPopoverRender: (popover) => finishButton(driverObj, popover),
                },
            },
            {
                element: document.querySelector('[data-tour="teacher-task"]'),
                popover: {
                    description:
                        "Cada bloco abaixo é uma questão da atividade, na ordem em que o aluno vai responder.",
                    side: "bottom",
                    align: "start",
                    onPopoverRender: (popover) => finishButton(driverObj, popover),
                },
            },
            {
                element: document.querySelector('[data-tour="teacher-task-actions"]'),
                popover: {
                    description:
                        '"Editar" altera o enunciado e o valor da questão. "Respostas da questão" abre as respostas dos alunos para esta questão. "Remover" exclui a questão — o que só é permitido se nenhum aluno tiver respondido.',
                    side: "left",
                    align: "center",
                    onPopoverRender: (popover) => finishButton(driverObj, popover),
                },
            },
        ].filter((step) => step.element),
    });

    await driverObj.drive();
}

/**
 * Tour da aba Entregas: lista de alunos, respostas e correção com nota final.
 * Assume o drawer da atividade já aberto.
 */
export async function StartTourActivityDeliveries() {
    store.openMai = false;

    let tabs;
    try {
        tabs = await waitForElement("#teacher-activity-tabs");
    } catch (error) {
        console.error(error);
        store.openMai = true;
        return;
    }

    const deliveriesTab = Array.from(
        tabs.querySelectorAll("button, a, [role='tab']"),
    ).find((el) => el.textContent.trim().toLowerCase().includes("entregas"));

    const tabDriver = drivertour({
        ...configDriver,
        steps: [
            {
                element: deliveriesTab ?? tabs,
                popover: {
                    description:
                        'Abra a aba "Entregas" para ver e corrigir as respostas dos alunos.',
                    side: "bottom",
                    align: "start",
                    showButtons: [""],
                },
                onHighlighted: async (el) => {
                    await sleep(1000);
                    el.click();
                    await sleep(1500);
                    await tabDriver.destroy();
                    await startDeliveriesMasterDetailTour();
                },
            },
        ],
    });

    await tabDriver.drive();
}

async function startDeliveriesMasterDetailTour() {
    let list;
    let finalGrade;
    try {
        list = await waitForElement("#teacher-deliveries-list");
        finalGrade = document.querySelector("#teacher-deliveries-final-grade");
    } catch (error) {
        console.error(error);
        store.openMai = true;
        return;
    }

    const driverObj = drivertour({
        ...configDriver,
        steps: [
            {
                element: list,
                popover: {
                    description:
                        "À esquerda fica a lista dos alunos que entregaram a atividade, com nome, turma, disciplina e o status da correção (Corrigida ou Pendente). Use os filtros para localizar quem ainda falta corrigir.",
                    side: "right",
                    align: "start",
                    onPopoverRender: (popover) => finishButton(driverObj, popover),
                },
            },
            {
                element: "#teacher-deliveries-detail",
                popover: {
                    description:
                        "Selecione um aluno na lista para ver as respostas dele aqui à direita, questão por questão, e lançar nota e feedback.",
                    side: "left",
                    align: "start",
                    onPopoverRender: (popover) => finishButton(driverObj, popover),
                },
            },
            finalGrade
                ? {
                      element: finalGrade,
                      popover: {
                          description:
                              "Ao final, você pode registrar a nota final da atividade e um feedback geral para o aluno.",
                          side: "top",
                          align: "start",
                          onPopoverRender: (popover) => finishButton(driverObj, popover),
                      },
                  }
                : null,
        ].filter(Boolean),
    });

    await driverObj.drive();
}

/**
 * Tour do modal de edição da atividade: título, avaliativa/valor e datas.
 * Assume o drawer da atividade já aberto.
 */
export async function StartTourEditActivityTask() {
    store.openMai = false;

    let editBtn;
    try {
        editBtn = await waitForElement("#teacher-activity-header button");
    } catch (error) {
        console.error(error);
        store.openMai = true;
        return;
    }

    const editButton = Array.from(
        document.querySelectorAll("#teacher-activity-header button"),
    ).find((el) => el.textContent.trim().toLowerCase().includes("editar"));

    const openDriver = drivertour({
        ...configDriver,
        steps: [
            {
                element: editButton ?? editBtn,
                popover: {
                    description:
                        'Clique em "Editar" no cabeçalho para abrir o formulário de edição da atividade.',
                    side: "bottom",
                    align: "end",
                    showButtons: [""],
                },
                onHighlighted: async (el) => {
                    await sleep(1000);
                    el.click();
                    await sleep(1800);
                    await openDriver.destroy();
                    await startEditModalTour();
                },
            },
        ],
    });

    await openDriver.drive();
}

async function startEditModalTour() {
    let modal;
    try {
        modal = await waitForElement("#edit-task-modal");
    } catch (error) {
        console.error(error);
        store.openMai = true;
        return;
    }

    const hasAnswers = Boolean(modal.querySelector(".alert-warning"));

    const steps = [
        {
            element: modal,
            popover: {
                description: hasAnswers
                    ? "Esta atividade já recebeu respostas, por isso apenas o título e as datas podem ser alterados. As demais configurações ficam bloqueadas."
                    : "Neste formulário você pode ajustar a configuração da atividade já criada.",
                side: "bottom",
                align: "start",
                onPopoverRender: (popover) => finishButton(driverObj, popover),
            },
        },
        {
            element: "#edit-task-title",
            popover: {
                description: "Altere o título da atividade.",
                side: "bottom",
                align: "start",
                onPopoverRender: (popover) => finishButton(driverObj, popover),
            },
        },
    ];

    if (!hasAnswers) {
        const avaliativa = document.querySelector("#edit-task-avaliativa");
        const value = document.querySelector("#edit-task-value");
        if (avaliativa) {
            steps.push({
                element: avaliativa,
                popover: {
                    description:
                        "Marque ou desmarque como atividade avaliativa e, se for avaliativa, defina o valor total.",
                    side: "bottom",
                    align: "start",
                    onPopoverRender: (popover) => finishButton(driverObj, popover),
                },
            });
        }
        if (value) {
            steps.push({
                element: value,
                popover: {
                    description:
                        "Defina o valor total da atividade. A soma das notas das questões não pode ultrapassar este valor.",
                    side: "bottom",
                    align: "start",
                    onPopoverRender: (popover) => finishButton(driverObj, popover),
                },
            });
        }
    }

    steps.push({
        element: "#edit-task-dates",
        popover: {
            description:
                "Ajuste o período de disponibilidade. A data final é o prazo máximo de entrega; deixe em branco se não houver prazo.",
            side: "top",
            align: "start",
            onPopoverRender: (popover) => finishButton(driverObj, popover),
        },
    });

    const driverObj = drivertour({
        ...configDriver,
        steps,
    });

    await driverObj.drive();
}
