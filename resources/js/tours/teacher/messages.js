import { configDriver, AddButtonActions } from "../utils";
const store = Alpine.store("tourguide");

export async function StartTourTeacherMessages() {
    store.openMai = false;
    const teacherId = document.querySelector('[id^="teacher-messages-header-"]')?.id.replace('teacher-messages-header-', '') || '';
    if (!teacherId) {
        console.error("ID do professor não encontrado");
        return;
    }

    const wait = (ms) => new Promise(resolve => setTimeout(resolve, ms));

    const driverObj = drivertour({
        ...configDriver,
        steps: [
            {
                element: `#teacher-messages-header-${teacherId}`,
                popover: {
                    description: "Este é o chat dos seus alunos. Aqui você conversa com suas turmas em tempo real.",
                    side: "bottom",
                    align: "start",
                },
            },
            {
                element: `#classes-sidebar-${teacherId}`,
                popover: {
                    description: "Esta é a lista de suas turmas. Clique em uma turma para ver os alunos:",
                    side: "right",
                    align: "start",
                    onHighlighted: () => {
                        setTimeout(() => {
                            const firstClasse = document.querySelector('.classe-item');
                            if (firstClasse && !firstClasse.classList.contains('bg-blue-50')) firstClasse.click();
                        }, 300);
                    },
                },
            },
            {
                element: `#students-sidebar-${teacherId}`,
                popover: {
                    description: "Agora veja os alunos da turma selecionada. Aqui você pode escolher entre chat em grupo ou privado:",
                    side: "right",
                    align: "start",
                    onHighlighted: () => {
                        setTimeout(() => {
                            const btn = document.querySelector(`#group-chat-${teacherId}`);
                            if (btn && !btn.classList.contains('bg-green-50')) btn.click();
                        }, 300);
                    },
                },
            },
            {
                element: `#group-chat-${teacherId}`,
                popover: {
                    description: "O chat em grupo permite falar com todos os alunos da turma de uma vez. Vou selecioná-lo:",
                    side: "right",
                    align: "start",
                    onHighlighted: () => {
                        setTimeout(() => {
                            const btn = document.querySelector(`#group-chat-${teacherId}`);
                            if (btn && !btn.classList.contains('bg-green-50')) btn.click();
                        }, 300);
                    },
                },
            },
            {
                element: `#students-list-${teacherId}`,
                popover: {
                    description: "Lista de alunos. Clique em qualquer aluno para abrir um chat privado. Vou clicar no primeiro:",
                    side: "right",
                    align: "start",
                    onHighlighted: () => {
                        setTimeout(() => {
                            const firstStudent = document.querySelector(`#students-list-${teacherId} .student-item`);
                            if (firstStudent) firstStudent.click();
                        }, 300);
                    },
                },
            },
            {
                element: `#chat-area-${teacherId}`,
                popover: {
                    description: "Área de mensagens. Mensagens suas ficam à direita (azul), as dos alunos à esquerda (branco).",
                    side: "left",
                    align: "start",
                    onHighlighted: () => {
                        setTimeout(() => {
                            const btn = document.querySelector(`#group-chat-${teacherId}`);
                            if (btn) btn.click();
                        }, 300);
                    },
                },
            },
            {
                element: `#message-input-${teacherId}`,
                popover: {
                    description: "Digite aqui e envie. Vou preencher um exemplo:",
                    side: "top",
                    align: "start",
                    onHighlighted: () => {
                        setTimeout(() => {
                            const input = document.querySelector(`#message-input-${teacherId} input`);
                            if (input) {
                                input.focus();
                                input.value = "Olá! Esta é uma mensagem de teste do tour.";
                                input.dispatchEvent(new Event('input', { bubbles: true }));
                            }
                        }, 400);
                    },
                    onPopoverRender: (popover) => {
                        const actions = AddButtonActions(popover, "Finalizar Tour");
                        actions.addEventListener("click", async () => {
                            const input = document.querySelector(`#message-input-${teacherId} input`);
                            if (input) {
                                input.value = '';
                                input.dispatchEvent(new Event('input', { bubbles: true }));
                            }
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
