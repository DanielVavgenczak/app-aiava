import {
    configDriver,
    addTourFinishButton,
    getFirstVisible,
    sleep,
    waitForElement
} from "../../utils.js";
const store = Alpine.store("tourguide");

function navigateTo(url) {
    if (window.Livewire?.navigate) {
        window.Livewire.navigate(url);
        return;
    }

    window.location.href = url;
}

function lockPage() {
    document.body.style.cursor = 'not-allowed';
    document.body.style.pointerEvents = 'none';
}

function unlockPage() {
    document.body.style.cursor = '';
    document.body.style.pointerEvents = '';
}

export async function StartTourTeacherForumInteract() {
    store.openMai = false;

    const cards = document.querySelectorAll('#forum-discipline-grid > div');
    const disciplines = Array.from(cards).map(card => {
        const name = card.querySelector('h3')?.textContent?.trim();
        const url = card.querySelector('a[href]')?.href;
        return { name, url, element: card };
    }).filter(d => d.name && d.url);

    if (disciplines.length === 0) {
        store.openMai = true;
        return;
    }

    cards.forEach(card => card.style.cursor = 'pointer');

    const preventDefault = (e) => e.preventDefault();

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

        document.getElementById('tour-block-overlay')?.remove();
    };

    if (disciplines.length === 1) {
        let navigatingAway = false;

        const driverObj = drivertour({
            ...configDriver,
            allowKeyboardControl: false,
            steps: [
                {
                    element: "#forum-discipline-card",
                    popover: {
                        description: "Interagindo com a ferramenta...",
                        side: "bottom",
                        align: "start",
                        showButtons: [],
                    },
                    onHighlighted: async () => {
                        await sleep(3000);
                        await driverObj.moveNext();
                    }
                },
                {
                    element: "#forum-access-btn",
                    popover: {
                        description: "Interagindo com a ferramenta...",
                        showButtons: [],
                    },
                    onHighlighted: async () => {
                        await sleep(3000);
                        const accessBtn = document.querySelector("#forum-access-btn");
                        if (!accessBtn) {
                            await driverObj.destroy();
                            return;
                        }

                        navigatingAway = true;
                        accessBtn.click();
                        await driverObj.destroy();
                    }
                }
            ],
            onDestroyed: () => {
                cards.forEach(card => card.style.cursor = '');
                if (!navigatingAway) store.openMai = true;
            },
        });

        return await driverObj.drive();
    }

    let navigatingAway = false;
    let confirmDriver = null;
    const timers = [];

    const clearAutoTimers = () => {
        timers.splice(0).forEach(clearTimeout);
    };

    const handleCardClick = (e) => {
        if (e.target.closest('a[href]')) {
            e.preventDefault();
            e.stopPropagation();
        }

        const card = e.currentTarget;
        const discipline = disciplines.find(d => d.element === card);
        if (!discipline) return;

        navigatingAway = true;
        driverObj.destroy();
        blockUserInteraction();

        const accessBtn = card.querySelector('a[href]');

        confirmDriver = drivertour({
            ...configDriver,
            allowKeyboardControl: false,
            allowClose: false,
            steps: [
                {
                    element: card,
                    popover: {
                        description: "Disciplina selecionada. Veja o botão para acessar o fórum.",
                        side: "bottom",
                        align: "center",
                        showButtons: [],
                    },
                },
                {
                    element: accessBtn,
                    popover: {
                        description: "Acessando o fórum automaticamente...",
                        side: "top",
                        align: "center",
                        showButtons: [],
                    },
                },
            ],
            onDestroyed: () => {
                clearAutoTimers();
                unblockUserInteraction();
            },
        });

        confirmDriver.drive();

        timers.push(setTimeout(() => {
            confirmDriver?.moveNext();
        }, 1500));

        timers.push(setTimeout(() => {
            unblockUserInteraction();
            confirmDriver?.destroy();
            navigateTo(discipline.url);
        }, 3000));
    };

    disciplines.forEach(d => d.element.addEventListener('click', handleCardClick));

    const driverObj = drivertour({
        ...configDriver,
        allowKeyboardControl: false,
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
            clearAutoTimers();
            disciplines.forEach(d => d.element.removeEventListener('click', handleCardClick));
            cards.forEach(card => card.style.cursor = '');
            unblockUserInteraction();
            if (!navigatingAway) store.openMai = true;
        },
    });

    return await driverObj.drive();
}

const REPLY_ITEM_SELECTOR = '[data-tour="reply-item"]';
const REPLY_TO_REPLY_SELECTOR = '[data-tour="reply-to-reply"]';
const EDIT_REPLY_SELECTOR = '[data-tour="edit-reply"]';
const DELETE_REPLY_SELECTOR = '[data-tour="delete-reply"]';
const REPLY_MODAL_FORM_SELECTOR = '#form-reply-topic, #form-reply-to-reply, #form-edit-reply';

const ownActions = (item, selector) =>
    Array.from(item.querySelectorAll(selector)).filter(
        (action) => action.closest(REPLY_ITEM_SELECTOR) === item
    );

// Quando ha mais de um item, o proprio usuario clica no botao. O tour so avanca
// depois que o Livewire realmente renderizou o modal.
const wireAdvanceOnModalOpen = ({ driver, buttons, modalSelector }) => {
    let handled = false;
    const controller = new AbortController();
    const originalDestroy = driver.destroy.bind(driver);
    let cleaned = false;

    const cleanup = () => {
        if (cleaned) return;
        cleaned = true;
        controller.abort();
    };

    driver.destroy = async (...args) => {
        cleanup();
        return originalDestroy(...args);
    };

    buttons.forEach((button) => {
        button.addEventListener('click', async () => {
            if (handled) return;
            handled = true;

            const opened = await waitForElement(modalSelector, { timeout: 8000 })
                .then(() => true)
                .catch(() => false);

            if (!opened) return;

            try {
                await driver.moveNext();
            } catch (error) {
                console.error('Falha ao avançar no tour de respostas:', error);
                await driver.destroy();
                store.openMai = true;
            }
        }, { once: true, signal: controller.signal });
    });

    return cleanup;
};


export async function StartTourTeacherForumRepliesNewReply() {
    store.openMai = false;

    let lastReplyEl = null;

    const driverObj = drivertour({
        ...configDriver,
        allowKeyboardControl: false,
        onDestroyed: () => {
            store.openMai = true;
        },
        steps: [
            {
                disableActiveInteraction: true,
                element: "#forum-replies-new-btn",
                popover: {
                    description: "Interagindo com a ferramenta...",
                    side: "left",
                    align: "center",
                    showButtons: [],
                },
                onHighlighted: async (el) => {
                    if (!el) {
                        await driverObj.destroy();
                        store.openMai = true;
                        return;
                    }

                    await sleep(1200);
                    el.click();

                    const opened = await waitForElement('#form-reply-topic', { timeout: 8000 })
                        .then(() => true)
                        .catch(() => false);

                    if (!opened) {
                        await driverObj.destroy();
                        store.openMai = true;
                        return;
                    }

                    await sleep(800);
                    await driverObj.moveNext();
                }
            },
            {
                element: () => getFirstVisible('#form-reply-topic'),
                disableActiveInteraction: false,
                popover: {
                    description: "Escreva sua resposta e clique em 'Responder' para publicar, ou em 'Cancelar' para fechar sem salvar.",
                    onPopoverRender: (popover) => {
                        addTourFinishButton(popover, driverObj, {
                            label: 'Cancelar operação',
                            onClick: async () => {
                                try {
                                    if (window.Livewire) {
                                        Livewire.dispatch('teacher::close-reply-forum-topic');
                                    }
                                } catch (e) {}
                                await driverObj.destroy();
                                store.openMai = true;
                            }
                        });
                    },
                },
                onHighlighted: async (el) => {
                    const finish = async () => {
                        await driverObj.destroy();
                        store.openMai = false;
                    };

                    if (!el || !el.matches('#form-reply-topic')) {
                        await finish();
                        return;
                    }

                    el.querySelector('textarea')?.focus();

                    const cancel = document.querySelector('#btn-cancel-reply');
                    const save = document.querySelector('#btn-save-reply');

                    cancel?.addEventListener('click', finish, { once: true });
                    save?.addEventListener('click', async () => {
                        await sleep(3000);

                        const repliesList = document.querySelector('#forum-replies-list');
                        if (repliesList) {
                            const items = repliesList.querySelectorAll(REPLY_ITEM_SELECTOR);
                            if (items.length > 0) {
                                lastReplyEl = items[items.length - 1];
                            }
                        }

                        await driverObj.moveNext();
                    }, { once: true });
                }
            },
            {
                element: () => lastReplyEl || document.querySelector(`${REPLY_ITEM_SELECTOR}:last-of-type`),
                disableActiveInteraction: true,
                popover: {
                    description: "Esta é a resposta que você acabou de publicar.",
                    side: "top",
                    align: "start",
                    showButtons: [],
                },
                onHighlighted: async (el) => {
                    if (!el) {
                        await driverObj.destroy();
                        store.openMai = false;
                        return;
                    }

                    await sleep(2000);
                    await driverObj.destroy();
                    store.openMai = false;
                }
            }
        ],
    });

    return await driverObj.drive();
}

export async function StartTourTeacherForumRepliesReplyToReply() {
    store.openMai = false;

    const cleanupHighlights = () => {
        document.querySelectorAll(REPLY_ITEM_SELECTOR).forEach((el) => {
            el.classList.remove('ring-2', 'ring-blue-500', 'rounded-lg', 'shadow-md', 'transition-all');
        });
        document.querySelectorAll(REPLY_TO_REPLY_SELECTOR).forEach((btn) => {
            btn.classList.remove('ring-2', 'ring-blue-500', 'ring-offset-2', 'rounded-lg', 'shadow-lg', 'transition-all');
        });
    };

    const replyable = Array.from(document.querySelectorAll(REPLY_ITEM_SELECTOR))
        .map((reply) => ({ reply, actions: ownActions(reply, REPLY_TO_REPLY_SELECTOR) }))
        .filter((entry) => entry.actions.length > 0);

    if (replyable.length === 0) {
        store.openMai = true;
        return;
    }

    const hasSingleReply = replyable.length === 1;

    const driverObj = drivertour({
        ...configDriver,
        allowKeyboardControl: false,
        onDestroyed: () => {
            store.openMai = true;
        },
        steps: [
            {
                element: () => hasSingleReply ? replyable[0].reply : getFirstVisible('#forum-replies-list'),
                popover: hasSingleReply
                    ? {
                        description: "Interagindo com a ferramenta...",
                        side: "top",
                        align: "center",
                        showButtons: [],
                    }
                    : {
                        description: "Escolha qual resposta deseja responder. Clique no botão 'Responder' da resposta desejada.",
                        side: "top",
                        align: "center",
                        //showButtons: [],
                        onPopoverRender: (popover) => {
                              popover.nextButton.remove();
                              popover.previousButton.remove();
                            addTourFinishButton(popover, driverObj, {
                                label: 'Cancelar operação',
                                onFinish: () => cleanupHighlights(),
                            });
                        },
                    },
                onHighlighted: async (el) => {
                    if (!hasSingleReply) {
                        replyable.forEach(({ reply }) => {
                            reply.classList.add('ring-2', 'ring-blue-500', 'rounded-lg', 'shadow-md', 'transition-all');
                        });
                        return;
                    }

                    await sleep(1200);
                    ownActions(el, REPLY_TO_REPLY_SELECTOR)[0]?.click();

                    const opened = await waitForElement('#form-reply-to-reply', { timeout: 8000 })
                        .then(() => true)
                        .catch(() => false);

                    if (!opened) {
                        cleanupHighlights();
                        await driverObj.destroy();
                        store.openMai = true;
                        return;
                    }

                    await sleep(800);
                    await driverObj.moveNext();
                },
                onDeselected: () => {
                    if (!hasSingleReply) {
                        cleanupHighlights();
                    }
                }
            },
            {
                element: () => getFirstVisible('#form-reply-to-reply'),
                disableActiveInteraction: false,
                popover: {
                    description: "Escreva sua resposta e clique em 'Responder' para publicar, ou em 'Cancelar' para fechar sem salvar.",
                    onPopoverRender: (popover) => {
                        addTourFinishButton(popover, driverObj, {
                            label: 'Cancelar operação',
                            onClick: async () => {
                                Livewire.dispatch('teacher::close-reply-to-reply-forum-topic');
                                await driverObj.destroy();
                                store.openMai = true;
                            }
                         });
                    },
                },
                onHighlighted: async (el) => {
                    const finish = async () => {
                        cleanupHighlights();
                        await driverObj.destroy();
                        store.openMai = false;
                    };

                    if (!el || !el.matches('#form-reply-to-reply')) {
                        await finish();
                        return;
                    }

                    el.querySelector('textarea')?.focus();

                    const cancel = el.querySelector('#btn-cancel-reply-to-reply');
                    const save = el.querySelector('#btn-save-reply-to-reply');

                    cancel?.addEventListener('click', finish, { once: true });
                    save?.addEventListener('click', async () => {
                        await sleep(1500);
                        cleanupHighlights();
                        await finish();
                    }, { once: true });
                }
            }
        ],
    });

    // Com varias respostas, o proprio usuario dispara a acao: o tour so avanca
    // quando o modal realmente abriu.
    if (!hasSingleReply) {
        wireAdvanceOnModalOpen({
            driver: driverObj,
            buttons: replyable.flatMap((entry) => entry.actions),
            modalSelector: '#form-reply-to-reply',
        });
    }

    // Cleanup ao destruir
    const originalDestroy = driverObj.destroy.bind(driverObj);
    driverObj.destroy = async (...args) => {
        cleanupHighlights();
        return originalDestroy(...args);
    };

    return await driverObj.drive();
}

export async function StartTourTeacherForumRepliesEditReply() {
    store.openMai = false;

    const visibleList = getFirstVisible('#forum-replies-list');

    if (!visibleList) {
        store.openMai = true;
        return;
    }

    const editableReplies = Array.from(visibleList.querySelectorAll(REPLY_ITEM_SELECTOR))
        .filter((reply) => ownActions(reply, EDIT_REPLY_SELECTOR).length > 0);

    if (editableReplies.length === 0) {
        store.openMai = true;
        return;
    }

    const hasSingleReply = editableReplies.length === 1;
    let selectedEditBtn = null;

    const openFormStep = {
        element: () => getFirstVisible('#form-edit-reply'),
        disableActiveInteraction: false,
        popover: {
            description: "Altere o texto da sua resposta e clique em 'Alterar resposta', ou em 'Cancelar' para desistir.",
            onPopoverRender: (popover) => {
                addTourFinishButton(popover, driverObj, {
                    label: 'Cancelar operação',
                    onClick: async () => {
                        Livewire.dispatch('teacher::close-edit-reply-forum-topic');
                        await driverObj.destroy();
                        store.openMai = true;
                    }
                });
            },
        },
        onHighlighted: async (el) => {
            const finish = async () => {
                await driverObj.destroy();
                store.openMai = false;
            };

            if (!el || !el.matches('#form-edit-reply')) {
                await finish();
                return;
            }

            el.querySelector('textarea')?.focus();

            const cancel = el.querySelector('#btn-cancel-edit-reply')
                ?? document.querySelector('#btn-cancel-edit-reply');
            const save = el.querySelector('#btn-save-edit-reply')
                ?? document.querySelector('#btn-save-edit-reply');

            cancel?.addEventListener('click', finish, { once: true });
            save?.addEventListener('click', async () => {
                await sleep(1500);
                await finish();
            }, { once: true });
        }
    };

    const buttonStep = {
        element: () => selectedEditBtn,
        disableActiveInteraction: true,
        popover: {
            description: "Interagindo com a ferramenta...",
            side: "top",
            align: "center",
            showButtons: [],
        },
        onHighlighted: async (el) => {
            if (!el) {
                await driverObj.destroy();
                store.openMai = true;
                return;
            }

            await sleep(1200);
            el.click();

            const opened = await waitForElement('#form-edit-reply', { timeout: 8000 })
                .then(() => true)
                .catch(() => false);

            if (!opened) {
                await driverObj.destroy();
                store.openMai = true;
                return;
            }

            await sleep(800);
            await driverObj.moveNext();
        }
    };

    const selectionStep = {
        element: visibleList,
        popover: hasSingleReply
            ? {
                description: "Interagindo com a ferramenta...",
                side: "top",
                align: "center",
                showButtons: [],
            }
            : {
                description: "Clique no botão 'Editar' da resposta que você deseja alterar.",
                side: "top",
                align: "center",
                showButtons: [],
                onPopoverRender: (popover) => {
                    addTourFinishButton(popover, driverObj);
                },
            },
        onHighlighted: async () => {
            if (!hasSingleReply) return;

            await sleep(1200);
            selectedEditBtn = ownActions(editableReplies[0], EDIT_REPLY_SELECTOR)[0];

            try {
                await driverObj.moveNext();
            } catch (error) {
                console.error('Falha ao avançar no tour de edição de resposta:', error);
                await driverObj.destroy();
                store.openMai = true;
            }
        }
    };

    const driverObj = drivertour({
        ...configDriver,
        allowKeyboardControl: false,
        onDestroyed: () => {
            store.openMai = true;
        },
        steps: hasSingleReply
            ? [selectionStep, buttonStep, openFormStep]
            : [selectionStep, openFormStep],
    });

    if (!hasSingleReply) {
        wireAdvanceOnModalOpen({
            driver: driverObj,
            buttons: editableReplies.flatMap((reply) => ownActions(reply, EDIT_REPLY_SELECTOR)),
            modalSelector: '#form-edit-reply',
        });
    }

    return await driverObj.drive();
}

export async function StartTourTeacherForumRepliesDeleteReply() {
    store.openMai = false;

    const visibleList = getFirstVisible('#forum-replies-list');

    if (!visibleList) {
        store.openMai = true;
        return;
    }

    const deletableReplies = Array.from(visibleList.querySelectorAll(REPLY_ITEM_SELECTOR))
        .filter((reply) => ownActions(reply, DELETE_REPLY_SELECTOR).length > 0);

    if (deletableReplies.length === 0) {
        store.openMai = true;
        return;
    }

    const hasSingleReply = deletableReplies.length === 1;
    let selectedDeleteBtn = null;

    const confirmStep = {
        element: () => getFirstVisible('#form-delete-reply'),
        disableActiveInteraction: false,
        popover: {
            description: "Confira a resposta e clique em 'Excluir' para confirmar. <br/> Para desistir, clique em 'Cancelar'. <br/><br/><strong>Atenção:</strong> esta ação não pode ser desfeita.",
            onPopoverRender: (popover) => {
                addTourFinishButton(popover, driverObj, {
                    label: 'Cancelar operação',
                    onClick: async () => {
                        Livewire.dispatch('teacher::close-delete-reply-to-replay-forum-topic');
                        await driverObj.destroy();
                        store.openMai = true;
                    }
                });
            },
        },
        onHighlighted: async (el) => {
            const finish = async () => {
                await driverObj.destroy();
                store.openMai = false;
            };

            if (!el) {
                await finish();
                return;
            }

            const cancel = el.querySelector('#btn-cancel-delete-reply')
                ?? document.querySelector('#btn-cancel-delete-reply');
            const confirm = el.querySelector('#btn-confirm-delete-reply')
                ?? document.querySelector('#btn-confirm-delete-reply');

            cancel?.addEventListener('click', finish, { once: true });
            confirm?.addEventListener('click', async () => {
                await sleep(1500);
                await finish();
            }, { once: true });
        }
    };

    const buttonStep = {
        element: () => selectedDeleteBtn,
        disableActiveInteraction: true,
        allowKeyboardControl: false,
        popover: {
            description: "Interagindo com a ferramenta...",
            side: "top",
            align: "center",
            showButtons: [],
        },
        onHighlighted: async (el) => {
            if (!el) {
                await driverObj.destroy();
                store.openMai = true;
                return;
            }

            await sleep(1200);
            el.click();

            const opened = await waitForElement('#form-delete-reply', { timeout: 8000 })
                .then(() => true)
                .catch(() => false);

            if (!opened) {
                await driverObj.destroy();
                store.openMai = true;
                return;
            }

            await sleep(800);
            await driverObj.moveNext();
        }
    };

    const selectionStep = {
        element: visibleList,
        popover: hasSingleReply
            ? {
                description: "Interagindo com a ferramenta...",
                side: "top",
                align: "center",
                showButtons: [],
            }
            : {
                description: "Clique no botão 'Excluir' da resposta que você deseja remover.",
                side: "top",
                align: "center",
                showButtons: [],
                onPopoverRender: (popover) => {
                    addTourFinishButton(popover, driverObj);
                },
            },
        onHighlighted: async () => {
            if (!hasSingleReply) return;

            await sleep(1200);
            selectedDeleteBtn = ownActions(deletableReplies[0], DELETE_REPLY_SELECTOR)[0];

            try {
                await driverObj.moveNext();
            } catch (error) {
                console.error('Falha ao avançar no tour de exclusão de resposta:', error);
                await driverObj.destroy();
                store.openMai = true;
            }
        }
    };

    const driverObj = drivertour({
        ...configDriver,
        allowKeyboardControl: false,
        onDestroyed: () => {
            store.openMai = true;
        },
        steps: hasSingleReply
            ? [selectionStep, buttonStep, confirmStep]
            : [selectionStep, confirmStep],
    });

    if (!hasSingleReply) {
        wireAdvanceOnModalOpen({
            driver: driverObj,
            buttons: deletableReplies.flatMap((reply) => ownActions(reply, DELETE_REPLY_SELECTOR)),
            modalSelector: '#form-delete-reply',
        });
    }

    return await driverObj.drive();
}

export async function StartTourTeacherForumShowNewTopic() {
    store.openMai = false;

    const buttonNewTopic = document.querySelector("#forum-show-new-topic");
    if (!buttonNewTopic) {
        store.openMai = true;
        return;
    }

    let modalCleanup = () => {};

    const driverObj = drivertour({
        ...configDriver,
        allowKeyboardControl: false,
        onDestroyed: () => {
            modalCleanup();
            store.openMai = true;
        },
        steps: [
            {
                element: "#forum-show-new-topic",
                popover: {
                    description: "Interagindo com a ferramenta...",
                    showButtons: [],
                },
                onHighlighted: async (el) => {
                    if (!el) {
                        await driverObj.destroy();
                        return;
                    }

                    await sleep(500);
                    el.click();

                    const opened = await waitForElement("#input-register-topic")
                        .then(() => true)
                        .catch(() => false);

                    if (!opened) {
                        await driverObj.destroy();
                        return;
                    }

                    await driverObj.moveNext();
                }
            },
            {
                element: "#input-register-topic",
                disableActiveInteraction: false,
                popover: {
                    description: "Escreva o título do tópico, selecione a turma e clique em 'Criar' para publicar ou em 'Cancelar' para fechar o formulário.",
                    onPopoverRender: (popover) => {
                        addTourFinishButton(popover, driverObj, {
                            label: 'Cancelar operação',
                            onClick: async () => {
                                window.Livewire?.dispatch?.("teacher::close-create-forum-topic");
                                await driverObj.destroy();
                                store.openMai = true;
                            },
                        });
                    },
                },
                onHighlighted: async () => {
                    const cancel = document.querySelector("#btn-cancel-register");
                    const save = document.querySelector("#btn-save-register");

                    if (!cancel || !save) return;

                    const finishOnCancel = async () => {
                        await driverObj.destroy();
                        window.Livewire?.dispatch?.("teacher::close-create-forum-topic");
                        store.openMai = false;
                    };
                    const finishOnSave = async () => {
                        await sleep(1000);
                        await driverObj.destroy();
                        store.openMai = false;
                    };

                    cancel.addEventListener("click", finishOnCancel, { once: true });
                    save.addEventListener("click", finishOnSave, { once: true });
                    modalCleanup = () => {
                        cancel.removeEventListener("click", finishOnCancel);
                        save.removeEventListener("click", finishOnSave);
                    };
                }
            }
        ]
    });

    return await driverObj.drive();
}

export async function StartTourTeacherForumShowSelectTopic() {
    store.openMai = false;

    const visibleList = getFirstVisible('#forum-show-all-topics, #forum-show-my-topics');
    if (!visibleList) {
        store.openMai = true;
        return;
    }

    const topics = Array.from(visibleList.querySelectorAll('.topic-item'));
    let navigatingAway = false;
    if (topics.length === 0) {
        store.openMai = true;
        return;
    }

    topics.forEach(t => t.style.cursor = 'pointer');

    const handleTopicClick = (e) => {
        if (e.target.closest('a, button')) return;

        const topic = e.currentTarget;
        const link = topic.querySelector('a[href]');
        if (!link) return;

        e.preventDefault();
        e.stopPropagation();

        navigatingAway = true;
        void driverObj.destroy();
        topics.forEach(t => t.removeEventListener('click', handleTopicClick));
        topics.forEach(t => t.style.cursor = '');

        navigateTo(link.href);
    };

    topics.forEach(t => t.addEventListener('click', handleTopicClick));

    const driverObj = drivertour({
        ...configDriver,
        allowKeyboardControl: false,
        steps: [
            {
                element: visibleList,
                popover: {
                    description: "Clique em qualquer tópico da lista para visualizar suas respostas e participar da discussão.",
                    side: "top",
                    align: "center",
                    onPopoverRender: (popover) => {
                        addTourFinishButton(popover, driverObj, {
                            label: 'Finalizar Tour',
                        });
                    },
                },
            },
        ],
        onDestroyed: () => {
            topics.forEach(t => t.removeEventListener('click', handleTopicClick));
            topics.forEach(t => t.style.cursor = '');
            if (!navigatingAway) store.openMai = true;
        },
    });

    return await driverObj.drive();
}

export async function StartTourTeacherForumShowMyTopics() {
    lockPage();
    store.openMai = false;

    const driverObj = drivertour({
        ...configDriver,
        allowKeyboardControl: false,
        onDestroyed: () => {
            unlockPage();
            store.openMai = true;
        },
        steps: [
            {
                disableActiveInteraction: true,
                element: "#my-topics-created",
                popover: {
                    description: "Interagindo com a ferramenta...",
                    side: "top",
                    align: "center",
                    showButtons: [],
                },
                onHighlighted: async (el) => {
                    if (!el) {
                        await driverObj.destroy();
                        return;
                    }

                    await sleep(1000);
                    el.click();

                    const opened = await waitForElement('#forum-show-my-topics')
                        .then(() => true)
                        .catch(() => false);

                    if (!opened) {
                        await driverObj.destroy();
                        return;
                    }

                    await driverObj.moveNext();
                }
            },
            {
                disableActiveInteraction: true,
                element: "#forum-show-my-topics",
                popover: {
                    description: "Interagindo com a ferramenta...",
                    side: "top",
                    align: "center",
                    showButtons: [],
                },
                onHighlighted: async () => {
                    unlockPage();
                    await sleep(2000);
                    await driverObj.destroy();
                }
            }
        ],
    });

    return await driverObj.drive();
}



export async function StartTourTeacherForumShowAllTopics() {
    store.openMai = false;

    const allTopicsTab = document.querySelector('#forum-show-tabs a[role="tab"]:first-of-type');
    if (!allTopicsTab) {
        store.openMai = true;
        return;
    }

    lockPage();

    const driverObj = drivertour({
        ...configDriver,
        allowKeyboardControl: false,
        onDestroyed: () => {
            unlockPage();
            store.openMai = true;
        },
        steps: [
            {
                disableActiveInteraction: true,
                element: '#forum-show-tabs a[role="tab"]:first-of-type',
                popover: {
                    description: "Interagindo com a ferramenta...",
                    side: "top",
                    align: "center",
                    showButtons: [],
                },
                onHighlighted: async (el) => {
                    if (!el) {
                        await driverObj.destroy();
                        return;
                    }

                    await sleep(1000);
                    el.click();

                    const opened = await waitForElement('#forum-show-all-topics')
                        .then(() => true)
                        .catch(() => false);

                    if (!opened) {
                        await driverObj.destroy();
                        return;
                    }

                    await driverObj.moveNext();
                }
            },
            {
                disableActiveInteraction: true,
                element: "#forum-show-all-topics",
                popover: {
                    description: "Interagindo com a ferramenta...",
                    side: "top",
                    align: "center",
                    showButtons: [],
                },
                onHighlighted: async () => {
                    unlockPage();
                    await sleep(2000);
                    await driverObj.destroy();
                }
            }
        ],
    });

    return await driverObj.drive();
}

const EDIT_TOPIC_SELECTOR = '[data-tour="edit-topic"]';
const TOPIC_ITEM_SELECTOR = '.topic-item';
const TOPIC_TITLE_SELECTOR = '[data-tour="topic-title"]';
const EDIT_MODAL_FORM_SELECTOR = '#form-edit-topic';
const EDIT_MODAL_INPUT_SELECTOR = '#form-edit-topic input, #input-edit-topic input, #input-edit-topic';

export async function StartTourTeacherForumShowEditTopic() {
    store.openMai = false;

    const visibleList = getFirstVisible('#forum-show-all-topics, #forum-show-my-topics');

    if (!visibleList) {
        store.openMai = true;
        return;
    }

    const editableTopics = Array.from(visibleList.querySelectorAll(TOPIC_ITEM_SELECTOR))
        .filter((topic) => topic.querySelector(EDIT_TOPIC_SELECTOR));

    if (editableTopics.length === 0) {
        store.openMai = true;
        return;
    }

    const hasSingleTopic = editableTopics.length === 1;
    let selectionHandled = false;
    let selectedTopic = null;
    let selectedTopicKey = null;

    editableTopics.forEach((topic) => {
        const title = topic.querySelector(TOPIC_TITLE_SELECTOR);
        if (title) title.style.cursor = 'pointer';
    });

    const findTopicByKey = (key) => {
        if (!key) return null;

        // O ":" do wire:key precisa de escape em seletores CSS.
        return document.querySelector(`[wire\\:key="${CSS.escape(key)}"]`);
    };

    // O driver reavalia o elemento a cada transição. Guardamos o nó como
    // cache e só reconsultamos o DOM se o Livewire o tiver substituído.
    const resolveEditButton = () => {
        let topic = selectedTopic?.isConnected ? selectedTopic : findTopicByKey(selectedTopicKey);

        if (!topic) return null;

        selectedTopic = topic;

        return topic.querySelector(EDIT_TOPIC_SELECTOR);
    };
    let guardAttached = false;
    const guardEvents = ['mousedown', 'mouseup', 'click', 'keydown'];

    const handleGuardedEvent = (event) => {
        if (event.target.closest('button') || event.target.closest(EDIT_TOPIC_SELECTOR)) return;

        event.preventDefault();
        event.stopPropagation();

        // Enter sobre o título também navegaria.
        if (event.type === 'keydown') return;

        if (event.type !== 'click' || selectionHandled) return;

        const topic = event.target.closest(TOPIC_ITEM_SELECTOR);
        if (!topic || !editableTopics.includes(topic)) return;

        selectTopic(topic);
    };

    const attachGuard = () => {
        if (guardAttached) return;

        guardEvents.forEach((type) => visibleList.addEventListener(type, handleGuardedEvent, true));
        guardAttached = true;
    };

    const detachGuard = () => {
        editableTopics.forEach((topic) => {
            const title = topic.querySelector(TOPIC_TITLE_SELECTOR);
            if (title) title.style.cursor = '';
        });

        if (!guardAttached) return;

        guardEvents.forEach((type) => visibleList.removeEventListener(type, handleGuardedEvent, true));
        guardAttached = false;
    };

    async function selectTopic(topic) {
        if (selectionHandled) return;
        selectionHandled = true;

        selectedTopic = topic;
        selectedTopicKey = topic.getAttribute('wire:key');

        // Libera a lista antes de o tour destacar o botão "Editar".
        detachGuard();

        topic.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        await sleep(300);

        try {
            await driverObj.moveNext();
        } catch (error) {
            console.error('Falha ao avançar no tour de edição de tópico:', error);
            await driverObj.destroy();
            store.openMai = true;
        }
    }

    if (!hasSingleTopic) attachGuard();

    const driverObj = drivertour({
        ...configDriver,
        allowKeyboardControl: false,

        steps: [
            {
                element: visibleList,
                popover: hasSingleTopic
                    ? {
                        description: "Interagindo com a ferramenta...",
                        side: "top",
                        align: "center",
                        showButtons: [],
                    }
                    : {
                        description: "Clique no título do tópico que você deseja editar.",
                        side: "top",
                        align: "center",
                        showButtons: [],
                        onPopoverRender: (popover) => {

                            addTourFinishButton(popover, driverObj);
                        },
                    },
                onHighlighted: async () => {
                    if (!hasSingleTopic) return;

                    await sleep(1000);
                    await selectTopic(editableTopics[0]);
                }
            },
            {
                element: resolveEditButton,
                disableActiveInteraction: true,
                popover: {
                    description: "Interagindo com a ferramenta...",
                    side: "top",
                    align: "center",
                    showButtons: [],
                },
                onHighlighted: async (el) => {
                    if (!el) {
                        await driverObj.destroy();
                        store.openMai = true;
                        return;
                    }

                    await sleep(1000);
                    el.click();

                    // O modal do Livewire já existe no DOM, só fica invisível.
                    // Esperamos ficar visível para não avançar antes da abertura.
                    const opened = await waitForElement(EDIT_MODAL_INPUT_SELECTOR, { timeout: 2000 })
                        .then(() => true)
                        .catch(() => false);

                    if (!opened) {
                        await driverObj.destroy();
                        store.openMai = true;
                        return;
                    }

                    await sleep(1000);
                    await driverObj.moveNext();
                }
            },
            {
                element: () => getFirstVisible(EDIT_MODAL_FORM_SELECTOR),
                disableActiveInteraction: false,
                popover: {
                    description: "Altere o título do tópico e clique em 'Salvar Alterações'. <br/> Se quiser apenas sair do tour, clique em 'Cancelar'.",
                    onPopoverRender: (popover) => {
                        addTourFinishButton(popover, driverObj, {
                            label: 'Cancelar operação',
                            onClick: async () => {
                                Livewire.dispatch('teacher::close-edit-forum-topic');
                                await driverObj.destroy();
                                store.openMai = true;
                            }
                        });
                    },
                },
                onHighlighted: async (el) => {
                    const finish = async () => {
                        Livewire.dispatch('teacher::close-edit-forum-topic');
                        detachGuard();
                        await driverObj.destroy();
                        store.openMai = false;
                    };

                    if (!el || !el.matches(EDIT_MODAL_FORM_SELECTOR)) {
                        await finish();
                        return;
                    }

                    // Deixa o título pronto para sobrescrita.
                    const input = getFirstVisible(EDIT_MODAL_INPUT_SELECTOR);
                    if (input && typeof input.select === 'function') {
                        input.focus();
                        input.select();
                    }

                    const cancel = el.querySelector('#btn-cancel-edit');
                    const save = el.querySelector('#btn-save-edit');

                    cancel?.addEventListener('click', async () => {
                        await finish();
                    });
                    save?.addEventListener('click', async () => {
                        await sleep(1200);
                        await finish();
                    }, { once: true });
                }
            }
        ],

        onDestroyed: () => {
            detachGuard();
            store.openMai = true;
        },
    });

    return await driverObj.drive();
}

const DELETE_TOPIC_SELECTOR = '[data-tour="delete-topic"]';
const DELETE_MODAL_FORM_SELECTOR = '#form-delete-topic';

export async function StartTourTeacherForumShowDeleteTopic() {
    store.openMai = false;

    const visibleList = getFirstVisible('#forum-show-all-topics, #forum-show-my-topics');

    if (!visibleList) {
        store.openMai = true;
        return;
    }

    const deletableTopics = Array.from(visibleList.querySelectorAll(TOPIC_ITEM_SELECTOR))
        .filter((topic) => topic.querySelector(DELETE_TOPIC_SELECTOR));

    if (deletableTopics.length === 0) {
        store.openMai = true;
        return;
    }

    const hasSingleTopic = deletableTopics.length === 1;
    let selectionHandled = false;
    let selectedTopic = null;
    let selectedTopicKey = null;
    let revealedActions = null;

    deletableTopics.forEach((topic) => {
        const title = topic.querySelector(TOPIC_TITLE_SELECTOR);
        if (title) title.style.cursor = 'pointer';
    });

    const findTopicByKey = (key) => {
        if (!key) return null;

        // O ":" do wire:key precisa de escape em seletores CSS.
        return document.querySelector(`[wire\\:key="${CSS.escape(key)}"]`);
    };

    const resolveDeleteButton = () => {
        let topic = selectedTopic?.isConnected ? selectedTopic : findTopicByKey(selectedTopicKey);

        if (!topic) return null;

        selectedTopic = topic;

        return topic.querySelector(DELETE_TOPIC_SELECTOR);
    };

    // Mesmo bloqueio do tour de edição: o <a> do título usa wire:navigate, que
    // registra a navegação no "mouseup" disparado pelo "mousedown" do link.
    let guardAttached = false;
    const guardEvents = ['mousedown', 'mouseup', 'click', 'keydown'];

    const handleGuardedEvent = (event) => {
        if (event.target.closest('button') || event.target.closest(DELETE_TOPIC_SELECTOR)) return;

        event.preventDefault();
        event.stopPropagation();

        if (event.type === 'keydown') return;
        if (event.type !== 'click' || selectionHandled) return;

        const topic = event.target.closest(TOPIC_ITEM_SELECTOR);
        if (!topic || !deletableTopics.includes(topic)) return;

        selectTopic(topic);
    };

    const attachGuard = () => {
        if (guardAttached) return;

        guardEvents.forEach((type) => visibleList.addEventListener(type, handleGuardedEvent, true));
        guardAttached = true;
    };

    const detachGuard = () => {
        deletableTopics.forEach((topic) => {
            const title = topic.querySelector(TOPIC_TITLE_SELECTOR);
            if (title) title.style.cursor = '';
        });

        if (revealedActions) {
            revealedActions.style.opacity = '';
            revealedActions = null;
        }

        if (!guardAttached) return;

        guardEvents.forEach((type) => visibleList.removeEventListener(type, handleGuardedEvent, true));
        guardAttached = false;
    };

    async function selectTopic(topic) {
        if (selectionHandled) return;
        selectionHandled = true;

        selectedTopic = topic;
        selectedTopicKey = topic.getAttribute('wire:key');

        detachGuard();

        revealedActions = topic.querySelector(DELETE_TOPIC_SELECTOR)?.closest('div') ?? null;
        if (revealedActions) revealedActions.style.opacity = '1';

        topic.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        await sleep(300);

        try {
            await driverObj.moveNext();
        } catch (error) {
            console.error('Falha ao avançar no tour de exclusão de tópico:', error);
            await driverObj.destroy();
            store.openMai = true;
        }
    }

    if (!hasSingleTopic) attachGuard();

    const driverObj = drivertour({
        ...configDriver,
        allowKeyboardControl: false,

        steps: [
            {
                element: visibleList,
                popover: hasSingleTopic
                    ? {
                        description: "Interagindo com a ferramenta...",
                        side: "top",
                        align: "center",
                        showButtons: [],
                    }
                    : {
                        description: "Clique no título do tópico que você deseja excluir.",
                        side: "top",
                        align: "center",
                        showButtons: [],
                        onPopoverRender: (popover) => {
                            addTourFinishButton(popover, driverObj);
                        },
                    },
                onHighlighted: async () => {
                    if (!hasSingleTopic) return;

                    await sleep(1500);
                    await selectTopic(deletableTopics[0]);
                }
            },
            {
                element: resolveDeleteButton,
                disableActiveInteraction: true,
                popover: {
                    description: "Interagindo com a ferramenta...",
                    side: "top",
                    align: "center",
                    showButtons: [],
                },
                onHighlighted: async (el) => {
                    if (!el) {
                        await driverObj.destroy();
                        store.openMai = true;
                        return;
                    }

                    await sleep(1000);
                    el.click();

                    const opened = await waitForElement(DELETE_MODAL_FORM_SELECTOR, { timeout: 2000 })
                        .then(() => true)
                        .catch(() => false);

                    if (!opened) {
                        await driverObj.destroy();
                        store.openMai = true;
                        return;
                    }

                    await sleep(800);
                    await driverObj.moveNext();
                }
            },
            {
                element: () => getFirstVisible(DELETE_MODAL_FORM_SELECTOR),
                disableActiveInteraction: false,
                popover: {
                    description: "Confira o tópico e clique em 'Sim, Excluir Tópico' para confirmar. <br/> Para desistir, clique em 'Cancelar'. <br/><br/><strong>Atenção:</strong> esta ação não pode ser desfeita.",
                    onPopoverRender: (popover) => {
                         addTourFinishButton(popover, driverObj, {
                            label: 'Cancelar operação',
                            onClick: async () => {
                                Livewire.dispatch('teacher::close-delete-forum-topic');
                                await driverObj.destroy();
                                store.openMai = true;
                            }
                        });
                    },
                },
                onHighlighted: async (el) => {
                    const finish = async () => {
                        Livewire.dispatch('teacher::close-delete-forum-topic');
                        detachGuard();
                        await driverObj.destroy();
                        store.openMai = false;
                    };

                    if (!el || !el.matches(DELETE_MODAL_FORM_SELECTOR)) {
                        await finish();
                        return;
                    }

                    const cancel = el.querySelector('#btn-cancel-delete');
                    const confirm = el.querySelector('#btn-confirm-delete');

                    cancel?.addEventListener('click', finish, { once: true });
                    confirm?.addEventListener('click', async () => {
                        await sleep(1500);
                        await finish();
                    }, { once: true });
                }
            }
        ],

        onDestroyed: () => {
            detachGuard();
            store.openMai = true;
        },
    });

    return await driverObj.drive();
}
