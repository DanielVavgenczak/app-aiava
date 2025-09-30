import { AddButtonActions, configDriver } from "./utils";
const store =  Alpine.store("tourguide");

export async function StartTourCreateDiscipline() {
    store.openMai = false;
    const newDisciplineButton = document.querySelector("button#discipline-create");
    if(!newDisciplineButton.id){
        return;
    }
    const driverObj = drivertour({
        ...configDriver,
        steps: [
            {
                element: `#${newDisciplineButton.id}`,
                popover: {
                    description: `Vou clicar no botão "+ Disciplina"`,
                    showButtons: [""],
                },
                onHighlighted: async (el) => {
                    await new Promise((resolve) => setTimeout(resolve, 3000));
                    if (!newDisciplineButton) {
                        return;
                    }
                    newDisciplineButton.click();
                    await new Promise((resolve) => setTimeout(resolve, 1000));
                    await driverObj.moveNext();
                },
            },
            {
                element: `#discipline-input-title`,
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
                element: `#discipline-save`,
                popover: {
                    description: `Clique em 'Cadastrar' para salvar ou 'Cancelar' para cancelar o cadastro.`,
                    onPopoverRender: (popover, { config, state }) => {
                        const actions = AddButtonActions(
                            popover,
                            "Voltar ao início",
                        );
                        actions.addEventListener("click", async() => {
                            Livewire.dispatch("close-create");
                            await driverObj.destroy();
                            store.openMai = true;
                        });
                    },
                },
                onHighlighted: async (el) => {
                    const btnCancel = el.querySelector("#create-cancel");
                    const btnCreate = el.querySelector("#create-confirm");
                    let disciplineIdToManager = null;
                    const toCloseActions = async () => {
                        Livewire.dispatch("close-create");
                        await driverObj.destroy();
                        store.openMai = true;
                    };
                    if (!btnCancel || !btnCreate) {
                        return;
                    }
                    btnCancel.addEventListener("click", toCloseActions);
                    /**
                     * Esse codigo aqui é apenas para o video do de apresentção
                     * depois devemos rever as funcionalidades
                     */
                    btnCreate.addEventListener("click", async () =>{
                        await new Promise((resolve) => setTimeout(resolve, 1000));
                        await Livewire.dispatch("close-create");
                        store.openMai = false;
                        Livewire.on('send-id-discipline', async (event) => {
                            disciplineIdToManager = event.discipline_id
                            await driverObj.destroy();
                            await new Promise((resolve) => setTimeout(resolve, 2000))
                            const continueStepe = [
                                {
                                    element: `#discipline-${disciplineIdToManager}`,
                                    popover: {
                                        description: `Agora vamos adiconar conteudo a esse disciplina`,
                                        side: "bottom",
                                        align: "start",
                                        showButtons:['']
                                    },
                                    onHighlighted: async (el) => {
                                        await new Promise((resolve) => setTimeout(resolve, 3000));
                                        await driverObj.moveNext();
                                    }
                                },
                                {
                                    element: `#discipline-manager-content-${disciplineIdToManager}`,
                                    popover: {
                                        description: `Vou clicar aqui para gerenciar a disciplina`,
                                        side: "bottom",
                                        align: "start",
                                        showButtons:['']
                                    },
                                    onHighlighted: async (el) => {
                                        await new Promise((resolve) => setTimeout(resolve, 1000));
                                        el.click();
                                        await updatedDriverObj.destroy()
                                        store.continueTour = true;
                                        Livewire.dispatch('continue-created-content', {continue:true});
                                    }
                                },
                            ];
                            const updatedDriverObj = drivertour({
                                ...configDriver,
                                steps: continueStepe,
                            });
                            await updatedDriverObj.drive();
                        });
                    });
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
                        description: `Selecione a disciplina que deseja editar`,
                        onPopoverRender: (popover, { config, state }) => {
                            const actions = AddButtonActions(popover, "Voltar ao início");
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
                                const disciplineFormActions = `discipline-edit-form-actions-${dataId}`
                                // Destrói o tour atual
                                await driverObj.destroy();
                                // Cria um novo tour com os passos atualizados
                                const secondStep = [
                                    {
                                        element: `#${disciplineId}`,
                                        popover: {
                                            description: `Vamos iniciar o processo de edição.`,
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
                                            description: `Vamos abrir o formulário de edição`,
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
                                        element: `.tour-discipline-edit-${dataId}`,
                                        popover: {
                                            description: `Vou abrir o formulário para editar a disciplina.`,
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
                                            description: "Altere o titulo para o qual deseja ou clique em Proximo",
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
                                        element: `#${disciplineFormActions}`,
                                        popover: {
                                            description: "Clique em 'Cancelar' para desistir ou 'Editar' para salvar",
                                            side: "top",
                                            align: "start",
                                            onPopoverRender: (popover, { config, state }) => {
                                                const actions =  AddButtonActions(popover, "Voltar ao início")
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
                        description: `Selecione a disciplina que deseja Deletar`,
                        onPopoverRender: (popover, { config, state }) => {
                            const actions = AddButtonActions(popover, "Voltar ao inicio");
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
                                const tourOperations = `#tour-operations-${dataId}`
                                const readText = `discipline-delete-text-${dataId}`
                                const disciplineDeleteActions = `discipline-delete-actions-${dataId}`
                                // Destrói o tour atual
                                await driverObj.destroy();
                                // Cria um novo tour com os passos atualizados
                                const secondStep = [
                                    {
                                        element: `#${disciplineId}`,
                                        popover: {
                                            description: `Vou iniciar o processo de exclusão.`,
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
                                        element: `${tourOperations}`, // Seleciona o elemento com base no ID
                                        popover: {
                                            description: `Vou abrir o modal de exclusão`,
                                            side: "bottom",
                                            align: "start",
                                            showButtons:['']
                                        },
                                        onHighlighted: async (el) => {
                                            await new Promise((resolve) => setTimeout(resolve, 3000));
                                            const dropDownClick = document.querySelector(`${tourOperations}`)
                                            if(!dropDownClick) return;
                                            await dropDownClick.click()
                                            el.style.zIndex = "0";
                                            await driverObj.moveNext();
                                        }
                                    },
                                    {
                                        element: `.tour-discipline-delete-${dataId}`,
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
                                            description: "Tem certeza que deseja deletar essa disciplina?",
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
    const inputSearch = document.querySelector("#discipline-input-search");
    if(!inputSearch){
        console.error("Input de pesquisa não encontrado");
        return;
    }
     const createInitialTour = () => {
        return drivertour({
            ...configDriver,
            steps: [
                {
                    element: `#discipline-input-search`,
                    popover: {
                        description: `Digite a disciplina que deseja filtrar`,
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
                },
                {
                    element:"#discipline-card",
                    popover:{
                        description: `A disciplina que você procura deve aparecer aqui`,
                    },
                    onHighlighted: async (el) => {
                        const noDiscipline = document.querySelector("#disciplines-empty");
                        if(noDiscipline){
                           alert("Nenhuma disciplina encontrada");
                           await driverObj.destroy();
                           store.openMai = true;
                           return;
                        }
                    }

                }
            ],
        });
    };
    const driverObj = createInitialTour();
    return await driverObj.drive();
}
export async function StartTourManagerDisciplineContent(){
    let store = Alpine.store("tourguide");
    store.openMai = false;
    const createInitialTour = () => {
        return drivertour({
            ...configDriver,
            steps: [
                {
                    element: `#disciplines-all`,
                    popover: {
                        description: `Selecione a disciplina que deseja gerenciar`,
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
                                // Destrói o tour atual
                                await driverObj.destroy();
                                // Cria um novo tour com os passos atualizados
                                const secondStep = [
                                    {
                                        element: `#${disciplineId}`,
                                        popover: {
                                            description: `Inicar opções de gerenciamento`,
                                            side: "bottom",
                                            align: "start",
                                            showButtons:['']
                                        },
                                        onHighlighted: async (el) => {
                                            await new Promise((resolve) => setTimeout(resolve, 3000));
                                            el.style.zIndex = "0"
                                            await driverObj.moveNext();
                                        }
                                    },
                                    {
                                        element: `#discipline-manager-content-${dataId}`,
                                        popover: {
                                            description: `Clicar no titulo da disciplina`,
                                            side: "bottom",
                                            align: "start",
                                            showButtons:['']
                                        },
                                        onHighlighted: async (el) => {
                                            await new Promise((resolve) => setTimeout(resolve, 3000));
                                            el.click();
                                            await store.initTour()
                                            await updatedDriverObj.destroy()
                                        }
                                    },
                                ];
                                // // Cria um novo tour com os passos atualizados
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
