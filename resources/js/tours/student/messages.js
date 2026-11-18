import { configDriver, AddButtonActions } from "../utils.js";
const store = Alpine.store("tourguide");
const wait = (ms) => new Promise(resolve => setTimeout(resolve, ms));

function currentStudentId() {
    return document.querySelector('[id^="student-messages-header"]')?.id.replace('student-messages-header-', '') || '';
}

async function startConversationSteps(studentId) {
    const chatArea = document.querySelector(`#chat-area-${studentId}`);
    const messageInput = document.querySelector(`#message-input-${studentId}`);
    if (!chatArea || !messageInput) {
        store.openMai = true;
        return;
    }

    const conversationTour = drivertour({
        ...configDriver,
        steps: [
            {
                element: `#chat-area-${studentId}`,
                popover: {
                    description: "Aqui você pode visualizar as mensagens da conversa.",
                    side: "left",
                    align: "start",
                    showButtons: [""],
                },
                onHighlighted: async () => {
                    await wait(1500);
                    await conversationTour.moveNext();
                },
            },
            {
                element: `#message-input-${studentId}`,
                popover: {
                    description: "Digite a mensagem que deseja enviar e clique no botão para enviar ou pressione Enter.",
                    side: "top",
                    align: "start",
                    showButtons: [""],
                },
                onHighlighted: async () => {
                    await wait(2000);
                    await conversationTour.destroy();
                    store.openMai = true;
                },
            },
        ],
    });

    return await conversationTour.drive();
}

export async function StartTourInitChatInClasse() {
    store.openMai = false;

    const studentId = currentStudentId();
    if (!studentId) {
        console.error("ID do estudante não encontrado");
        store.openMai = true;
        return;
    }

    let conversationSelected = false;

    const highlightSidebar = async (el, tour) => {
        await wait(500);
        if (!el) {
            await tour.destroy();
            store.openMai = true;
            return;
        }

        const groupChat = el.querySelector(`#group-chat-${studentId}`);
        const teacherChat = el.querySelector(`#teacher-chat-${studentId}`);
        const classmatesList = el.querySelector(`#classmates-list-${studentId}`);
        const classmate = classmatesList?.nextElementSibling?.tagName === 'BUTTON'
            ? classmatesList.nextElementSibling
            : null;

        const selectConversation = async () => {
            if (conversationSelected) return;
            conversationSelected = true;
            await wait(1000);
            await tour.destroy();
            await startConversationSteps(studentId);
        };

        groupChat?.addEventListener('click', selectConversation, { once: true });
        teacherChat?.addEventListener('click', selectConversation, { once: true });
        classmate?.addEventListener('click', selectConversation, { once: true });
    };

    const tour = drivertour({
        ...configDriver,
        steps: [
            {
                element: `#chat-sidebar-${studentId}`,
                popover: {
                    description: "Clique em chat em grupo, no professor ou em um colega para iniciar uma conversa.",
                    side: "right",
                    align: "start",
                    showButtons: [""],
                },
                onHighlighted: (el) => highlightSidebar(el, tour),
            },
        ],
    });

    return await tour.drive();
}

export async function StartTourStudentMessages() {
    store.openMai = false;

    const studentId = currentStudentId();
    if (!studentId) {
        console.error("ID do estudante não encontrado");
        store.openMai = true;
        return;
    }

    const driverObj = drivertour({
        ...configDriver,
        steps: [
            {
                element: `#student-messages-header-${studentId}`,
                popover: {
                    description: "Este é o chat da sua turma. Aqui você conversa com seu professor e colegas.",
                    side: "bottom",
                    align: "start",
                },
            },
            {
                element: `#chat-sidebar-${studentId}`,
                popover: {
                    description: "Esta é a barra lateral de conversas. Navegue aqui para trocar de contato.",
                    side: "right",
                    align: "start",
                },
            },
            {
                element: `#group-chat-${studentId}`,
                popover: {
                    description: "Clique para abrir o chat em grupo. Vou selecioná-lo para você ver:",
                    side: "right",
                    align: "start",
                    onHighlighted: () => {
                        setTimeout(() => {
                            const btn = document.querySelector(`#group-chat-${studentId}`);
                            if (btn && !btn.classList.contains('bg-green-50')) btn.click();
                        }, 300);
                    },
                },
            },
            {
                element: `#teacher-chat-${studentId}`,
                popover: {
                    description: "Aqui você fala em privado com o professor. Vou clicar:",
                    side: "right",
                    align: "center",
                    onHighlighted: () => {
                        setTimeout(() => {
                            const btn = document.querySelector(`#teacher-chat-${studentId}`);
                            if (btn) btn.click();
                        }, 300);
                    },
                },
            },
            {
                element: `#classmates-list-${studentId}`,
                popover: {
                    description: "Lista de colegas. Vou clicar no primeiro para abrir o chat privado:",
                    side: "right",
                    align: "start",
                    onHighlighted: () => {
                        setTimeout(() => {
                            const list = document.querySelector(`#classmates-list-${studentId}`);
                            if (list) {
                                const next = list.nextElementSibling;
                                if (next && next.tagName === 'BUTTON') next.click();
                            }
                        }, 300);
                    },
                },
            },
            {
                element: `#chat-area-${studentId}`,
                popover: {
                    description: "Área de mensagens. Mensagens suas ficam à direita (azul), as dos outros à esquerda.",
                    side: "left",
                    align: "start",
                    onHighlighted: () => {
                        setTimeout(() => {
                            const btn = document.querySelector(`#group-chat-${studentId}`);
                            if (btn) btn.click();
                        }, 300);
                    },
                },
            },
            {
                element: `#message-input-${studentId}`,
                popover: {
                    description: "Digite aqui e envie. Vou preencher um exemplo:",
                    side: "top",
                    align: "start",
                    onHighlighted: () => {
                        setTimeout(() => {
                            const input = document.querySelector(`#message-input-${studentId} input`);
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
                            const input = document.querySelector(`#message-input-${studentId} input`);
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
