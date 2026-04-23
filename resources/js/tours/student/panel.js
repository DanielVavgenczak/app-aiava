import { configDriver, AddButtonActions } from "../utils";
const store = Alpine.store("tourguide");

export async function StartTourStudentPanel() {
    store.openMai = false;
    
    const studentId = document.querySelector('[id^="student-welcome-"]')?.id.replace('student-welcome-', '') || '';
    
    if (!studentId) {
        console.error("ID do estudante não encontrado");
        return;
    }

    const driverObj = drivertour({
        ...configDriver,
        steps: [
            {
                element: `#student-welcome-${studentId}`,
                popover: {
                    description: "Este é o seu painel de boas-vindas. Aqui você pode ver seu nome e avatar.",
                    side: "bottom",
                    align: "start",
                },
            },
            {
                element: `#student-avatar-${studentId}`,
                popover: {
                    description: "Seu avatar personalizado. Você pode alterá-lo nas configurações do perfil.",
                    side: "right",
                    align: "center",
                },
            },
            {
                element: `#student-name-${studentId}`,
                popover: {
                    description: "Seu nome completo. Clique no menu 'Meu Perfil' para editar suas informações.",
                    side: "right",
                    align: "start",
                },
            },
            {
                element: `#student-quick-actions-${studentId}`,
                popover: {
                    description: "Aqui estão suas ações rápidas de navegação. Clique em qualquer卡片 para acessar.",
                    side: "top",
                    align: "center",
                    onPopoverRender: (popover, { config, state }) => {
                        const actions = AddButtonActions(popover, "Voltar ao início");
                        actions.addEventListener("click", async () => {
                            await driverObj.destroy();
                            store.openMai = true;
                        });
                    },
                },
            },
            {
                element: `#btn-my-disciplines-${studentId}`,
                popover: {
                    description: "Acesse suas disciplinas e continue aprendendo. Clique aqui para ver seus cursos.",
                    side: "bottom",
                    align: "center",
                },
            },
            {
                element: `#btn-forum-${studentId}`,
                popover: {
                    description: "Participe do fórum da sua turma. Tire dúvidas e interaja com colegas.",
                    side: "bottom",
                    align: "center",
                },
            },
            {
                element: `#btn-profile-${studentId}`,
                popover: {
                    description: "Gerencie suas informações pessoais, altere sua foto e dados de conta.",
                    side: "bottom",
                    align: "center",
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