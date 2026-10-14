import { ContinueCreateAcessClasseTour } from "./teacher/classe/createAndAccess";
import { ContinueRegisterStudentTour } from "./teacher/classe/RegisterStudent";
import { AddButtonActions, configDriver
 } from "./utils";

export async function StartTourClasseCreate() {
    let store = Alpine.store("tourguide");
    store.openMai = false;
    store.isTourActive = true;
    return new Promise(async (resolve) => {
        const btnClasseCreate = document.querySelector("button#classe-create");
        if(!btnClasseCreate) { store.isTourActive = false; return; }

        const driverObj = drivertour({
            ...configDriver,
            steps: [
                {
                    element: `#${btnClasseCreate.id}`,
                    popover: {
                        description: `Interagindo com a ferramenta...`,
                        showButtons: [""],
                    },
                    onHighlighted: async (el) => {
                        await new Promise((resolve) => setTimeout(resolve, 3000));
                        if (!btnClasseCreate) {
                            return;
                        }
                        btnClasseCreate.click();
                        await new Promise((resolve) => setTimeout(resolve, 2000));
                        await driverObj.moveNext();
                    },
                },
                {
                    element: `#register-classe-inputs`,
                    popover: {
                        description: "Selecione a disciplina, e insira um nome para a turma",
                        //showButtons: [""],
                        onPopoverRender: (popover, { config, state }) => {
                            const nextBtn = popover.nextButton;
                            const prevBtn = popover.previousButton;
                            if (nextBtn) nextBtn.remove();  // remove do DOM
                            if (prevBtn) prevBtn.remove();
                            const actions = AddButtonActions(
                                popover,
                                "Cancelar operação",
                            );
                            actions.addEventListener("click", async() => {
                                Livewire.dispatch("classe-create-cancel");
                                await driverObj.destroy();
                                store.openMai = true;
                                store.isTourActive = false;
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
                            store.isTourActive = false;
                        });

                        save.addEventListener("click", async () => {
                            await driverObj.destroy();
                            store.openMai = false;
                            await new Promise((resolve) => setTimeout(resolve, 2500));
                            let lastInsertedClasse = document.querySelectorAll("div.card-classe");
                            if (!lastInsertedClasse || lastInsertedClasse.length === 0) {
                                store.isTourActive = false;
                                return;
                            }
                            await new Promise((resolve) => setTimeout(resolve, 1000));
                            await ContinueCreateAcessClasseTour(lastInsertedClasse[0].getAttribute('data-tour'));
                            store.isTourActive = false;
                        });
                    },
                }
                // {
                //     element: `#classe-input-name`,
                //     popover: {
                //         description: "Adicione o nome da turma",
                //         onPopoverRender: (popover, { config, state }) => {
                //             const actions = AddButtonActions(
                //                 popover,
                //                 "Cancelar operação",
                //             );
                //             actions.addEventListener("click", () => {
                //                 Livewire.dispatch("classe-create-cancel");
                //                 driverObj.destroy();
                //                 store.openMai = true;
                //             });
                //         },
                //     },
                // },
                // {
                //     element: `#classe-input-actions`,
                //     popover: {
                //         description: "Escolher Cancelar ou Cadastrar",
                //         onPopoverRender: (popover, { config, state }) => {
                //             const actions = AddButtonActions(
                //                 popover,
                //                 "Voltar ao início",
                //             );
                //             actions.addEventListener("click", async() => {
                //                 Livewire.dispatch("classe-create-cancel");
                //                 await driverObj.destroy();
                //                 store.openMai = true;
                //             });
                //         },
                //     },
                //     onHighlighted:async(el) => {
                //         const cancel = el.querySelector("#classe-input-cancel");
                //         const save = el.querySelector("#classe-input-save");
                //         if (!cancel && !save) {
                //             return;
                //         }
                //         cancel.addEventListener("click",async() => {
                //             Livewire.dispatch("classe-create-cancel");
                //             await driverObj.destroy();
                //             store.openMai = true;
                //         });

                //         save.addEventListener("click", async () => {
                //             await driverObj.destroy();
                //             store.openMai = false;
                //             await new Promise((resolve) => setTimeout(resolve, 2500));
                //             let lastInsertedClasse = document.querySelectorAll("div.card-classe");
                //             if (!lastInsertedClasse || lastInsertedClasse.length === 0) {
                //                 return;
                //             }
                //             await new Promise((resolve) => setTimeout(resolve, 1000));
                //             await ContinueCreateAcessClasseTour(lastInsertedClasse[0].getAttribute('data-tour'));
                //         });
                //     },
                // },
            ]
        })
        return await driverObj.drive();
    })
}


export async function StartTourClasseDelete(){
    let store = Alpine.store("tourguide");
    store.openMai = false;
    store.isTourActive = true;
    return new Promise(async (resolve) => {
        const classList = document.querySelector("#classe-list");
        if(!classList) {
            store.isTourActive = false;
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
                        BlockButtons(el);
                        const classeCount = el.querySelectorAll("div.card-classe")
                        let classeLected = null
                        if (!classeCount || classeCount.length === 0) {
                            return;
                        }

                        const startDeleteTour = async (classe) => {
                            if (classeLected) return;
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
                                        description: `Interagindo com a ferramenta...`,
                                        side: "bottom",
                                        align: "start",
                                        showButtons:['']
                                    },
                                    onHighlighted: async (el) => {
                                        await new Promise((resolve) => setTimeout(resolve, 1000));
                                        el.style.zIndex = "0";
                                        await new Promise((resolve) => setTimeout(resolve, 1000));
                                        await driverObj.moveNext();
                                    }
                                },
                                {
                                    element: `#classe-delete-${dataId}`,
                                    popover: {
                                        description: `Interagindo com a ferramenta...`,
                                        side: "right",
                                        align: "center",
                                        showButtons:['']
                                    },
                                    onHighlighted: async (el) => {
                                        await new Promise((resolve) => setTimeout(resolve, 1000));
                                        el.click(); // Simula o clique no botão de edição
                                        await new Promise((resolve) => setTimeout(resolve, 2500));
                                        await driverObj.moveNext();
                                    }
                                },
                                {
                                    element: `#classe-delete-content`, // Seleciona o elemento com base no ID
                                    popover: {
                                        description: "Tem certeza que deseja deletar essa turma? Clique em 'Cancelar' para desistir ou 'Deltar' para finalizar",
                                        onPopoverRender: (popover, { config, state }) => {
                                            popover.nextButton.remove();
                                            popover.previousButton.remove();
                                            const actions =  AddButtonActions(popover, "Cancelar operação")
                                            actions.addEventListener('click', async () => {
                                                Livewire.dispatch("classe-delete-cancel");
                                                await driverObj.destroy();
                                                store.openMai = true;
                                                store.isTourActive = false;
                                            })
                                        }
                                    },
                                    onHighlighted: async(el) => {
                                        const btnCancel = el.querySelector(`#classe-delete-cancel-${dataId}`)
                                        const btnDelete = el.querySelector(`#classe-delete-confirm-${dataId}`)
                                        if(!btnCancel || !btnDelete) return;
                                        btnCancel.addEventListener('click', async () => {
                                            Livewire.dispatch("classe-delete-cancel");
                                            await driverObj.destroy();
                                            store.openMai = true;
                                            store.isTourActive = false;
                                        })
                                        btnDelete.addEventListener('click', async () => {
                                            //Livewire.dispatch("classe-delete-cancel");
                                            driverObj.destroy();
                                            store.openMai = true;
                                            store.isTourActive = false;
                                        })
                                    }
                                },
                                // {
                                //     element: `#classe-delete-actions-${dataId}`, // Seleciona o elemento com base no ID
                                //     popover: {
                                //         description: "Clique em 'Cancelar' para desistir ou 'Deltar' para finalizar",
                                //         onPopoverRender: (popover, { config, state }) => {
                                //             const actions =  AddButtonActions(popover, "voltar ao inicio")
                                //             actions.addEventListener('click', async () => {
                                //                 Livewire.dispatch("classe-delete-cancel");
                                //                 await driverObj.destroy();
                                //                 store.openMai = true;
                                //                 store.isTourActive = false;
                                //             })
                                //         }
                                //     },
                                //     onHighlighted: async(el) => {
                                //         await new Promise((resolve) => setTimeout(resolve, 2000));

                                //     }
                                // },
                            ];

                            // Cria um novo tour com os passos atualizados
                            const updatedDriverObj = drivertour({
                                ...configDriver,
                                steps: secondStep,
                            });

                            // Inicia o novo tour
                            await updatedDriverObj.drive();
                            store.isTourActive = false;
                        };

                        classeCount.forEach((classe) => {
                            classe.style.zIndex = "200000000";
                            classe.addEventListener('click', (event) => {
                                event.preventDefault();
                                startDeleteTour(classe);
                            });
                        });

                        // Quando o professor possui apenas 1 turma, o tour seleciona
                        // e clica automaticamente no botão "Excluir" dessa turma
                        if (classeCount.length === 1) {
                            await new Promise((resolve) => setTimeout(resolve, 2000));
                            await startDeleteTour(classeCount[0]);
                        }
                    }
                }
            ]
        })
        return await driverObj.drive();
    })
}


/** HELPER */
export function BlockButtons(el)
{
    const allButtons = el.querySelectorAll('button, a, [wire\\:click], [x-on\\:click]');
    allButtons.forEach(btn => {
        // Verifica se é o elemento que QUEREMOS permitir
        const isAllowed = btn.matches('.card-classe');
        if (!isAllowed) {
            btn.addEventListener('click', (event) => {
                if (btn.dataset.tourDisabled === 'true') {
                    event.preventDefault();
                    event.stopPropagation();
                }
            });
            btn.style.pointerEvents = 'none';
            //btn.style.opacity = '0.5';
            btn.dataset.tourDisabled = 'true';

            if (btn.tagName === 'A') {
                 btn.dataset.originalHref = btn.getAttribute('href');
                btn.removeAttribute('href');
                btn.removeAttribute('wire:navigate');
            }
        }
    })
}


export async function StartTourClasseEdit()
{
    let store = Alpine.store("tourguide");
    store.openMai = false;
    store.isTourActive = true;
   return new Promise(async (resolve) => {

        const classList = document.querySelectorAll(".card-classe");
        const classContent = document.querySelector("#classe-list-content");
        let text;
        if(!classContent || classList.length === 0) {
            store.isTourActive = false;
            return
        }

        const driverObj = drivertour({
            ...configDriver,
            steps: [
                {
                    element: `#${classContent.id}`,
                    popover: {
                        description: classList.length > 1 ? `Escolha a Turma que deseja editar` : `Interagindo com a ferramenta...`,
                        showButtons: [""],
                    },
                    onHighlighted: async (el) => {
                        BlockButtons(el);

                        const classeCount = el.querySelectorAll(".card-classe")
                        let classeLected = null
                        if (!classeCount || classeCount.length === 0) {
                            return;
                        }

                        const startEditTour = async (classe) => {
                            if (classeLected) return;
                            classeLected = true;
                            // Remove z-index das disciplinas não selecionadas
                            classeCount.forEach((otherClasse) => {
                                if (classe !== otherClasse) {
                                    otherClasse.style.zIndex = "0";
                                }
                            });

                            // Reabilita os botões antes de continuar
                            document.querySelectorAll('[data-tour-disabled="true"]').forEach(btn => {
                                btn.style.pointerEvents = '';
                                btn.style.opacity = '';
                                delete btn.dataset.tourDisabled;
                            });

                            const dataId = classe.getAttribute('data-tour');
                            await driverObj.destroy();
                            const secondStep = [
                                {
                                    element: `#classe-${dataId}`,
                                    popover: {
                                        description: `Interagindo com a ferramenta...`,
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
                                        description: `Interagindo com a ferramenta...`,
                                        side: "bottom",
                                        align: "start",
                                        showButtons:[''],
                                    },
                                    onHighlighted: async (el) => {
                                        await new Promise((resolve) => setTimeout(resolve, 1000));
                                        const btnEdit = document.querySelector(`#classe-edit-${dataId}`)
                                        if(!btnEdit) return;
                                        await btnEdit.click()
                                        el.style.zIndex = "0";

                                        await new Promise((resolve) => setTimeout(resolve, 2000));
                                        await driverObj.moveNext();
                                    }
                                },
                                {
                                    element: `#classe-edit-form-${dataId}`,
                                    popover: {
                                        description: `Veja as informações que deseja alterar, em seguida, clique em "Renomear" ou "Cancelar"`,
                                        side: "left",
                                        align: "start",
                                        onPopoverRender: (popover, { config, state }) => {
                                            const actions =  AddButtonActions(popover, "Cancelar operação")
                                            popover.nextButton.remove();
                                            popover.previousButton.remove();
                                            actions.addEventListener('click', async () => {
                                                Livewire.dispatch("classe-edit-cancel");
                                                await driverObj.destroy();
                                                store.openMai = true;
                                                store.isTourActive = false;
                                            })
                                        }
                                    },
                                    onHighlighted: async (el) => {
                                        await new Promise((resolve) => setTimeout(resolve, 1000));
                                        const btnCancel = el.querySelector(`#edit-cancel`)
                                        const btnConfirm = el.querySelector(`#edit-confirm`)
                                        if(!btnCancel || !btnConfirm) return;
                                        btnCancel.addEventListener('click', async () => {
                                            await driverObj.destroy();
                                            store.openMai = true;
                                            store.isTourActive = false;
                                        })
                                        btnConfirm.addEventListener('click', async () => {
                                            driverObj.destroy();
                                            store.openMai = true;
                                            store.isTourActive = false;
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
                            store.isTourActive = false;
                        };

                        classeCount.forEach((classe) => {
                            classe.style.zIndex = "200000000";
                            classe.addEventListener('click', (event) => {
                                event.preventDefault();
                                startEditTour(classe);
                            });
                        });

                        // Quando o professor possui apenas 1 turma, o tour seleciona
                        // e clica automaticamente no botão "Editar" dessa turma
                        if (classeCount.length === 1) {
                            await new Promise((resolve) => setTimeout(resolve, 2000));
                            await startEditTour(classeCount[0]);
                        }
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
    store.isTourActive = true;
   return new Promise(async (resolve) => {
        const classList = document.querySelectorAll(".card-classe");
        const classContent = document.querySelector("#classe-list-content");
        if(!classContent || classList.length === 0) {
            store.isTourActive = false;
            return
        }

        const driverObj = drivertour({
            ...configDriver,
            steps: [
                {
                    element: `#${classContent.id}`,
                    popover: {
                        description: classList.length > 1 ? `Escolha a Turma que deseja "Editar"` : `Interagindo com a ferramenta...`,
                        showButtons: [""],
                    },
                    onHighlighted: async (el) => {
                        BlockButtons(el);

                        const classeCount = el.querySelectorAll("div.card-classe")
                        let classeLected = null
                        if (!classeCount || classeCount.length === 0) {
                            return;
                        }

                        const startAccessTour = async (classe) => {
                            if (classeLected) return;
                            classeLected = true;
                            // Remove z-index das disciplinas não selecionadas
                            classeCount.forEach((otherClasse) => {
                                if (classe !== otherClasse) {
                                    otherClasse.style.zIndex = "0";
                                }
                            });

                            // Reabilita os botões antes de continuar
                            document.querySelectorAll('[data-tour-disabled="true"]').forEach(btn => {
                                btn.style.pointerEvents = '';
                                //btn.style.opacity = '';
                                 // Restaura links <a>
                                if (btn.tagName === 'A') {
                                    // Restaura href se foi salvo
                                    if (btn.dataset.originalHref) {
                                        btn.setAttribute('href', btn.dataset.originalHref);
                                        delete btn.dataset.originalHref;
                                    }

                                    // Restaura wire:navigate
                                    if (btn.dataset.hadWireNavigate === 'true') {
                                        btn.setAttribute('wire:navigate', '');
                                        delete btn.dataset.hadWireNavigate;
                                    }

                                    // Restaura wire:click se foi salvo
                                    if (btn.dataset.originalWireClick) {
                                        btn.setAttribute('wire:click', btn.dataset.originalWireClick);
                                        delete btn.dataset.originalWireClick;
                                    }
                                }

                                delete btn.dataset.tourDisabled;
                            });

                            const dataId = classe.getAttribute('data-tour');
                            await driverObj.destroy();
                            const secondStep = [
                                {
                                    element: `#classe-${dataId}`,
                                    popover: {
                                        description: `Interagindo com a ferramenta...`,
                                        side: "bottom",
                                        align: "start",
                                        showButtons:['']
                                    },
                                    onHighlighted: async (el) => {
                                        await new Promise((resolve) => setTimeout(resolve, 2000));
                                        el.style.zIndex = "0";
                                        await driverObj.moveNext();
                                    }
                                },
                                {
                                    element: `#classe-access-${dataId}`, // Seleciona o elemento com base no ID
                                    popover: {
                                        description: `Interagindo com a ferramenta...`,
                                        side: "bottom",
                                        align: "start",
                                        showButtons:['']
                                    },
                                    onHighlighted: async (el) => {
                                        await new Promise((resolve) => setTimeout(resolve, 1000));
                                        el.click()
                                        //el.style.zIndex = "0";
                                        await new Promise((resolve) => setTimeout(resolve, 1000));
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
                            store.isTourActive = false;
                        };

                        classeCount.forEach((classe) => {
                            classe.style.zIndex = "200000000";
                            classe.addEventListener('click', (event) => {
                                event.preventDefault();
                                startAccessTour(classe);
                            });
                        });

                        // Quando o professor possui apenas 1 turma, o tour seleciona
                        // e clica automaticamente no botão "Acessar" dessa turma
                        if (classeCount.length === 1) {
                            await new Promise((resolve) => setTimeout(resolve, 2000));
                            await startAccessTour(classeCount[0]);
                        }
                    }
                }
            ]
        })
        return await driverObj.drive();
    })
 }


/**
 * Tour contextual: detecta turmas sem alunos e guia o professor
 * a acessá-las e cadastrar os primeiros alunos.
 *
 * Fluxo:
 *   1. Lista turmas sem alunos
 *   2. Se há mais de 1, o usuário escolhe qual acessar
 *   3. Se há apenas 1, seleciona automaticamente
 *   4. Destaca a turma, clica em "Acessar"
 *   5. Redireciona para a página da turma
 *   6. Inicia o tour de cadastro de alunos (com loop)
 */
export async function StartTourClasseEmptyStudents() {
  let store = Alpine.store("tourguide");
  store.openMai = false;
  store.isTourActive = true;

  return new Promise(async (resolve) => {
    const classList = document.querySelectorAll(".card-classe");
    const classContent = document.querySelector("#classe-list-content");

    if (!classContent || classList.length === 0) {
      store.openMai = true;
      store.isTourActive = false;
      return resolve();
    }

    const driverObj = drivertour({
      ...configDriver,
      steps: [
        {
          element: `#${classContent.id}`,
          popover: {
            description: "Interagindo com a ferramenta...",
            side: "bottom",
            align: "start",
            showButtons: [""],
          },
          onHighlighted: async (el) => {
            BlockButtons(el);

            const allCards = el.querySelectorAll(".card-classe");
            // const classeCount = Array.from(allCards).filter(
            //   (card) => card.getAttribute("data-students-count") === "0"
            // );
            let classeLected = null;

            if (!allCards || allCards.length === 0) {
                return;
            }

            // Atualiza a descrição do popover baseado na quantidade
            const popover = document.querySelector(".driver-popover-description");
            if (popover) {
              popover.textContent = allCards.length > 1
                ? "Escolha qual turma deseja cadastrar alunos."
                : "Interagindo com a ferramenta...";
            }

            const startEmptyStudentsTour = async (classe) => {
              if (classeLected) return;
              classeLected = true;
              allCards.forEach((otherClasse) => {
                if (classe !== otherClasse) {
                  otherClasse.style.zIndex = "0";
                }
              });

              // Reabilita os botões antes de continuar
              document.querySelectorAll('[data-tour-disabled="true"]').forEach(btn => {
                if (btn.tagName === 'A') {
                    // Restaura href
                    if (btn.dataset.originalHref) {
                        btn.setAttribute('href', btn.dataset.originalHref);
                        delete btn.dataset.originalHref;
                    }

                    // Restaura wire:navigate
                    if (btn.dataset.hadWireNavigate === 'true') {
                        btn.setAttribute('wire:navigate', '');
                        delete btn.dataset.hadWireNavigate;
                    }

                    // Restaura wire:click se foi salvo
                    if (btn.dataset.originalWireClick) {
                        btn.setAttribute('wire:click', btn.dataset.originalWireClick);
                        delete btn.dataset.originalWireClick;
                    }
                }

                btn.style.pointerEvents = '';
                btn.style.opacity = '';
                delete btn.dataset.tourDisabled;
              });

              const dataId = classe.getAttribute("data-tour");
              if (!dataId) {
                store.isTourActive = false;
                return;
              }

              await driverObj.destroy();
              const secondStep = [
                {
                  element: `#classe-${dataId}`,
                  popover: {
                    description: "Interagindo com a ferramenta...",
                    side: "bottom",
                    align: "start",
                    showButtons: [""],
                  },
                  onHighlighted: async (el) => {
                    await new Promise((r) => setTimeout(r, 3000));
                    el.style.zIndex = "0";
                    await driverObj.moveNext();
                  },
                },
                {
                  element: `#classe-access-${dataId}`,
                  popover: {
                    description: "Interagindo com a ferramenta...",
                    side: "bottom",
                    align: "start",
                    showButtons: [""],
                  },
                  onHighlighted: async (el) => {
                    await new Promise((r) => setTimeout(r, 1000));
                    el.click();
                    //if(el.tagName === "A") {
                        // await new Promise((r) => setTimeout(r, 300));
                        // if (el.hasAttribute('href')) {
                        //     el.dataset.originalHref = el.getAttribute('href');
                        //     el.removeAttribute('href');
                        // }

                        // // Salva wire:navigate
                        // if (el.hasAttribute('wire:navigate')) {
                        //     el.dataset.hadWireNavigate = 'true';
                        //     el.removeAttribute('wire:navigate');
                        // }


                        await driverObj.destroy();
                        await new Promise((r) => setTimeout(r, 1500));
                        await ContinueRegisterStudentTour();
                        if (resolve) resolve();
                   // }
                  },
                },
              ];

              const updatedDriverObj = drivertour({
                ...configDriver,
                steps: secondStep,
              });
              await updatedDriverObj.drive();
              store.isTourActive = false;
            };

            allCards.forEach((classe) => {
              classe.style.zIndex = "200000000";
              classe.addEventListener("click", (event) => {
                event.preventDefault();
                startEmptyStudentsTour(classe);
              });
            });

            // Quando há apenas 1 turma sem alunos, seleciona automaticamente
            if (allCards.length === 1) {
              await new Promise((r) => setTimeout(r, 2000));
              await startEmptyStudentsTour(allCards[0]);
            }
          },
        },
      ],
    });
    await driverObj.drive();
    store.isTourActive = false;
  });
}

