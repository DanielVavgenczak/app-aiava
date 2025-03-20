import { AddButtonActions, configDriver,startDriverTour } from "./utils";
export async function StartTourCreateDiscipline() {
    let store = Alpine.store("tourguide");
    store.openMai = false;
    const newDisciplineButton = document.querySelector("#discipline-create");
    const driverObj = drivertour({
        ...configDriver,
        steps: [
            {
                element: `#${newDisciplineButton.id}`,
                popover: {
                    description: `<h2>Criar uma disciplina</h2>`,
                    showButtons: [""],
                },
                onHighlighted: async (el) => {
                    await new Promise((resolve) => setTimeout(resolve, 3000));
                    if (!newDisciplineButton) {
                        console.error("não encontramos ela aqui");
                    }
                    newDisciplineButton.click();
                    await new Promise((resolve) => setTimeout(resolve, 1000));
                    await driverObj.moveNext();
                },
            },
            {
                element: `#discipline-input-title`,
                popover: {
                    description: "Adicione o titulo da disciplina",
                    onPopoverRender: (popover, { config, state }) => {
                        const actions = AddButtonActions(
                            popover,
                            "voltar ao inicio",
                        );
                        actions.addEventListener("click", () => {
                            Livewire.dispatch("to-close-create");
                            driverObj.destroy();
                            store.openMai = true;
                        });
                    },
                },
            },
            {
                element: `#discipline-input-description`,
                popover: {
                    description: "Adicione o titulo da disciplina",
                    onPopoverRender: (popover, { config, state }) => {
                        const actions = AddButtonActions(
                            popover,
                            "voltar ao inicio",
                        );
                        actions.addEventListener("click", () => {
                            Livewire.dispatch("to-close-create");
                            driverObj.destroy();
                            store.openMai = true;
                        });
                    },
                },
            },
            {
                element: `#discipline-save`,
                popover: {
                    description: `Clique em 'Registrar' para salver ou 'Cancelar' para cancelar o registro.`,
                    onPopoverRender: (popover, { config, state }) => {
                        const actions = AddButtonActions(
                            popover,
                            "voltar ao inicio",
                        );
                        actions.addEventListener("click", () => {
                            Livewire.dispatch("to-close-create");
                            driverObj.destroy();
                            store.openMai = true;
                        });
                    },
                },
                onHighlighted: async (el) => {
                    const toCloseActions = () => {
                        Livewire.dispatch("to-close-create");
                        store.openMai = false;
                        driverObj.destroy();
                    };
                    const btnCancel = el.querySelector("#create-cancel");
                    const btnCreate = el.querySelector("#create-confirm");
                    if (!btnCancel || !btnCreate) {
                        console.error("element not found", btnCancel);
                    }
                    btnCancel.addEventListener("click", toCloseActions);
                    btnCreate.addEventListener("click", toCloseActions);
                },
            },
        ],
    });
    return await driverObj.drive();
}
/** Tour to edit disciplines */
export async function StartTourEditDiscipline() {
    let store = Alpine.store("tourguide");
    store.openMai = false;
     const createInitialTour = () => {
        return drivertour({
            ...configDriver,
            steps: [
                {
                    element: `#disciplines-all`,
                    popover: {
                        description: `<div class="p-4 "><p class="text-base">Selecione a disciplina que deseja editar</p></div>`,
                        onPopoverRender: (popover, { config, state }) => {
                            const actions = AddButtonActions(popover, "voltar ao inicio");
                            actions.addEventListener('click', async () => {
                                driverObj.destroy();
                                const removeIndex = document.querySelectorAll("div.content-discipline");
                                removeIndex.forEach((item) => item.style.zIndex = "0");
                                store.openMai = true;
                            });
                        }
                    },
                    onHighlighted: async (el) => {
                        const disciplines = el.querySelectorAll("div.discipline-card");
                        let disciplineSelected = false;
                        if (!disciplines || disciplines.length === 0) return;
                        disciplines.forEach((discipline) => {
                            discipline.style.zIndex = "200000000";
                            discipline.addEventListener('click', async (event) => {
                                if (disciplineSelected) return;
                                event.preventDefault();
                                disciplineSelected = true;
                                // Remove z-index das disciplinas não selecionadas
                                disciplines.forEach((otherDiscipline) => {
                                    if (discipline !== otherDiscipline) {
                                        otherDiscipline.style.zIndex = "0";
                                    }
                                });

                                const disciplineId = discipline.getAttribute('id');
                                const dataId = discipline.getAttribute('data-tour');
                                const tourOperations = `tour-operations-${dataId}`
                                const disciplinaInputEditTitle = `discipline-edit-form-title-${dataId}`
                                const disciplineInputEditDescription = `discipline-edit-form-description-${dataId}`
                                const disciplineFormActions = `discipline-edit-form-actions-${dataId}`
                                // Destrói o tour atual
                                await driverObj.destroy();
                                // Cria um novo tour com os passos atualizados
                                const secondStep = [
                                    {
                                        element: `#${disciplineId}`,
                                        popover: {
                                            description: `<div class="p-4"><p class="text-base">Vamos iniciar o processo de edição.</p></div>`,
                                            side: "bottom",
                                            align: "start",
                                            showButtons:['']
                                        },
                                        onHighlighted: async (el) => {
                                            await new Promise((resolve) => setTimeout(resolve, 3000));
                                            el.style.zIndex = "0";
                                            await driverObj.moveNext();
                                        }
                                    },
                                    {
                                        element: `#${tourOperations}`, // Seleciona o elemento com base no ID
                                        popover: {
                                            description: `<div class="p-4"><p class="text-base">Vamos abrir o formulário de edição</p></div>`,
                                            side: "bottom",
                                            align: "start",
                                            showButtons:['']
                                        },
                                        onHighlighted: async (el) => {
                                            await new Promise((resolve) => setTimeout(resolve, 3000));
                                            const dropDownClick = document.querySelector(`#${tourOperations}`)
                                            if(!dropDownClick) return;
                                            await dropDownClick.click()
                                            el.style.zIndex = "0";
                                            await driverObj.moveNext();
                                        }
                                    },
                                    {
                                        element: `#discipline-edit-${dataId}`,
                                        popover: {
                                            description: `Clique aqui para editar a disciplina.`,
                                            side: "right",
                                            align: "center",
                                            showButtons:['']
                                        },
                                        onHighlighted: async (el) => {
                                            await new Promise((resolve) => setTimeout(resolve, 3000));
                                            el.click(); // Simula o clique no botão de edição
                                            await driverObj.moveNext();
                                        }
                                    },
                                    {
                                        element: `#${disciplinaInputEditTitle}`, // Seleciona o elemento com base no ID
                                        popover: {
                                            description: "Mude o titulo para o qual deseja, ou então vá até o campo descrição clicando em Proximo",
                                            side: "top",
                                            align: "start",
                                            onPopoverRender: (popover, { config, state }) => {
                                                const actions =  AddButtonActions(popover, "opções")
                                                actions.addEventListener('click', async () => {
                                                    Livewire.dispatch('to-close')
                                                    await driverObj.destroy();
                                                    store.openMai = true;
                                                })
                                            }
                                        },
                                        onHighlighted: async(el) => {
                                            el.classList.add('driver-active-element')
                                        }
                                    },
                                    {
                                        element: `#${disciplineInputEditDescription}`, // Seleciona o elemento com base no ID
                                        popover: {
                                            description: "Mude o titulo para o qual deseja, ou então vá até o campo descrição clicando em Proximo",
                                            side: "top",
                                            align: "start",
                                            onPopoverRender: (popover, { config, state }) => {
                                                const actions =  AddButtonActions(popover, "voltar ao inicio")
                                                actions.addEventListener('click', async () => {
                                                    Livewire.dispatch('to-close')
                                                    await driverObj.destroy();
                                                    store.openMai = true;
                                                })
                                            }
                                        },
                                        onHighlighted: async(el) => {
                                            el.classList.add('driver-active-element')
                                        }
                                    },
                                    {
                                        element: `#${disciplineFormActions}`, // Seleciona o elemento com base no ID
                                        popover: {
                                            description: "Clique em 'Cancelar' para desistir ou 'Editar' para salvar",
                                            side: "top",
                                            align: "start",
                                            onPopoverRender: (popover, { config, state }) => {
                                                const actions =  AddButtonActions(popover, "voltar  ao inicio")
                                                actions.addEventListener('click', async () => {
                                                    Livewire.dispatch('to-close')
                                                    await driverObj.destroy();
                                                    store.openMai = true;
                                                })
                                            }
                                        },
                                        onHighlighted: async(el) => {
                                            const cancelButton = el.querySelector(`#discipline-edit-cancel-${dataId}`)
                                            const save = el.querySelector(`#discipline-edit-form-send-${dataId}`)
                                            const close =  async () => {
                                                await driverObj.destroy();
                                                store.openMai = true;
                                            }
                                            cancelButton.addEventListener('click', close)
                                            save.addEventListener('click', close)
                                        }
                                    }
                                    // Adicione mais passos conforme necessário
                                ];

                                // Cria um novo tour com os passos atualizados
                                const updatedDriverObj = drivertour({
                                    ...configDriver,
                                    steps: secondStep,
                                });

                                // Inicia o novo tour
                                await updatedDriverObj.drive();
                            });
                        });
                    },
                }
            ],
        });
    };
    const driverObj = createInitialTour();
    return await driverObj.drive();
}
/** Delete */
export async function StartTourDeleteDiscipline() {
    let store = Alpine.store("tourguide");
    store.openMai = false;
    const createInitialTour = () => {
        return drivertour({
            ...configDriver,
            steps: [
                {
                    element: `#disciplines-all`,
                    popover: {
                        description: `<div class="p-4 "><p class="text-base">Selecione a disciplina que deseja Deletar</p></div>`,
                        onPopoverRender: (popover, { config, state }) => {
                            const actions = AddButtonActions(popover, "voltar ao inicio");
                            actions.addEventListener('click', async () => {
                                driverObj.destroy();
                                const removeIndex = document.querySelectorAll("div.content-discipline");
                                removeIndex.forEach((item) => item.style.zIndex = "0");
                                store.openMai = true;
                            });
                        }
                    },
                    onHighlighted: async (el) => {
                        const disciplines = el.querySelectorAll("div.discipline-card");
                        let disciplineSelected = false;
                        if (!disciplines || disciplines.length === 0) return;
                        disciplines.forEach((discipline) => {
                            discipline.style.zIndex = "200000000";
                            discipline.addEventListener('click', async (event) => {
                                if (disciplineSelected) return;
                                event.preventDefault();
                                disciplineSelected = true;
                                // Remove z-index das disciplinas não selecionadas
                                disciplines.forEach((otherDiscipline) => {
                                    if (discipline !== otherDiscipline) {
                                        otherDiscipline.style.zIndex = "0";
                                    }
                                });

                                const disciplineId = discipline.getAttribute('id');
                                const dataId = discipline.getAttribute('data-tour');
                                const tourOperations = `tour-operations-${dataId}`
                                const readText = `discipline-delete-text-${dataId}`
                                const disciplineDeleteActions = `discipline-delete-actions-${dataId}`
                                // Destrói o tour atual
                                await driverObj.destroy();
                                // Cria um novo tour com os passos atualizados
                                const secondStep = [
                                    {
                                        element: `#${disciplineId}`,
                                        popover: {
                                            description: `<div class="p-4"><p class="text-base">Vamos iniciar o processo de exclusão.</p></div>`,
                                            side: "bottom",
                                            align: "start",
                                            showButtons:['']
                                        },
                                        onHighlighted: async (el) => {
                                            await new Promise((resolve) => setTimeout(resolve, 3000));
                                            el.style.zIndex = "0";
                                            await driverObj.moveNext();
                                        }
                                    },
                                    {
                                        element: `#${tourOperations}`, // Seleciona o elemento com base no ID
                                        popover: {
                                            description: `<div class="p-4"><p class="text-base">Vamos abrir o modal de exlusão0</p></div>`,
                                            side: "bottom",
                                            align: "start",
                                            showButtons:['']
                                        },
                                        onHighlighted: async (el) => {
                                            await new Promise((resolve) => setTimeout(resolve, 3000));
                                            const dropDownClick = document.querySelector(`#${tourOperations}`)
                                            if(!dropDownClick) return;
                                            await dropDownClick.click()
                                            el.style.zIndex = "0";
                                            await driverObj.moveNext();
                                        }
                                    },
                                    {
                                        element: `#discipline-delete-${dataId}`,
                                        popover: {
                                            description: `Vamos abrir a modal de deletar`,
                                            side: "right",
                                            align: "center",
                                            showButtons:['']
                                        },
                                        onHighlighted: async (el) => {
                                            await new Promise((resolve) => setTimeout(resolve, 3000));
                                            el.click(); // Simula o clique no botão de edição
                                            await driverObj.moveNext();
                                        }
                                    },
                                    {
                                        element: `#${readText}`, // Seleciona o elemento com base no ID
                                        popover: {
                                            description: "Leia o texto para continuar",
                                            side: "top",
                                            align: "start",
                                            onPopoverRender: (popover, { config, state }) => {
                                                const actions =  AddButtonActions(popover, "voltar ao inicio")
                                                actions.addEventListener('click', async () => {
                                                    Livewire.dispatch('to-close')
                                                    await driverObj.destroy();
                                                    store.openMai = true;
                                                })
                                            }
                                        },
                                        onHighlighted: async(el) => {
                                            el.classList.add('driver-active-element')
                                        }
                                    },
                                    
                                    {
                                        element: `#${disciplineDeleteActions}`, // Seleciona o elemento com base no ID
                                        popover: {
                                            description: "Clique em 'Cancelar' para desistir ou 'Deltar' para finalizar",
                                            side: "top",
                                            align: "start",
                                            onPopoverRender: (popover, { config, state }) => {
                                                const actions =  AddButtonActions(popover, "voltar  ao inicio")
                                                actions.addEventListener('click', async () => {
                                                    Livewire.dispatch('to-close')
                                                    await driverObj.destroy();
                                                    store.openMai = true;
                                                })
                                            }
                                        },
                                        onHighlighted: async(el) => {
                                            const cancelButton = el.querySelector(`#discipline-delete-cancel-${dataId}`)
                                            const save = el.querySelector(`#discipline-delete-send-${dataId}`)
                                            const close =  async () => {
                                                await driverObj.destroy();
                                                store.openMai = true;
                                            }
                                            cancelButton.addEventListener('click', close)
                                            save.addEventListener('click', close)
                                        }
                                    }
                                ];

                                // Cria um novo tour com os passos atualizados
                                const updatedDriverObj = drivertour({
                                    ...configDriver,
                                    steps: secondStep,
                                });

                                // Inicia o novo tour
                                await updatedDriverObj.drive();
                            });
                        });
                    },
                }
            ],
        });
    };
    const driverObj = createInitialTour();
    return await driverObj.drive();
}

export async function StartTourSearchDiscipline(){
    let store = Alpine.store("tourguide");
    store.openMai = false;
     const createInitialTour = () => {
        return drivertour({
            ...configDriver,
            steps: [
                {
                    element: `#discipline-input-search`,
                    popover: {
                        description: `<div class="p-4 "><p class="text-base">Digite a disciplina que deseja filtrar</p></div>`,
                        onPopoverRender: (popover, { config, state }) => {
                            const actions = AddButtonActions(popover, "voltar ao inicio");
                            actions.addEventListener('click', async () => {
                                driverObj.destroy();
                                const removeIndex = document.querySelectorAll("div.content-discipline");
                                removeIndex.forEach((item) => item.style.zIndex = "0");
                                store.openMai = true;
                            });
                        }
                    },                   
                }
            ],
        });
    };
    const driverObj = createInitialTour();
    return await driverObj.drive();
}

export async function StartTourManagerDiscipline(){
    let store = Alpine.store("tourguide");
    store.openMai = false;
    const createInitialTour = () => {
        return drivertour({
            ...configDriver,
            steps: [
                {
                    element: `#disciplines-all`,
                    popover: {
                        description: `<div class="p-4 "><p class="text-base">Selecione a disciplina que deseja gerenciar</p></div>`,
                        onPopoverRender: (popover, { config, state }) => {
                            const actions = AddButtonActions(popover, "voltar ao inicio");
                            actions.addEventListener('click', async () => {
                                driverObj.destroy();
                                const removeIndex = document.querySelectorAll("div.content-discipline");
                                removeIndex.forEach((item) => item.style.zIndex = "0");
                                store.openMai = true;
                            });
                        }
                    },
                    onHighlighted: async (el) => {
                        const disciplines = el.querySelectorAll("div.discipline-card");
                        let disciplineSelected = false;
                        if (!disciplines || disciplines.length === 0) return;
                        disciplines.forEach((discipline) => {
                            discipline.style.zIndex = "200000000";
                            discipline.addEventListener('click', async (event) => {
                                if (disciplineSelected) return;
                                event.preventDefault();
                                disciplineSelected = true;
                                // Remove z-index das disciplinas não selecionadas
                                disciplines.forEach((otherDiscipline) => {
                                    if (discipline !== otherDiscipline) {
                                        otherDiscipline.style.zIndex = "0";
                                    }
                                });

                                const disciplineId = discipline.getAttribute('id');
                                const dataId = discipline.getAttribute('data-tour');
                                const tourOperations = `tour-operations-${dataId}`
                                const readText = `discipline-delete-text-${dataId}`
                                const disciplineDeleteActions = `discipline-delete-actions-${dataId}`
                                // Destrói o tour atual
                                await driverObj.destroy();
                                // Cria um novo tour com os passos atualizados
                                const secondStep = [
                                    {
                                        element: `#${disciplineId}`,
                                        popover: {
                                            description: `<div class="p-4"><p class="text-base">Vamos iniciar o processo de exclusão.</p></div>`,
                                            side: "bottom",
                                            align: "start",
                                            showButtons:['']
                                        },
                                        onHighlighted: async (el) => {
                                            await new Promise((resolve) => setTimeout(resolve, 3000));
                                            el.style.zIndex = "0";
                                            await driverObj.moveNext();
                                        }
                                    },
                                    {
                                        element: `#${tourOperations}`, // Seleciona o elemento com base no ID
                                        popover: {
                                            description: `<div class="p-4"><p class="text-base">Vamos abrir o modal de exlusão0</p></div>`,
                                            side: "bottom",
                                            align: "start",
                                            showButtons:['']
                                        },
                                        onHighlighted: async (el) => {
                                            await new Promise((resolve) => setTimeout(resolve, 3000));
                                            const dropDownClick = document.querySelector(`#${tourOperations}`)
                                            if(!dropDownClick) return;
                                            await dropDownClick.click()
                                            el.style.zIndex = "0";
                                            await driverObj.moveNext();
                                        }
                                    },
                                    {
                                        element: `#discipline-delete-${dataId}`,
                                        popover: {
                                            description: `Vamos abrir a modal de deletar`,
                                            side: "right",
                                            align: "center",
                                            showButtons:['']
                                        },
                                        onHighlighted: async (el) => {
                                            await new Promise((resolve) => setTimeout(resolve, 3000));
                                            el.click(); // Simula o clique no botão de edição
                                            await driverObj.moveNext();
                                        }
                                    },
                                    {
                                        element: `#${readText}`, // Seleciona o elemento com base no ID
                                        popover: {
                                            description: "Leia o texto para continuar",
                                            side: "top",
                                            align: "start",
                                            onPopoverRender: (popover, { config, state }) => {
                                                const actions =  AddButtonActions(popover, "voltar ao inicio")
                                                actions.addEventListener('click', async () => {
                                                    Livewire.dispatch('to-close')
                                                    await driverObj.destroy();
                                                    store.openMai = true;
                                                })
                                            }
                                        },
                                        onHighlighted: async(el) => {
                                            el.classList.add('driver-active-element')
                                        }
                                    },
                                    
                                    {
                                        element: `#${disciplineDeleteActions}`, // Seleciona o elemento com base no ID
                                        popover: {
                                            description: "Clique em 'Cancelar' para desistir ou 'Deltar' para finalizar",
                                            side: "top",
                                            align: "start",
                                            onPopoverRender: (popover, { config, state }) => {
                                                const actions =  AddButtonActions(popover, "voltar  ao inicio")
                                                actions.addEventListener('click', async () => {
                                                    Livewire.dispatch('to-close')
                                                    await driverObj.destroy();
                                                    store.openMai = true;
                                                })
                                            }
                                        },
                                        onHighlighted: async(el) => {
                                            const cancelButton = el.querySelector(`#discipline-delete-cancel-${dataId}`)
                                            const save = el.querySelector(`#discipline-delete-send-${dataId}`)
                                            const close =  async () => {
                                                await driverObj.destroy();
                                                store.openMai = true;
                                            }
                                            cancelButton.addEventListener('click', close)
                                            save.addEventListener('click', close)
                                        }
                                    }
                                ];

                                // Cria um novo tour com os passos atualizados
                                const updatedDriverObj = drivertour({
                                    ...configDriver,
                                    steps: secondStep,
                                });

                                // Inicia o novo tour
                                await updatedDriverObj.drive();
                            });
                        });
                    },
                }
            ],
        });
    };
    const driverObj = createInitialTour();
    return await driverObj.drive();
}