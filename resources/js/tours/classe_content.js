import { AddButtonActions, configDriver } from "./utils";
export async function StartTourStudentFilter()
{
    let store = Alpine.store("tourguide");
    store.openMai = false;
    return new Promise(async (resolve) => {
        const inputStudentFilter = document.querySelector("#student-filter");
        if(!inputStudentFilter) return;
        const driverObj = drivertour({
            ...configDriver,
            steps: [
                {
                    element: `#student-filter`,
                    popover: {
                        description: `Insira o nome do estudante que deseja encontrar`,
                       //showButtons: [""],
                        onPopoverRender: (popover, { config, state }) => {
                            const actions = AddButtonActions(
                                popover,
                                "voltar ao inicio",
                            );
                            actions.addEventListener("click", () => {
                                driverObj.destroy();
                                store.openMai = true;
                            });
                        },
                    },
                    onHighlighted: async (el) => {
                        await new Promise((resolve) => setTimeout(resolve, 3000));
                        const noResult = document.querySelector(`#student-no-result`);
                        if(noResult) {
                            alert("Nenhum aluno encontrado com esse filtro");
                            driverObj.destroy();
                            store.openMai = true;
                            return;
                        }

                    },
                },

                {
                    element: `#student-list`,
                    popover: {
                        description: "Resultados da busca",
                        onPopoverRender: (popover, { config, state }) => {
                            const actions = AddButtonActions(
                                popover,
                                "voltar ao inicio",
                            );
                            actions.addEventListener("click", () => {
                               //Livewire.dispatch("student-register-cancel");
                                driverObj.destroy();
                                store.openMai = true;
                            });
                        },
                    },
                },
            ]
        })
        return await driverObj.drive();
    })

}

export async function StartTourStudentRegister()
{
    let store = Alpine.store("tourguide");
    store.openMai = false;
    return new Promise(async (resolve) => {
        const btnStudentRegister = document.querySelector("button#student-register");
        if(!btnStudentRegister) return;
        const driverObj = drivertour({
            ...configDriver,
            steps: [
                {
                    element: `#${btnStudentRegister.id}`,
                    popover: {
                        description: `Vou clicar no botão "+ Aluno"`,
                        showButtons: [""],
                    },
                    onHighlighted: async (el) => {
                        await new Promise((resolve) => setTimeout(resolve, 3000));
                        if (!btnStudentRegister) {
                            return;
                        }
                        btnStudentRegister.click();
                        await new Promise((resolve) => setTimeout(resolve, 1000));
                        await driverObj.moveNext();
                    },
                },

                {
                    element: `#student-input-full_name`,
                    popover: {
                        description: "Insira o nome completo do aluno",
                    },
                },
                {
                    element: `#student-input-email`,
                    popover: {
                        description: "Insira o email do aluno",
                    },
                },
                {
                    element: `#student-input-phone`,
                    popover: {
                        description: "Caso queira insira o telefone do aluno",
                    },
                },
                {
                    element: `#student-input-add`,
                    popover: {
                        description: "Clique em '+ adicionar', para adicionar o aluno a lista",
                    },
                    onHighlighted: async (el) => {
                        await new Promise((resolve) => setTimeout(resolve, 3000));
                        await driverObj.moveNext();
                    },
                },
                {
                    element: `#student-input-list`,
                    popover: {
                        description: "Verifique os dados do aluno, caso esteja errado exclua da lista clicando na lixeira",
                        onPopoverRender: (popover, { config, state }) => {
                            const actions = AddButtonActions(
                                popover,
                                "voltar ao inicio",
                            );
                            actions.addEventListener("click", () => {
                                Livewire.dispatch("student-register-cancel");
                                driverObj.destroy();
                                store.openMai = true;
                            });
                        },
                    },
                    onHighlighted:async(el) => {
                        await new Promise((resolve) => setTimeout(resolve, 2000));
                    }
                },
                {
                    element: `#student-input-actions`,
                    popover: {
                        description: "Escolher Cancelar ou Registrar",
                        onPopoverRender: (popover, { config, state }) => {
                            const actions = AddButtonActions(
                                popover,
                                "voltar ao inicio",
                            );
                            actions.addEventListener("click", async() => {
                                Livewire.dispatch("student-register-cancel");
                                await driverObj.destroy();
                                store.openMai = true;
                            });
                        },
                    },
                    onHighlighted:async(el) => {
                        const cancel = el.querySelector("#student-input-cancel");
                        const save = el.querySelector("#student-input-register");
                        if (!cancel && !save) {
                            return;
                        }
                        cancel.addEventListener("click",async(event) => {
                            event.preventDefault();

                            Livewire.dispatch("student-register-cancel");
                            await driverObj.destroy();
                            store.openMai = true;
                        });

                        save.addEventListener("click", async () => {
                            Livewire.dispatch("student-register-cancel");
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

export async function StartTourStudentAccess()
{
    let store = Alpine.store("tourguide");
    store.openMai = false;
   return new Promise(async (resolve) => {
        const studentList = document.querySelector("#student-list");
        if(!studentList) {
            return
        }

        const driverObj = drivertour({
            ...configDriver,
            steps: [
                {
                    element: `#${studentList.id}`,
                    popover: {
                        description: `Escolha o estudante que deseja acessar`,
                        showButtons: [""],
                    },
                    onHighlighted: async (el) => {
                        const studentCount = el.querySelectorAll("tr.card-student")
                        let studentSelected = null
                        if (!studentCount || studentCount.length === 0) {
                            return;
                        }
                        //console.log('Estundatens', studentCount.length)
                        studentCount.forEach((student) => {
                            student.style.zIndex = "200000000";
                            student.addEventListener('click', async (event) => {
                                if (studentSelected) return;
                                event.preventDefault();
                                studentSelected = true;
                                // Remove z-index das disciplinas não selecionadas
                                studentCount.forEach((otherStudent) => {
                                    if (student !== otherStudent) {
                                        otherStudent.style.zIndex = "0";
                                    }
                                });
                                const dataId = student.getAttribute('data-tour');
                                await driverObj.destroy();
                                const secondStep = [
                                    {
                                        element: `#student-${dataId}`,
                                        popover: {
                                            description: `Vamos acessar informações do aluno`,
                                            side: "bottom",
                                            align: "start",
                                            showButtons:['']
                                        },
                                        onHighlighted: async (el) => {
                                            await new Promise((resolve) => setTimeout(resolve, 3000));
                                            el.style.zIndex = "0";
                                            await driverObj.moveNext();
                                            return;
                                        }
                                    },
                                    {
                                        element: `#student-access-${dataId}`, // Seleciona o elemento com base no ID
                                        popover: {
                                            description: `Vamos abrir informações do aluno`,
                                            side: "bottom",
                                            align: "start",
                                            showButtons:['']
                                        },
                                        onHighlighted: async (el) => {
                                            await new Promise((resolve) => setTimeout(resolve, 3000));
                                            const btnAccess = document.querySelector(`#student-access-${dataId}`)
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

export async function StartTourStudentEdit()
{
    let store = Alpine.store("tourguide");
    store.openMai = false;
   return new Promise(async (resolve) => {
        const studentList = document.querySelector("#student-list");
        if(!studentList) {
            return
        }

        const driverObj = drivertour({
            ...configDriver,
            steps: [
                {
                    element: `#${studentList.id}`,
                    popover: {
                        description: `Escolha o estudante que deseja editar`,
                        showButtons: [""],
                    },
                    onHighlighted: async (el) => {
                        const studentCount = el.querySelectorAll("tr.card-student")
                        let studentSelected = null
                        if (!studentCount || studentCount.length === 0) {
                            return;
                        }
                        //console.log('Estundatens', studentCount.length)
                        studentCount.forEach((student) => {
                            student.style.zIndex = "200000000";
                            student.addEventListener('click', async (event) => {
                                if (studentSelected) return;
                                event.preventDefault();
                                studentSelected = true;
                                // Remove z-index das disciplinas não selecionadas
                                studentCount.forEach((otherStudent) => {
                                    if (student !== otherStudent) {
                                        otherStudent.style.zIndex = "0";
                                    }
                                });
                                const dataId = student.getAttribute('data-tour');
                                await new Promise((resolve) => setTimeout(resolve, 1000));
                                await driverObj.destroy();
                                const secondStep = [
                                    {
                                        element: `#student-${dataId}`,
                                        popover: {
                                            description: `Vamos clicar no botão de editar`,
                                            side: "bottom",
                                            align: "start",
                                            showButtons:['']
                                        },
                                        onHighlighted: async (el) => {
                                            await new Promise((resolve) => setTimeout(resolve, 3000));
                                            el.style.zIndex = "0";
                                            await driverObj.moveNext();
                                            //return;
                                        }
                                    },
                                    {
                                        element: `#student-btn-edit-${dataId}`, // Seleciona o elemento com base no ID
                                        popover: {
                                            description: `Vamos abrir modal de editção do aluno`,
                                            side: "bottom",
                                            align: "start",
                                            showButtons:['']
                                        },
                                        onHighlighted: async (el) => {
                                            await new Promise((resolve) => setTimeout(resolve, 3000));
                                            const btnEdit = document.querySelector(`#student-btn-edit-${dataId}`)
                                            if(!btnEdit) return;
                                            await btnEdit.click()
                                            el.style.zIndex = "0";
                                            await new Promise((resolve) => setTimeout(resolve, 1000));
                                            await driverObj.moveNext();

                                        }
                                    },
                                    {
                                        element: `#student-input-full_name-${dataId}`, // Seleciona o elemento com base no ID
                                        popover: {
                                            description: `Caso queira edite o nome do aluno`,
                                            side: "bottom",
                                            align: "start",
                                            //showButtons:['']
                                        },

                                    },
                                    {
                                        element: `#student-input-email-${dataId}`, // Seleciona o elemento com base no ID
                                        popover: {
                                            description: `Caso queira edite o email do aluno`,
                                            side: "bottom",
                                            align: "start",
                                            //showButtons:['']
                                        },

                                    },
                                    {
                                        element: `#student-input-phone-${dataId}`, // Seleciona o elemento com base no ID
                                        popover: {
                                            description: `Caso queira edite o telefone do estudante`,
                                            side: "bottom",
                                            align: "start",
                                            //showButtons:['']
                                        },

                                    },
                                    {
                                        element: `#student-edit-actions-${dataId}`, // Seleciona o elemento com base no ID
                                        popover: {
                                            description: "Clique em 'Cancelar' para cancelar Ou 'Editar' para savar as alterações",
                                            onPopoverRender: (popover, { config, state }) => {
                                                const actions =  AddButtonActions(popover, "voltar ao inicio")
                                                actions.addEventListener('click', async () => {
                                                    Livewire.dispatch("student-edit-cancel");
                                                    await driverObj.destroy();
                                                    store.openMai = true;
                                                })
                                            }
                                        },
                                        onHighlighted: async(el) => {
                                            //await new Promise((resolve) => setTimeout(resolve, 2000));
                                            const btnCancel = el.querySelector(`#student-edit-cancel-${dataId}`)
                                            const btnConfirm = el.querySelector(`#student-edit-confirm-${dataId}`)
                                            console.log('Achados', btnCancel, btnConfirm);
                                            if(!btnCancel || !btnConfirm) return;

                                            await btnCancel.addEventListener('click', async (event) => {
                                                event.preventDefault();
                                                console.log('cliquei nessa merda');
                                                await  handlerCloseModal();
                                            })
                                            await btnConfirm.addEventListener('click', async () => {
                                               await handlerCloseModal()
                                            })

                                            async function handlerCloseModal()
                                            {
                                                Livewire.dispatch("student-edit-cancel");
                                                await updatedDriverObj.destroy();
                                                store.openMai = true;
                                            }
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
export async function StartTourStudentDelete()
{
    let store = Alpine.store("tourguide");
    store.openMai = false;
   return new Promise(async (resolve) => {
        const studentList = document.querySelector("#student-list");
        if(!studentList) {
            return
        }

        const driverObj = drivertour({
            ...configDriver,
            steps: [
                {
                    element: `#${studentList.id}`,
                    popover: {
                        description: `Escolha o estudante que deseja deletar`,
                        showButtons: [""],
                    },
                    onHighlighted: async (el) => {
                        const studentCount = el.querySelectorAll("tr.card-student")
                        let studentSelected = null
                        if (!studentCount || studentCount.length === 0) {
                            return;
                        }
                        //console.log('Estundatens', studentCount.length)
                        studentCount.forEach((student) => {
                            student.style.zIndex = "200000000";
                            student.addEventListener('click', async (event) => {
                                if (studentSelected) return;
                                event.preventDefault();
                                studentSelected = true;
                                // Remove z-index das disciplinas não selecionadas
                                studentCount.forEach((otherStudent) => {
                                    if (student !== otherStudent) {
                                        otherStudent.style.zIndex = "0";
                                    }
                                });
                                const dataId = student.getAttribute('data-tour');
                                await new Promise((resolve) => setTimeout(resolve, 1000));
                                await driverObj.destroy();
                                const secondStep = [
                                    {
                                        element: `#student-${dataId}`,
                                        popover: {
                                            description: `Vamos clicar no botão de deletar`,
                                            side: "bottom",
                                            align: "start",
                                            showButtons:['']
                                        },
                                        onHighlighted: async (el) => {
                                            await new Promise((resolve) => setTimeout(resolve, 3000));
                                            el.style.zIndex = "0";
                                            await driverObj.moveNext();
                                            //return;
                                        }
                                    },
                                    {
                                        element: `#student-btn-delete-${dataId}`, // Seleciona o elemento com base no ID
                                        popover: {
                                            description: `Vamos abrir modal de exclusão do aluno`,
                                            side: "bottom",
                                            align: "start",
                                            showButtons:['']
                                        },
                                        onHighlighted: async (el) => {
                                            await new Promise((resolve) => setTimeout(resolve, 3000));
                                            const btnEdit = document.querySelector(`#student-btn-delete-${dataId}`)
                                            if(!btnEdit) return;
                                            await btnEdit.click()
                                            el.style.zIndex = "0";
                                            await new Promise((resolve) => setTimeout(resolve, 2000));
                                            await driverObj.moveNext();

                                        }
                                    },
                                    {
                                        element: `#student-delete-text-${dataId}`, // Seleciona o elemento com base no ID
                                        popover: {
                                            description: `Clique em 'Cancelar' para cancelar a exclusão ou 'Deletar' para confirmar a exclusão`,
                                            side: "bottom",
                                            align: "start",
                                            //showButtons:['']
                                              onPopoverRender: (popover, { config, state }) => {
                                                const actions =  AddButtonActions(popover, "voltar ao inicio")
                                                actions.addEventListener('click', async () => {
                                                    Livewire.dispatch("student-delete-cancel");
                                                    await driverObj.destroy();
                                                    store.openMai = true;
                                                })
                                            }
                                        },
                                        onHighlighted: async(el) => {
                                            await new Promise((resolve) => setTimeout(resolve, 2000));
                                            const btnCancel = el.querySelector(`#student-delete-cancel-${dataId}`)
                                            const btnConfirm = el.querySelector(`#student-confirm-delete-${dataId}`)
                                            if(!btnCancel || !btnConfirm) return;
                                            btnCancel.addEventListener('click', async () => {
                                                Livewire.dispatch("student-delete-cancel");
                                                await driverObj.destroy();
                                                store.openMai = true;
                                            })
                                            btnConfirm.addEventListener('click', async () => {
                                                Livewire.dispatch("student-delete-cancel");
                                                await driverObj.destroy();
                                                store.openMai = true;

                                            })
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
