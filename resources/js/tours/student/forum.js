import { configDriver, AddButtonActions } from "../utils";
const store = Alpine.store("tourguide");

function navigateTo(url) {
    if (window.Livewire?.navigate) {
        window.Livewire.navigate(url);
        return;
    }

    window.location.href = url;
}

export async function StartTourStudentForum() {

    document.body.style.cursor = 'not-allowed';
    document.body.style.pointerEvents = 'none';

    store.openMai = false;

    const driverObj = drivertour({
        ...configDriver,
        steps: [
            {
                element: "#forum-breadcrumb",
                popover: {
                    description: "Você está na página do Fórum. Aqui você acompanha todas as discussões da sua turma.",
                    side: "bottom",
                    align: "start",
                },
            },
            {
                element: "#forum-header",
                popover: {
                    description: "Esta é a seção de Postagens no Fórum. Aqui você encontra um resumo da sua disciplina.",
                    side: "bottom",
                    align: "center",
                },
            },
            {
                element: "#forum-discipline-grid",
                popover: {
                    description: "Este card representa sua disciplina. Veja quantos tópicos de discussão existem.",
                    side: "top",
                    align: "center",
                },
            },
            {
                element: "#forum-discipline-card",
                popover: {
                    description: "Mostra o nome da disciplina, professor, quantidade de tópicos e um botão para acessar o fórum.",
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

export async function StartTourStudentForumShow() {
    store.openMai = false;

    const driverObj = drivertour({
        ...configDriver,
        steps: [
            {
                element: "#forum-show-breadcrumb",
                popover: {
                    description: "Você está visualizando os tópicos de fórum de uma disciplina específica.",
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
                    description: "Cada tópico mostra título, autor e data. Clique no título para ver as respostas.",
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

export async function StartTourStudentForumReplies() {
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

export async function StartTourStudentForumShowNewTopic() {
    store.openMai = false;

    const buttonNewTopic = document.querySelector("#forum-show-new-topic");
    if (!buttonNewTopic) return;

    const driverObj = drivertour({
        ...configDriver,
        steps: [
            {
                element: "#forum-show-new-topic",
                popover: {
                    description: "Vou clicar nesse botão para abrir o formulário e você pode criar um novo tópico de discussão para esta disciplina.",
                    showButtons: [""],
                },
                onHighlighted: async (el) => {
                    await new Promise((resolve) => setTimeout(resolve, 1000));
                    if (!buttonNewTopic) return;
                    await buttonNewTopic.click();
                    await new Promise((resolve) => setTimeout(resolve, 1000));
                    await driverObj.moveNext();
                }
            },
            {
                element: `#forum-input-title`,
                popover: {
                    description: "Adicione o título do tópico",
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

export async function StartTourStudentForumShowSelectTopic() {
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

        navigateTo(link.href);
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

export async function StartTourStudentForumShowMyTopics() {
    document.body.style.cursor = 'not-allowed';
    document.body.style.pointerEvents = 'none';
    store.openMai = false;

    const driverObj = drivertour({
        ...configDriver,
        steps: [
            {
                disableActiveInteraction: true,
                allowKeyboardControl: false,
                stageCursor: 'not-allowed',
                element: "#my-topics-created",
                popover: {
                    description: "Aqui estão os tópicos que você criou. É possível editá-los ou excluí-los diretamente.",
                    side: "top",
                    align: "center",
                    showButtons: [""],
                },
                onHighlighted: async (el) => {
                    await new Promise((resolve) => setTimeout(resolve, 3000));
                    await el.click();
                    await new Promise((resolve) => setTimeout(resolve, 3000));
                    await driverObj.moveNext();
                }
            },
            {
                disableActiveInteraction: true,
                stageCursor: 'not-allowed',
                element: "#forum-show-my-topics",
                popover: {
                    description: "Tópicos que você registrou.",
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
