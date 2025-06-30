import { AddButtonActions, configDriver
 } from "./utils";

export async function StartTourClasseCreate() {
    let store = Alpine.store("tourguide");
    store.openMai = false;
    return new Promise(async (resolve) => {
        const btnClasseCreate = document.querySelector("button#classe-create");
        if(!btnClasseCreate) return;

        const driverObj = drivertour({
            ...configDriver,
            steps: [
                {
                    element: `#${btnClasseCreate.id}`,
                    popover: {
                        description: `Vou clicar no botão "+ Turma"`,
                        showButtons: [""],
                    },
                    onHighlighted: async (el) => {
                        await new Promise((resolve) => setTimeout(resolve, 3000));
                        if (!btnClasseCreate) {
                            return;
                        }
                        btnClasseCreate.click();
                        await new Promise((resolve) => setTimeout(resolve, 1000));
                        await driverObj.moveNext();
                    },
                },
                {
                    element: `#classe-input-discipline`,
                    popover: {
                        description: "Selecione a disciplina",
                        onPopoverRender: (popover, { config, state }) => {
                            const actions = AddButtonActions(
                                popover,
                                "voltar ao inicio",
                            );
                            actions.addEventListener("click", async() => {
                                Livewire.dispatch("classe-create-cancel");
                                await driverObj.destroy();
                                store.openMai = true;
                            });
                        },
                    },
                },
                {
                    element: `#classe-input-name`,
                    popover: {
                        description: "Adicione o nome da turma",
                        onPopoverRender: (popover, { config, state }) => {
                            const actions = AddButtonActions(
                                popover,
                                "voltar ao inicio",
                            );
                            actions.addEventListener("click", () => {
                                Livewire.dispatch("classe-create-cancel");
                                driverObj.destroy();
                                store.openMai = true;
                            });
                        },
                    },
                },
                {
                    element: `#classe-input-actions`,
                    popover: {
                        description: "Escolher Cancelar ou Cadastrar",
                        onPopoverRender: (popover, { config, state }) => {
                            const actions = AddButtonActions(
                                popover,
                                "voltar ao inicio",
                            );
                            actions.addEventListener("click", async() => {
                                Livewire.dispatch("classe-create-cancel");
                                await driverObj.destroy();
                                store.openMai = true;
                            });
                        },
                    },
                    onHighlighted:async(el) => {
                        const cancel = el.querySelector("#classe-input-cancel");
                        const save = el.querySelector("#classe-input-save");
                        if (!cancel && !save) {
                            return;
                        }
                        cancel.addEventListener("click",async() => {
                            Livewire.dispatch("classe-create-cancel");
                            await driverObj.destroy();
                            store.openMai = true;
                        });

                        save.addEventListener("click", async () => {
                            await driverObj.destroy();
                            store.openMai = true;
                        });
                    },
                },
            ]
        })
        return await driverObj.drive();
    })
}
export async function StartTourClasseDelete(){
    let store = Alpine.store("tourguide");
    store.openMai = false;
    return new Promise(async (resolve) => {
        const classList = document.querySelector("#classe-list");
        if(!classList) {
            return
        }

        const driverObj = drivertour({
            ...configDriver,
            steps: [
                {
                    element: `#${classList.id}`,
                    popover: {
                        description: `Escolha a classe que deseja excluir`,
                        showButtons: [""],
                    },
                    onHighlighted: async (el) => {
                        const classeCount = el.querySelectorAll("div.card-classe")
                        let classeLected = null
                        if (!classeCount || classeCount.length === 0) {
                            return;
                        }
                        classeCount.forEach((classe) => {
                            classe.style.zIndex = "200000000";
                            classe.addEventListener('click', async (event) => {
                                if (classeLected) return;
                                event.preventDefault();
                                classeLected = true;
                                // Remove z-index das disciplinas não selecionadas
                                classeCount.forEach((otherClasse) => {
                                    if (classe !== otherClasse) {
                                        otherClasse.style.zIndex = "0";
                                    }
                                });
                                const dataId = classe.getAttribute('data-tour');
                                await driverObj.destroy();
                                const secondStep = [
                                    {
                                        element: `#classe-${dataId}`,
                                        popover: {
                                            description: `Vou deletar essa turma`,
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
                                        element: `#classe-delete-${dataId}`, // Seleciona o elemento com base no ID
                                        popover: {
                                            description: `Vou abrir o modal de exclusão`,
                                            side: "bottom",
                                            align: "start",
                                            showButtons:['']
                                        },
                                        onHighlighted: async (el) => {
                                            await new Promise((resolve) => setTimeout(resolve, 3000));
                                            const btnDelete = document.querySelector(`#classe-delete-${dataId}`)
                                            if(!btnDelete) return;
                                            await btnDelete.click()
                                            el.style.zIndex = "0";
                                            await driverObj.moveNext();
                                        }
                                    },
                                    {
                                        element: `#classe-delete-${dataId}`,
                                        popover: {
                                            description: `Vamos abrir a modal de deletar`,
                                            side: "right",
                                            align: "center",
                                            showButtons:['']
                                        },
                                        onHighlighted: async (el) => {
                                            await new Promise((resolve) => setTimeout(resolve, 2000));
                                            el.click(); // Simula o clique no botão de edição
                                            await driverObj.moveNext();
                                        }
                                    },
                                    {
                                        element: `#classe-delete-text-${dataId}`, // Seleciona o elemento com base no ID
                                        popover: {
                                            description: "Tem certeza que deseja deletar essa turma?",
                                            side: "top",
                                            align: "start",
                                            onPopoverRender: (popover, { config, state }) => {
                                                const actions =  AddButtonActions(popover, "voltar ao inicio")
                                                actions.addEventListener('click', async () => {
                                                    Livewire.dispatch("classe-delete-cancel");
                                                    await driverObj.destroy();
                                                    store.openMai = true;
                                                })
                                            }
                                        },
                                    },
                                    {
                                        element: `#classe-delete-actions-${dataId}`, // Seleciona o elemento com base no ID
                                        popover: {
                                            description: "Clique em 'Cancelar' para desistir ou 'Deltar' para finalizar",
                                            onPopoverRender: (popover, { config, state }) => {
                                                const actions =  AddButtonActions(popover, "voltar ao inicio")
                                                actions.addEventListener('click', async () => {
                                                    Livewire.dispatch("classe-delete-cancel");
                                                    await driverObj.destroy();
                                                    store.openMai = true;
                                                })
                                            }
                                        },
                                        onHighlighted: async(el) => {
                                            await new Promise((resolve) => setTimeout(resolve, 2000));
                                            const btnCancel = el.querySelector(`#classe-delete-cancel-${dataId}`)
                                            const btnDelete = el.querySelector(`#classe-delete-confirm-${dataId}`)
                                            if(!btnCancel || !btnDelete) return;
                                            btnCancel.addEventListener('click', async () => {
                                                Livewire.dispatch("classe-delete-cancel");
                                                await driverObj.destroy();
                                                store.openMai = true;
                                            })
                                            btnDelete.addEventListener('click', async () => {
                                                //Livewire.dispatch("classe-delete-cancel");
                                                driverObj.destroy();
                                                store.openMai = true;
                                            })
                                        }
                                    },
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
                    }
                }
            ]
        })
        return await driverObj.drive();
    })
}

export async function StartTourClasseEdit()
{
    let store = Alpine.store("tourguide");
    store.openMai = false;
   return new Promise(async (resolve) => {
        const classList = document.querySelector("#classe-list");
        if(!classList) {
            return
        }

        const driverObj = drivertour({
            ...configDriver,
            steps: [
                {
                    element: `#${classList.id}`,
                    popover: {
                        description: `Escolha a classe que deseja editar`,
                        showButtons: [""],
                    },
                    onHighlighted: async (el) => {
                        const classeCount = el.querySelectorAll("div.card-classe")
                        let classeLected = null
                        if (!classeCount || classeCount.length === 0) {
                            return;
                        }
                        classeCount.forEach((classe) => {
                            classe.style.zIndex = "200000000";
                            classe.addEventListener('click', async (event) => {
                                if (classeLected) return;
                                event.preventDefault();
                                classeLected = true;
                                // Remove z-index das disciplinas não selecionadas
                                classeCount.forEach((otherClasse) => {
                                    if (classe !== otherClasse) {
                                        otherClasse.style.zIndex = "0";
                                    }
                                });
                                const dataId = classe.getAttribute('data-tour');
                                await driverObj.destroy();
                                const secondStep = [
                                    {
                                        element: `#classe-${dataId}`,
                                        popover: {
                                            description: `Vamos editar essa turma`,
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
                                        element: `#classe-edit-${dataId}`, // Seleciona o elemento com base no ID
                                        popover: {
                                            description: `Vou abrir o modal de edição`,
                                            side: "bottom",
                                            align: "start",
                                            showButtons:['']
                                        },
                                        onHighlighted: async (el) => {
                                            await new Promise((resolve) => setTimeout(resolve, 3000));
                                            const btnEdit = document.querySelector(`#classe-edit-${dataId}`)
                                            if(!btnEdit) return;
                                            await btnEdit.click()
                                            el.style.zIndex = "0";
                                            await driverObj.moveNext();
                                        }
                                    },
                                    {
                                        element: `#classe-edit-form-${dataId}`,
                                        popover: {
                                            description: `Veja as informações que deseja alterar`,
                                            side: "left",
                                            align: "start",
                                            //showButtons:['']
                                        },

                                    },
                                    {
                                        element: `#classe-input-discipline-${dataId}`, // Seleciona o elemento com base no ID
                                        popover: {
                                            description: "Alterar disciplina da turma",
                                            side: "top",
                                            align: "start",
                                            onPopoverRender: (popover, { config, state }) => {
                                                const actions =  AddButtonActions(popover, "voltar ao inicio")
                                                actions.addEventListener('click', async () => {
                                                    Livewire.dispatch("classe-edit-cancel");
                                                    await driverObj.destroy();
                                                    store.openMai = true;
                                                })
                                            }
                                        },
                                    },
                                     {
                                        element: `#classe-input-name-${dataId}`, // Seleciona o elemento com base no ID
                                        popover: {
                                            description: "Alterar o nome da turma",
                                            side: "top",
                                            align: "start",
                                            onPopoverRender: (popover, { config, state }) => {
                                                const actions =  AddButtonActions(popover, "voltar ao inicio")
                                                actions.addEventListener('click', async () => {
                                                    Livewire.dispatch("classe-edit-cancel");
                                                    await driverObj.destroy();
                                                    store.openMai = true;
                                                })
                                            }
                                        },
                                    },
                                    {
                                        element: `#classe-edit-actions-${dataId}`, // Seleciona o elemento com base no ID
                                        popover: {
                                            description: "Clique em 'Cancelar' para cancelar Ou 'Editar' para savar as alterações",
                                            onPopoverRender: (popover, { config, state }) => {
                                                const actions =  AddButtonActions(popover, "voltar ao inicio")
                                                actions.addEventListener('click', async () => {
                                                    //Livewire.dispatch("classe-delete-cancel");
                                                    await driverObj.destroy();
                                                    store.openMai = true;
                                                })
                                            }
                                        },
                                        onHighlighted: async(el) => {
                                            await new Promise((resolve) => setTimeout(resolve, 2000));
                                            const btnCancel = el.querySelector(`#classe-edit-cancel`)
                                            const btnConfirm = el.querySelector(`#classe-edit-confirm`)
                                            if(!btnCancel || !btnConfirm) return;
                                            btnCancel.addEventListener('click', async () => {
                                                Livewire.dispatch("classe-edit-cancel");
                                                await driverObj.destroy();
                                                store.openMai = true;
                                            })
                                            btnConfirm.addEventListener('click', async () => {
                                                Livewire.dispatch("classe-edit-cancel");
                                                driverObj.destroy();
                                                store.openMai = true;
                                            })
                                        }
                                    },
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
                    }
                }
            ]
        })
        return await driverObj.drive();
    })
}


export async function StartTourClasseAccess()
{
    let store = Alpine.store("tourguide");
    store.openMai = false;
   return new Promise(async (resolve) => {
        const classList = document.querySelector("#classe-list");
        if(!classList) {
            return
        }

        const driverObj = drivertour({
            ...configDriver,
            steps: [
                {
                    element: `#${classList.id}`,
                    popover: {
                        description: `Escolha a Turma que deseja acessar`,
                        showButtons: [""],
                    },
                    onHighlighted: async (el) => {
                        const classeCount = el.querySelectorAll("div.card-classe")
                        let classeLected = null
                        if (!classeCount || classeCount.length === 0) {
                            return;
                        }
                        classeCount.forEach((classe) => {
                            classe.style.zIndex = "200000000";
                            classe.addEventListener('click', async (event) => {
                                if (classeLected) return;
                                event.preventDefault();
                                classeLected = true;
                                // Remove z-index das disciplinas não selecionadas
                                classeCount.forEach((otherClasse) => {
                                    if (classe !== otherClasse) {
                                        otherClasse.style.zIndex = "0";
                                    }
                                });
                                const dataId = classe.getAttribute('data-tour');
                                await driverObj.destroy();
                                const secondStep = [
                                    {
                                        element: `#classe-${dataId}`,
                                        popover: {
                                            description: `Vamos acessar essa turma`,
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
                                        element: `#classe-access-${dataId}`, // Seleciona o elemento com base no ID
                                        popover: {
                                            description: `Vou redirecionar para a turma selecionada`,
                                            side: "bottom",
                                            align: "start",
                                            showButtons:['']
                                        },
                                        onHighlighted: async (el) => {
                                            await new Promise((resolve) => setTimeout(resolve, 3000));
                                            const btnAccess = document.querySelector(`#classe-access-${dataId}`)
                                            if(!btnAccess) return;
                                            await btnAccess.click()
                                            el.style.zIndex = "0";
                                            await driverObj.moveNext();
                                        }
                                    },
                                ]

                                // Cria um novo tour com os passos atualizados
                                const updatedDriverObj = drivertour({
                                    ...configDriver,
                                    steps: secondStep,
                                });

                                // Inicia o novo tour
                                await updatedDriverObj.drive();
                            });
                        });
                    }
                }
            ]
        })
        return await driverObj.drive();
    })
}

