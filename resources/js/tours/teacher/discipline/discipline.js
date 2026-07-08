import { AddButtonActions, configDriver } from "../../utils";
const store =  Alpine.store("tourguide");

export async function StartTourCreateDiscipline() {
    store.openMai = false;
    const newDisciplineButton = document.querySelector("button#discipline-create");
    if(!newDisciplineButton){
        return;
    }
    const driverObj = drivertour({
        ...configDriver,
        steps: [
            {
                element: `#${newDisciplineButton.id}`,
                popover: {
                    // description: `Vou clicar no botão "+ Disciplina"`,
                    description: `Interagindo com a ferramenta...`,
                    showButtons: [""],
                },
                onHighlighted: async (el) => {
                    await new Promise((resolve) => setTimeout(resolve, 1000));
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
                    description: "Digite o nome da disciplina, e clique em 'Registrar'",
                    onPopoverRender: (popover, { config, state }) => {
                        // refatorar
                        const nextBtn = popover.nextButton;
                        const prevBtn = popover.previousButton;
                        if (nextBtn) nextBtn.remove();  // remove do DOM
                        if (prevBtn) prevBtn.remove();
                        const actions = AddButtonActions(
                            popover,
                            "Cancelar operação",
                        );
                        actions.addEventListener("click", async () => {
                            Livewire.dispatch("close-create-with-tour");
                            await driverObj.destroy();
                            store.openMai = true;
                        });
                    },
                },
                onHighlighted: async (el) => {
                    await new Promise((resolve) => setTimeout(resolve, 500));
                    const btnCancel = el.querySelector("#create-cancel");
                    const btnCreate = el.querySelector("#create-confirm");
                    let disciplineIdToManager = null;
                    if (!btnCancel || !btnCreate) {
                        return;
                    }
                    btnCancel.addEventListener("click", async () => {
                        await Livewire.dispatch("discipline-close-modal-create");
                        await driverObj.destroy();
                        store.openMai = true;
                    });

                    btnCreate.addEventListener("click", async () => {
                        await new Promise((resolve) => setTimeout(resolve, 500));
                        const errorTitle = document.querySelector("#title_discipline_error");
                        await new Promise((resolve) => setTimeout(resolve, 500));
                        /** Resolução temporaria até recriarmos */
                        if(errorTitle && errorTitle.textContent.trim() !== ""){
                            alert("Erro: Você ja possue uma disciplina com esse titulo.");
                            return;
                        }
                        await new Promise((resolve) => setTimeout(resolve, 500));
                        /** para fechar o model  */
                        Livewire.dispatch("discipline-close-modal-create");
                        await driverObj.destroy();
                        store.openMai = false;

                        await new Promise((resolve) => setTimeout(resolve, 1500));

                        const disciplinesAfterCount = document.querySelectorAll("div.discipline-card");
                        const lastRegisted = disciplinesAfterCount[0];
                        if(!lastRegisted) return;
                        const dataId = lastRegisted.getAttribute("data-tour");

                        // Cria uma Promise que resolve quando o evento for recebido
                        disciplineIdToManager = dataId;

                        await new Promise((resolve) => setTimeout(resolve, 1500));

                        let updatedDriverObj;
                        const continueStepe = [
                            {
                                element: `#discipline-${disciplineIdToManager}`,
                                popover: {
                                    description: `Agora vamos adicionar conteúdo a essa disciplina`,
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
                                    description: `Interagindo com a ferramenta...`,
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
                        updatedDriverObj = drivertour({
                            ...configDriver,
                            steps: continueStepe,
                        });
                        await updatedDriverObj.drive();

                        Livewire.on('discipline-send-id-tour', async (event) => {
                            disciplineIdToManager = event.discipline_id;
                            store.disciplineId = event.discipline_id;
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
    store.openMai = false;
     const createInitialTour = () => {

        /** identificação de contexto */
        const disciplinesCount = document.querySelectorAll("div.discipline-card");
        return drivertour({
            ...configDriver,
            steps: [
                {
                    element: `#disciplines-all`,
                    popover: {
                        description: disciplinesCount.length === 1 ? `Interagindo com a ferramenta` : `Selecione a disciplina que deseja editar`,
                        //showButtons: [''],
                        onPopoverRender: (popover, { config, state }) => {
                            popover.previousButton?.remove();
                            popover.nextButton?.remove();
                            const actions = AddButtonActions(popover, "Cancelar operação");
                            actions.addEventListener('click', async () => {
                                driverObj.destroy();
                                //const removeIndex = document.querySelectorAll("div.discipline-card");
                                //removeIndex.forEach((item) => item.style.zIndex = "0");
                                store.openMai = true;
                            });
                        }
                    },
                    onHighlighted: async (el) => {
                        const disciplines = el.querySelectorAll("div.discipline-card");
                        let disciplineSelected = false;
                        if (!disciplines || disciplines.length === 0) return;

                        const startEditFlow = async (discipline) => {
                            const disciplineId = discipline.getAttribute('id');
                            const dataId = discipline.getAttribute('data-tour');
                            const tourOperations = `tour-operations-${dataId}`
                            const disciplineFormActions = `discipline-edit-form-actions-${dataId}`

                            await driverObj.destroy();

                            const secondStep = [
                                {
                                    element: `#${disciplineId}`,
                                    popover: {
                                        description: `Interagindo com a ferramenta....`,
                                        side: "bottom",
                                        align: "start",
                                        showButtons:['']
                                    },
                                    onHighlighted: async (el) => {
                                        document.activeElement?.blur();
                                        await new Promise((resolve) => setTimeout(resolve, 3000));
                                        el.style.zIndex = "0";
                                        await driverObj.moveNext();
                                    }
                                },
                                {
                                    element: `#${tourOperations}`,
                                    popover: {
                                        description: `Interagindo com a ferramenta....`,
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
                                        description: `Interagindo com a ferramenta....`,
                                        side: "right",
                                        align: "center",
                                        showButtons:['']
                                    },
                                    onHighlighted: async (el) => {
                                        await new Promise((resolve) => setTimeout(resolve, 3000));
                                        el.click();
                                        const pollStart = Date.now();
                                        while (!document.querySelector('dialog.modal[open]') && Date.now() - pollStart < 5000) {
                                            await new Promise(r => setTimeout(r, 100));
                                        }
                                        await driverObj.moveNext();
                                    }
                                },
                                {
                                    element: `#${disciplineFormActions}`,
                                    popover: {
                                        description: "Caso queira edite o titulo da disciplina, e clique em 'Editar' para salvar ou 'Cancelar' para desistir",
                                        side: "top",
                                        align: "start",
                                        onPopoverRender: (popover, { config, state }) => {
                                            popover.previousButton?.remove();
                                            popover.nextButton?.remove();
                                            const actions =  AddButtonActions(popover, "Cancelar operação")
                                            actions.addEventListener('click', async () => {
                                                Livewire.dispatch('to-close')
                                                await driverObj.destroy();
                                                store.openMai = false;
                                            })
                                        }
                                    },
                                    onHighlighted: async(el) => {
                                        await new Promise(resolve => setTimeout(resolve, 500));
                                        el.querySelector('input')?.focus();
                                        const cancelButton = el.querySelector(`#discipline-edit-cancel-${dataId}`)
                                        const save = el.querySelector(`#discipline-edit-form-send-${dataId}`)
                                        const close =  async () => {
                                            await driverObj.destroy();
                                            store.openMai = false;
                                        }
                                        cancelButton.addEventListener('click', close)
                                        save.addEventListener('click', close)
                                    }
                                }
                            ];

                            const updatedDriverObj = drivertour({
                                ...configDriver,
                                steps: secondStep,
                            });

                            await updatedDriverObj.drive();
                        };

                        if(disciplines.length === 1){
                            await startEditFlow(disciplines[0]);
                            return;
                        }

                        disciplines.forEach((discipline) => {
                            discipline.style.zIndex = "200000000";
                            discipline.addEventListener('click', async (event) => {
                                if (disciplineSelected) return;
                                event.preventDefault();
                                disciplineSelected = true;
                                disciplines.forEach((otherDiscipline) => {
                                    if (discipline !== otherDiscipline) {
                                        otherDiscipline.style.zIndex = "0";
                                    }
                                });

                                await startEditFlow(discipline);
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
    store.openMai = false;
    const createInitialTour = () => {
        const disciplinesCount = document.querySelectorAll("div.discipline-card");
        return drivertour({
            ...configDriver,
            steps: [
                {
                    element: `#disciplines-all`,
                    popover: {
                        description: disciplinesCount.length === 1
                            ? `Interagindo com a ferramenta`
                            : `Selecione a disciplina que deseja deletar`,
                        onPopoverRender: (popover, { config, state }) => {
                            popover.previousButton?.remove();
                            popover.nextButton?.remove();
                            const actions = AddButtonActions(popover, "Cancelar operação");
                            actions.addEventListener('click', async () => {
                                driverObj.destroy();
                                const removeIndex = document.querySelectorAll("div.discipline-card");
                                removeIndex.forEach((item) => item.style.zIndex = "0");
                                store.openMai = true;
                            });
                        }
                    },
                    onHighlighted: async (el) => {
                        const disciplines = el.querySelectorAll("div.discipline-card");
                        let disciplineSelected = false;
                        if (!disciplines || disciplines.length === 0) return;

                        const startDeleteFlow = async (discipline) => {
                            const disciplineId = discipline.getAttribute('id');
                            const dataId = discipline.getAttribute('data-tour');
                            const tourOperations = `#tour-operations-${dataId}`
                            const readText = `discipline-delete-text-${dataId}`
                            const disciplineDeleteActions = `discipline-delete-actions-${dataId}`

                            await driverObj.destroy();

                            const secondStep = [
                                {
                                    element: `#${disciplineId}`,
                                    popover: {
                                        description: `Interagindo com a ferramenta....`,
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
                                    element: `${tourOperations}`,
                                    popover: {
                                        description: `Interagindo com a ferramenta.... `,
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
                                        description: `Interagindo com a ferramenta...  `,
                                        side: "right",
                                        align: "center",
                                        showButtons:['']
                                    },
                                    onHighlighted: async (el) => {
                                        await new Promise((resolve) => setTimeout(resolve, 3000));
                                        el.click();
                                        const pollStart = Date.now();
                                        while (!document.querySelector('dialog.modal[open]') && Date.now() - pollStart < 5000) {
                                            await new Promise(r => setTimeout(r, 100));
                                        }
                                        await driverObj.moveNext();
                                    }
                                },
                                {
                                    element: `#${readText}`,
                                    popover: {
                                        description: "Tem certeza que deseja deletar essa disciplina?",
                                        side: "top",
                                        align: "start",
                                        onPopoverRender: (popover, { config, state }) => {
                                            popover.previousButton?.remove();
                                            popover.nextButton?.remove();
                                            const actions =  AddButtonActions(popover, "Cancelar operação")
                                            actions.addEventListener('click', async () => {
                                                Livewire.dispatch('to-close')
                                                await driverObj.destroy();
                                                store.openMai = true;
                                            })
                                        }
                                    },
                                    onHighlighted: async(el) => {
                                        el.classList.add('driver-active-element')
                                        const cancelButton = el.querySelector(`#discipline-delete-cancel-${dataId}`)
                                        const save = el.querySelector(`#discipline-delete-send-${dataId}`)
                                        const close =  async () => {
                                            await driverObj.destroy();
                                            store.openMai = true;
                                        }
                                        cancelButton.addEventListener('click', close)
                                        save.addEventListener('click', close)
                                    }
                                },

                                // {
                                //     element: `#${disciplineDeleteActions}`,
                                //     popover: {
                                //         description: "Clique em 'Cancelar' para desistir ou 'Deletar' para finalizar",
                                //         side: "top",
                                //         align: "start",
                                //         onPopoverRender: (popover, { config, state }) => {
                                //             const actions =  AddButtonActions(popover, "voltar  ao inicio")
                                //             actions.addEventListener('click', async () => {
                                //                 Livewire.dispatch('to-close')
                                //                 await driverObj.destroy();
                                //                 store.openMai = true;
                                //             })
                                //         }
                                //     },
                                //     onHighlighted: async(el) => {
                                //         const cancelButton = el.querySelector(`#discipline-delete-cancel-${dataId}`)
                                //         const save = el.querySelector(`#discipline-delete-send-${dataId}`)
                                //         const close =  async () => {
                                //             await driverObj.destroy();
                                //             store.openMai = true;
                                //         }
                                //         cancelButton.addEventListener('click', close)
                                //         save.addEventListener('click', close)
                                //     }
                                // }
                            ];

                            const updatedDriverObj = drivertour({
                                ...configDriver,
                                steps: secondStep,
                            });

                            await updatedDriverObj.drive();
                        };

                        if(disciplines.length === 1){
                            await startDeleteFlow(disciplines[0]);
                            return;
                        }

                        disciplines.forEach((discipline) => {
                            discipline.style.zIndex = "200000000";
                            discipline.addEventListener('click', async (event) => {
                                if (disciplineSelected) return;
                                event.preventDefault();
                                disciplineSelected = true;
                                disciplines.forEach((otherDiscipline) => {
                                    if (discipline !== otherDiscipline) {
                                        otherDiscipline.style.zIndex = "0";
                                    }
                                });

                                await startDeleteFlow(discipline);
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
    store.openMai = false;
    const inputSearch = document.querySelector("#discipline-input-search");
    if(!inputSearch){
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
                                const removeIndex = document.querySelectorAll("div.discipline-card");
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
    store.openMai = false;
    const createInitialTour = () => {
        const disciplinesCount = document.querySelectorAll("div.discipline-card");
        return drivertour({
            ...configDriver,
            steps: [
                {
                    element: `#disciplines-all`,
                    popover: {
                        description: disciplinesCount.length === 1
                            ? `Interagindo com a ferramenta`
                            : `Selecione a disciplina que deseja gerenciar`,
                        onPopoverRender: (popover, { config, state }) => {
                            popover.previousButton?.remove();
                            popover.nextButton?.remove();
                            const actions = AddButtonActions(popover, "Cancelar operação");
                            actions.addEventListener('click', async () => {
                                driverObj.destroy();
                                store.openMai = true;
                            });
                        }
                    },
                    onHighlighted: async (el) => {
                        const disciplines = el.querySelectorAll("div.discipline-card");
                        let disciplineSelected = false;
                        if (!disciplines || disciplines.length === 0) return;
                        const startManageFlow = async (discipline) => {
                            const disciplineId = discipline.getAttribute('id');
                            const dataId = discipline.getAttribute('data-tour');
                            let updatedDriverObj;

                            await driverObj.destroy();

                            const secondStep = [
                                {
                                    element: `#${disciplineId}`,
                                    popover: {
                                        description: `Interagindo com a ferramenta....`,
                                        showButtons:['']
                                    },
                                    onHighlighted: async () => {
                                        await new Promise((resolve) => setTimeout(resolve, 2000));
                                        //el.style.zIndex = "0"
                                        await driverObj.moveNext();
                                    }
                                },
                                {
                                    element: `#discipline-manager-content-${dataId}`,
                                    popover: {
                                        description: `Interagindo com a ferramenta...`,
                                        showButtons:['']
                                    },
                                    onHighlighted: async (el) => {
                                        await new Promise((resolve) => setTimeout(resolve, 3000));
                                        const filterCountModules = document.querySelectorAll(`#card-discipline-count-module-${dataId}`);

                                        if (!filterCountModules || filterCountModules.length === 0) return;

                                        const fullText = filterCountModules[0]?.textContent?.trim() || '';
                                        const match = fullText.match(/(\d+)/);
                                        const count = match ? parseInt(match[1], 10) : 0;

                                        if(count === 0){
                                            el.click();
                                            await updatedDriverObj.destroy()
                                            store.continueTour = true;
                                            Livewire.dispatch('continue-created-content', {continue:true});
                                            return;
                                        }

                                        el.click();
                                        await store.initTour()
                                        await updatedDriverObj.destroy()
                                    }
                                },
                            ];
                            updatedDriverObj = drivertour({
                                ...configDriver,
                                steps: secondStep,
                            });

                            await updatedDriverObj.drive();
                        };

                        if(disciplines.length === 1){
                            await startManageFlow(disciplines[0]);
                            return;
                        }

                        disciplines.forEach((discipline) => {
                            console.log("bate aqui?");
                            discipline.style.zIndex = "200000000";
                            discipline.addEventListener('click', async (event) => {
                                if (disciplineSelected) return;
                                event.preventDefault();
                                disciplineSelected = true;
                                disciplines.forEach((otherDiscipline) => {
                                    if (discipline !== otherDiscipline) {
                                        otherDiscipline.style.zIndex = "0";
                                    }
                                });

                                await startManageFlow(discipline);
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
