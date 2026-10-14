import { configDriver, AddButtonActions } from "../utils";
import { StartTourCreateDiscipline } from "./discipline/discipline";
import {StartTourClasseCreate} from "../classe_list";

const store = Alpine.store("tourguide");


export async function StartTourTeacherMessageToDiscipline()
{
    store.openMai = false;
     const driverObj = drivertour({
        ...configDriver,
        steps: [
            {
                popover: {
                    description: "Interagindo com a ferramenta...",
                    showButtons: [""],
                },
                onHighlighted: async() => {
                    await new Promise((resolve) => setTimeout(resolve, 3000));
                    await driverObj.destroy();
                    await new Promise((resolve) => setTimeout(resolve, 2000));
                    Livewire.navigate('/professor/disciplina');
                    await new Promise((resolve) => setTimeout(resolve, 2000));
                    await StartTourCreateDiscipline();
                },
            }
        ],
    });
    return await driverObj.drive();
}


export async function StartTourTeacherMessageToClasse()
{
    store.openMai = false;
     const driverObj = drivertour({
        ...configDriver,
        steps: [
            {
                popover: {
                    description: "Interagindo com a ferramenta...",
                    showButtons: [""],
                },
                onHighlighted: async() => {
                    await new Promise((resolve) => setTimeout(resolve, 3000));
                    driverObj.destroy();
                    const waitForNavigation = new Promise(resolve => {
                        document.addEventListener('livewire:navigated', () => {
                            setTimeout(resolve, 1000); // Pequeno delay extra
                        }, { once: true });
                    });

                    Livewire.navigate('/professor/turmas');
                    await waitForNavigation;

                    // Agora sim, a página carregou
                    await StartTourClasseCreate();
                },
            }
        ],
    });
    return await driverObj.drive();
}




export async function StartTourInitChatInClasse()
{
    store.openMai = false;
    const classes = document.querySelectorAll('.classe-item');

    // Se tiver apenas 1 classe, pega o ID e vai direto
    const singleClasseId = classes.length === 1 ? classes[0].getAttribute('data-classe-id') : null;

    const tourMessage = drivertour({
        ...configDriver,
        steps: [
            {
                popover: {
                    description: singleClasseId ? "Interagindo com a ferramenta..." : "Selecione a turma que deseja iniciar uma conversa.",
                    showButtons: [""],
                },
                onHighlighted: async() => {
                    await new Promise((resolve) => setTimeout(resolve, 2000));

                    if (singleClasseId) {
                        // Vai direto para o segundo tour com a classe já selecionada
                        await tourMessage.destroy();
                        await startSecondTour(singleClasseId);
                    } else {
                        await tourMessage.moveNext();
                    }
                }
            },
            {
                element: `#classe-listing-all`,
                popover: {
                    description: "Clique na turma para continuar.",
                    showButtons: [""],
                },
                onHighlighted: async(el) => {
                    await new Promise((resolve) => setTimeout(resolve, 500));
                    const classeCard = el.querySelectorAll(".classe-item");
                    let classeSelected = false;

                    classeCard.forEach(module => {
                        module.style.zIndex = "200000000"
                        module.addEventListener('click', async (event) => {
                            if (classeSelected) return;
                            event.preventDefault();
                            classeSelected = true;

                            classeCard.forEach((otherModule) => {
                                if (module !== otherModule) {
                                    otherModule.style.zIndex = "0";
                                }
                            });

                            const classeSelectedId = module.getAttribute('data-classe-id');
                            await tourMessage.destroy();
                            await startSecondTour(classeSelectedId);
                        });
                    });
                }
            }
        ],
    });

    await tourMessage.drive();
}

// Função separada para o segundo tour (reutilizável)
async function startSecondTour(classeSelectedId) {
    await new Promise((resolve) => setTimeout(resolve, 1000));
     const studentsListCont = document.querySelector(`#students-list-${classeSelectedId}`);
     let sidebarDescription;
     if(studentsListCont) {
             const studentItems = studentsListCont.querySelectorAll('.student-item');
                sidebarDescription = studentItems.length > 1
                    ? "Clique em chat em grupo para conversar com a turma, ou escolha um aluno para conversar individualmente."
                    : "Interagindo com a ferramenta...";
    }
    const secontStep = drivertour({
        ...configDriver,
        steps: [
            {
                element: `#classe-chat-${classeSelectedId}`,
                popover: {
                    description: "Interagindo com a ferramenta...",
                    showButtons: [""],
                },
                onHighlighted: async(el) => {
                    await new Promise((resolve) => setTimeout(resolve, 1000));

                    await el.click();
                    await new Promise((resolve) => setTimeout(resolve, 1000));
                    await secontStep.moveNext();
                }
            },
            {
                element: `#classe-chat-sidebar-${classeSelectedId}`,
                popover: {
                    description: (() => {
                        const studentsList = document.querySelector(`#students-list-${classeSelectedId}`);
                        if (studentsList) {
                            const studentItems = studentsList.querySelectorAll('.student-item');
                            return studentItems.length > 1
                                ? "Clique em chat em grupo para conversar com a turma, ou escolha um aluno para conversar individualmente."
                                : "Interagindo com a ferramenta...";
                        }
                        return "Interagindo com a ferramenta...";
                    })(),
                    showButtons: [""],
                },
                onHighlighted: async(el) => {
                    await new Promise((resolve) => setTimeout(resolve, 500));

                    const btnGroup = el.querySelector(`#group-chat-${classeSelectedId}`);
                    if(btnGroup) {
                        btnGroup.addEventListener('click', async (event) => {
                            event.preventDefault();
                            await new Promise((resolve) => setTimeout(resolve, 1500));
                            await secontStep.moveNext();
                        });
                    }

                    const studentsList = el.querySelector(`#students-list-${classeSelectedId}`);
                    if(studentsList) {
                        const studentItems = studentsList.querySelectorAll('.student-item');
                        if(studentItems.length === 1) {
                              const studentId = studentItems[0].dataset.studentId;
                            await new Promise((resolve) => setTimeout(resolve, 1000));
                             const btnStudent = document.querySelector(`#student-conversation-${studentId}`);
                                if(btnStudent) {
                                    btnStudent.click();
                                    await new Promise((resolve) => setTimeout(resolve, 2000));
                                }

                                await secontStep.moveNext();
                                return;
                        }

                        let isTourClicking = false;

                        const studentClickHandler = async (event) => {
                            if (isTourClicking) return;

                            const studentButton = event.target.closest('.student-item');
                            if (!studentButton) return;

                            event.preventDefault();
                            event.stopPropagation();
                            event.stopImmediatePropagation();

                            const studentId = studentButton.dataset.studentId;
                            studentsList.removeEventListener('click', studentClickHandler, true);

                            await new Promise((resolve) => setTimeout(resolve, 1000));
                            isTourClicking = true;

                            const btnStudent = document.querySelector(`#student-conversation-${studentId}`);
                            if(btnStudent) {
                                btnStudent.click();
                                await new Promise((resolve) => setTimeout(resolve, 2000));
                                isTourClicking = false;
                                await secontStep.moveNext();
                            } else {
                                isTourClicking = false;
                                await secontStep.moveNext();
                            }
                        };

                        studentsList.addEventListener('click', studentClickHandler, true);
                    }
                }
            },
            {
                element:`#chat-area-${classeSelectedId}`,
                popover: {
                    description: "Aqui você pode visualizar as mensagens.",
                    showButtons: [""],
                },
                onHighlighted: async() => {
                    await new Promise((resolve) => setTimeout(resolve, 2500));
                    await secontStep.moveNext();
                }
            },
            {
                element:`#message-sender-${classeSelectedId}`,
                popover: {
                    description: "Digite a mensagem que deseja enviar e clique no botão para enviar ou pressione Enter.",
                    showButtons: [""],
                },
                onHighlighted: async() => {
                    await new Promise((resolve) => setTimeout(resolve, 1000));
                    const justChast = document.querySelector(`#just-chat`);
                    if(justChast) {
                        justChast.classList.add('bottom-40');
                    }

                    await new Promise((resolve) => setTimeout(resolve, 4000));
                    await secontStep.destroy();
                    justChast.classList.remove('bottom-40');
                }
            }
        ]
    });

    await secontStep.drive();
}

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
