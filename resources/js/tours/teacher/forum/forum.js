import { configDriver, AddButtonActions } from "../../utils";
const store = Alpine.store("tourguide");

export async function StartTourTeacherForum() {
    store.openMai = false;

    const driverObj = drivertour({
        ...configDriver,
        steps: [
            {
                element: "#forum-breadcrumb",
                popover: {
                    description: "Você está na página do Fórum. Aqui você acompanha todas as discussões das suas turmas.",
                    side: "bottom",
                    align: "start",
                },
            },
            {
                element: "#forum-header",
                popover: {
                    description: "Esta é a seção de Postagens no Fórum. Aqui você encontra um resumo de todas as disciplinas que possuem tópicos de discussão.",
                    side: "bottom",
                    align: "center",
                },
            },
            {
                element: "#forum-discipline-grid",
                popover: {
                    description: "Cada card representa uma disciplina. Veja quantos tópicos de discussão existem em cada uma.",
                    side: "top",
                    align: "center",
                },
            },
            {
                element: "#forum-discipline-card",
                popover: {
                    description: "Este card mostra o nome da disciplina, a quantidade de tópicos e um botão para acessar o fórum da disciplina.",
                    side: "bottom",
                    align: "center",
                },
            },
            {
                element: "#forum-access-btn",
                popover: {
                    description: "Clique em 'Acessar' para entrar no fórum da disciplina e ver todos os tópicos de discussão.",
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
            },
        ],
    });

    return await driverObj.drive();
}

export async function StartTourTeacherForumInteract() {
    store.openMai = false;

    const cards = document.querySelectorAll('#forum-discipline-grid > div');
    const disciplines = Array.from(cards).map(card => {
        const name = card.querySelector('h3')?.textContent?.trim();
        const url = card.querySelector('a[href]')?.href;
        return { name, url, element: card };
    }).filter(d => d.name && d.url);

    if (disciplines.length === 0) return;

    cards.forEach(card => card.style.cursor = 'pointer');

    // ==========================================
    // FUNÇÕES DE BLOQUEIO (SÓ DEPOIS DO CLIQUE)
    // ==========================================
    const blockUserInteraction = () => {
        document.addEventListener('keydown', preventDefault, { passive: false });
        document.addEventListener('keyup', preventDefault, { passive: false });
        document.addEventListener('keypress', preventDefault, { passive: false });

        const overlay = document.createElement('div');
        overlay.id = 'tour-block-overlay';
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 9998;
            cursor: default;
            background: transparent;
        `;
        document.body.appendChild(overlay);
    };

    const unblockUserInteraction = () => {
        document.removeEventListener('keydown', preventDefault);
        document.removeEventListener('keyup', preventDefault);
        document.removeEventListener('keypress', preventDefault);

        const overlay = document.getElementById('tour-block-overlay');
        if (overlay) overlay.remove();
    };

    const preventDefault = (e) => e.preventDefault();

    // ==========================================
    // CLIQUE NO CARD → TOUR AUTOMÁTICO
    // ==========================================
    const handleCardClick = (e) => {
        const card = e.currentTarget;
        const d = disciplines.find(d => d.element === card);
        if (!d) return;

        // Destroi o tour inicial
        driverObj.destroy();

        // BLOQUEIA interações imediatamente após o clique
        blockUserInteraction();

        const accessBtn = card.querySelector('a[href]');

        // ==========================================
        // TOUR AUTOMÁTICO (SEM INTERAÇÃO DO USUÁRIO)
        // ==========================================
        const confirmDriver = drivertour({
            animate: true,
            opacity: 0.75,
            padding: 10,
            allowClose: false,
            showButtons: false,
            allowKeyboardControl: false,
            steps: [
                {
                    element: card,
                    popover: {
                        description: `Disciplina selecionada. Veja o botão para acessar o fórum.`,
                        side: "bottom",
                        align: "center",
                    },
                },
                {
                    element: accessBtn,
                    popover: {
                        description: "Acessando o fórum automaticamente...",
                        side: "top",
                        align: "center",
                    },
                },
            ],
            onDestroyed: () => {
                unblockUserInteraction();
            },
        });

        confirmDriver.drive();

        // ==========================================
        // AVANÇO AUTOMÁTICO COM DELAYS
        // ==========================================
        setTimeout(() => {
            confirmDriver.moveNext(); // Vai para o botão "Acessar"
        }, 1500);

        setTimeout(() => {
            unblockUserInteraction(); // Libera antes de redirecionar
            window.location.href = d.url; // Redireciona
        }, 3000);
    };


    if (disciplines.length === 1) {
        const driverObj = drivertour({
        ...configDriver,
        steps: [
            {
                element: "#forum-discipline-card",
                popover: {
                    description: "Como você possue apenas 1 disciplina.",
                    side: "bottom",
                    align: "start",
                    showButtons:[""]
                },
                onHighlighted: async () => {
                    await new Promise((resolve) => setTimeout(resolve, 3000));
                    await driverObj.moveNext();
                }
            },
            {
                element: "#forum-access-btn",
                popover: {
                    description: "Vamos acessa-la diretamente",
                    showButtons:[""]
                },
                onHighlighted: async () => {
                    await new Promise((resolve) => setTimeout(resolve, 3000));
                    const btnaccess = document.querySelector("#forum-access-btn");
                    await btnaccess.click();
                    await driverObj.destroy();
                }

            }
        ]
        });

        return await driverObj.drive();
            console.log("Aqui bem grande  vai slicar sozinho", disciplines.length);
            return;
    }

    // ==========================================
    // EVENTOS DE CLIQUE NOS CARDS
    // ==========================================
    disciplines.forEach(d => d.element.addEventListener('click', handleCardClick));

    // ==========================================
    // TOUR INICIAL (COM INTERAÇÃO DO USUÁRIO)
    // ==========================================
    const driverObj = drivertour({
        ...configDriver,


        steps: [
            {
                element: "#forum-discipline-grid",
                popover: {
                    description: "Clique na disciplina que deseja para acessar o fórum.",
                    side: "top",
                    align: "center",
                },
            },
        ],
        onDestroyed: () => {
            disciplines.forEach(d => d.element.removeEventListener('click', handleCardClick));
            cards.forEach(card => card.style.cursor = '');
        },
    });

    return await driverObj.drive();
}

export async function StartTourTeacherForumInteractss() {
    console.log("nem funciona mais")
    store.openMai = false;

    const cards = document.querySelectorAll('#forum-discipline-grid > div');
    const disciplines = Array.from(cards).map(card => {
        const name = card.querySelector('h3')?.textContent?.trim();
        const url = card.querySelector('a[href]')?.href;
        return { name, url, element: card };
    }).filter(d => d.name && d.url);

    if (disciplines.length === 0) return;

    cards.forEach(card => card.style.cursor = 'pointer');

    const handleCardClick = (e) => {
        const card = e.currentTarget;
        const d = disciplines.find(d => d.element === card);
        if (!d) return;

        driverObj.destroy();

        const accessBtn = card.querySelector('a[href]');

        const confirmDriver = drivertour({
            animate: true,
            opacity: 0.75,
            padding: 10,
            allowClose: false,
            nextBtnText: "Próximo",
            prevBtnText: "Anterior",
            doneBtnText: "Concluir",
            steps: [
                {
                    element: card,
                    popover: {
                        description: "Você selecionou esta disciplina. Veja o botão para acessar o fórum.",
                        side: "bottom",
                        align: "center",
                        onHighlighted: () => {
                            setTimeout(() => {
                                confirmDriver.moveNext();
                            }, 2000);
                        },
                    },
                },
                {
                    element: accessBtn,
                    popover: {
                        description: "Clique em 'Acessar' para entrar no fórum. Vou redirecionar...",
                        side: "top",
                        align: "center",
                        onHighlighted: () => {
                            setTimeout(() => {
                                window.location.href = d.url;
                            }, 2000);
                        },
                    },
                },
            ],
        });

        confirmDriver.drive();
    };

    disciplines.forEach(d => d.element.addEventListener('click', handleCardClick));

    const driverObj = drivertour({
        ...configDriver,
        steps: [
            {
                element: "#forum-discipline-grid",
                popover: {
                    description: "Clique na disciplina que deseja para acessar o fórum.",
                    side: "top",
                    align: "center",
                },
            },
        ],
        onDestroyed: () => {
            disciplines.forEach(d => d.element.removeEventListener('click', handleCardClick));
            cards.forEach(card => card.style.cursor = '');
        },
    });

    return await driverObj.drive();
}

export async function StartTourTeacherForumShow() {
    store.openMai = false;

    const driverObj = drivertour({
        ...configDriver,
        steps: [
            {
                element: "#forum-show-breadcrumb",
                popover: {
                    description: "(x) Você está visualizando os tópicos de fórum de uma disciplina específica.",
                    side: "bottom",
                    align: "start",
                },
            },
            {
                element: "#forum-show-header",
                popover: {
                    description: "Esta é a disciplina selecionada. Aqui você vê todos os tópicos de discussão relacionados a ela.",
                    side: "bottom",
                    align: "center",
                },
                // onHighlighted: async (el) => {
                //     await new Promise((resolve) => setTimeout(resolve, 2000));
                //     await driverObj.moveNext();
                //     console.log("mover sozinha paro o proximo pass aquii")
                //     // const redirectToForum = el.querySelector("#forum-access-btn");
                //     // if (!redirectToForum) return;
                //     // await redirectToForum.click();
                //     // await new Promise((resolve) => setTimeout(resolve, 3000));
                //     // const dropDownClick = document.querySelector(`#${tourOperations}`)
                //     //                         if(!dropDownClick) return;
                //     //                         await dropDownClick.click()
                // }
            },
            {
                element: "#forum-show-controls",
                popover: {
                    description: "Use este seletor para definir quantos tópicos exibir por página.",
                    side: "left",
                    align: "center",
                },
            },
            {
                element: "#forum-show-new-topic",
                popover: {
                    description: "Clique aqui para criar um novo tópico de discussão para esta disciplina.",
                    side: "left",
                    align: "center",
                },
            },
            {
                element: "#forum-show-tabs",
                popover: {
                    description: "Alterna entre 'Todos os Tópicos' e 'Meus Tópicos' para filtrar as discussões.",
                    side: "bottom",
                    align: "center",
                },
            },
            {
                element: ".topic-item:first-child",
                popover: {
                    description: "Cada tópico mostra título, autor, turma e data. Clique no título para ver as respostas.",
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
            },
        ],
    });

    return await driverObj.drive();
}

export async function StartTourTeacherForumReplies() {
    store.openMai = false;

    const driverObj = drivertour({
        ...configDriver,
        steps: [
            {
                element: "#forum-replies-breadcrumb",
                popover: {
                    description: "Você está visualizando as respostas de um tópico específico do fórum.",
                    side: "bottom",
                    align: "start",
                },
            },
            {
                element: "#forum-replies-header",
                popover: {
                    description: "Este é o tópico selecionado. Veja o título, autor e estatísticas de respostas.",
                    side: "bottom",
                    align: "center",
                },
            },
            {
                element: "#forum-replies-new-btn",
                popover: {
                    description: "Clique aqui para adicionar uma nova resposta a este tópico.",
                    side: "left",
                    align: "center",
                },
            },
            {
                element: "#forum-replies-list",
                popover: {
                    description: "Aqui ficam todas as respostas organizadas em ordem cronológica. Você pode responder, editar ou excluir respostas.",
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
            },
        ],
    });

    return await driverObj.drive();
}

export async function StartTourTeacherForumShowNewTopic() {
    store.openMai = false;

    const buttonNewTopic = document.querySelector("#forum-show-new-topic");
    if (!buttonNewTopic) return;

    const driverObj = drivertour({
        ...configDriver,
        steps: [
            // {
            //     element: "#forum-show-breadcrumb",
            //     popover: {
            //         description: "Você está visualizando os tópicos de fórum de uma disciplina específica.",
            //         side: "bottom",
            //         align: "start",
            //     },
            // },
            // {
            //     element: "#forum-show-header",
            //     popover: {
            //         description: "Esta é a disciplina selecionada com o total de tópicos disponíveis para discussão.",
            //         side: "bottom",
            //         align: "center",
            //     },
            // },
            {
                element: "#forum-show-new-topic",
                popover: {
                    description: "Vou criar nesse botão para abrir o formulario e você pode criar um novo tópico de discussão para esta disciplina.",
                    showButtons: [""],
                },

                onHighlighted: async (el) => {
                    await new Promise((resolve) => setTimeout(resolve, 1000));
                    //const redirectToNewTopic = el.querySelector("#forum-show-new-topic");
                    if (!buttonNewTopic) return;
                    await buttonNewTopic.click();
                    console.log("clicou para criar novo tópico");
                    await new Promise((resolve) => setTimeout(resolve, 1000));
                    await driverObj.moveNext();
                }
            },
            {
             element: `#forum-input-title`,
             popover: {
                description: "Adicione o título da disciplina",
                onPopoverRender: (popover, { config, state }) => {
                    const actions = AddButtonActions(
                     popover,
                      "Voltar ao início",
                    );
                    actions.addEventListener("click", async () => {
                    Livewire.dispatch("close-create");
                    await driverObj.destroy();
                    store.openMai = true;
                 });
              },
           },
          },
           {
                element: `#forum-input-class`,
                popover: {
                    description: "Selecione a turma relacionada ao tópico",
                },
            },
            {
                element: `#forum-input-actions`,
                popover: {
                    description: "Clique em 'Cancelar' para fechar o formulário ou 'Salvar' para criar o tópico.",
                }
           }

                ],
    });

    return await driverObj.drive();
}

export async function StartTourTeacherForumShowSelectTopic() {
    store.openMai = false;

    const topics = document.querySelectorAll('.topic-item');
    if (topics.length === 0) return;

    topics.forEach(t => t.style.cursor = 'pointer');

    const handleTopicClick = (e) => {
        const topic = e.currentTarget;
        const link = topic.querySelector('a[href]');
        if (!link) return;

        driverObj.destroy();
        topics.forEach(t => t.removeEventListener('click', handleTopicClick));
        topics.forEach(t => t.style.cursor = '');

        window.location.href = link.href;
    };

    topics.forEach(t => t.addEventListener('click', handleTopicClick));

    const driverObj = drivertour({
        ...configDriver,
        steps: [
            {
                element: "#forum-show-all-topics",
                popover: {
                    description: "Clique em qualquer tópico da lista para visualizar suas respostas e participar da discussão.",
                    side: "top",
                    align: "center",
                },
            },
        ],
        onDestroyed: () => {
            topics.forEach(t => t.removeEventListener('click', handleTopicClick));
            topics.forEach(t => t.style.cursor = '');
        },
    });

    return await driverObj.drive();
}

export async function StartTourTeacherForumShowMyTopics() {

    // TODO: refatorar depois criar uma function para reaproveitar
    document.body.style.cursor = 'not-allowed';
    document.body.style.pointerEvents = 'none';
    store.openMai = false;

    const driverObj = drivertour({
        ...configDriver,

        steps: [
            {
                disableActiveInteraction:true,
                allowKeyboardControl:false,
                stageCursor:'not-allowed',
                element: "#my-topics-created",
                popover: {
                    description: "Aqui está os tópicos que criou. É possível editá-los ou excluí-los diretamente.",
                    side: "top",
                    align: "center",
                    showButtons: [""],
                },
                onHighlighted: async (el) => {
                    //document.body.style.cursor = 'not-allowed';
                    await new Promise((resolve) => setTimeout(resolve, 3000));
                    await el.click();
                    await new Promise((resolve) => setTimeout(resolve, 3000));
                    await driverObj.moveNext();
                }
            },
            {
                disableActiveInteraction:true,
                stageCursor:'not-allowed',
                element: "#forum-show-my-topics",
                popover: {
                    description: "Topicos que você registrou.",
                    side: "top",
                    align: "center",
                },
                onHighlighted: async (el) => {

                    document.body.style.cursor = '';
                    document.body.style.pointerEvents = '';
                }
            }
        ],
    });

    return await driverObj.drive();
}
