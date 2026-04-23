import { configDriver, AddButtonActions } from "../utils";
const store = Alpine.store("tourguide");

export async function HomeTour() {
    store.openMai = false;
    
    const teacherId = document.querySelector('[id^="teacher-welcome-"]')?.id.replace('teacher-welcome-', '') || '';
    
    if (!teacherId) {
        console.error("ID do professor não encontrado");
        return;
    }

    const driverObj = drivertour({
        ...configDriver,
        steps: [
            {
                element: `#teacher-welcome-${teacherId}`,
                popover: {
                    description: "Olá! Este é seu painel de boas-vindas. Aqui você tem uma visão geral da sua plataforma.",
                    side: "bottom",
                    align: "start",
                },
            },
            {
                element: `#teacher-stats-${teacherId}`,
                popover: {
                    description: "Aqui você vê estatísticas gerais: suas disciplinas, turmas, alunos e tópicos do fórum.",
                    side: "top",
                    align: "center",
                    onPopoverRender: (popover, { config, state }) => {
                        const actions = AddButtonActions(popover, "Próximo");
                        actions.addEventListener("click", async () => {
                            await driverObj.moveNext();
                        });
                    },
                },
            },
            {
                element: `#teacher-stat-disciplines-${teacherId}`,
                popover: {
                    description: "Número de disciplinas que você criou. Clique em 'Nova Disciplina' para adicionar mais.",
                    side: "right",
                    align: "center",
                },
            },
            {
                element: `#teacher-stat-classes-${teacherId}`,
                popover: {
                    description: "Total de turmas ativas. Gerencie suas turmas para organizar melhor seus alunos.",
                    side: "right",
                    align: "center",
                },
            },
            {
                element: `#teacher-stat-students-${teacherId}`,
                popover: {
                    description: "Quantidade total de alunos cadastrados nas suas turmas.",
                    side: "left",
                    align: "center",
                },
            },
            {
                element: `#teacher-quick-actions-${teacherId}`,
                popover: {
                    description: "Ações rápidas para você começar. Crie novas disciplinas ou turmas rapidamente.",
                    side: "top",
                    align: "center",
                },
            },
            {
                element: `#btn-create-discipline-${teacherId}`,
                popover: {
                    description: "Clique aqui para criar uma nova disciplina. Você pode adicionar módulos, aulas e atividades.",
                    side: "right",
                    align: "start",
                },
            },
            {
                element: `#btn-create-class-${teacherId}`,
                popover: {
                    description: "Crie novas turmas para organizar seus alunos por classe ou período.",
                    side: "left",
                    align: "start",
                    onPopoverRender: (popover, { config, state }) => {
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