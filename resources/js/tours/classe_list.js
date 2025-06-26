import { AddButtonActions, configDriver, startDriverTour } from "./utils";

export async function StartTourClasseCreate() {
    let store = Alpine.store("tourguide");
    store.openMai = false;
    console.log('vamos criar uma classe')
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
                            console.error("não encontramos ela aqui");
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
                            actions.addEventListener("click", () => {
                                //Livewire.dispatch("to-close-create");
                                driverObj.destroy();
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
                                //Livewire.dispatch("to-close-create");
                                driverObj.destroy();
                                store.openMai = true;
                            });
                        },
                    },
                },
                {
                    element: `#classe-input-actions`,
                    popover: {
                        description: "Escolhar Cancelar ou Cadastrar",
                        onPopoverRender: (popover, { config, state }) => {
                            const actions = AddButtonActions(
                                popover,
                                "voltar ao inicio",
                            );
                            actions.addEventListener("click", () => {
                                //Livewire.dispatch("to-close-create");
                                driverObj.destroy();
                                store.openMai = true;
                            });
                        },
                    },
                    onHighlighted:async(el) => {
                        console.log("Loaded das ações das classes", el)
                        const cancel = el.querySelector("#classe-input-cancel");
                        const save = el.querySelector("#classe-input-save");
                        if (!cancel && !save) {
                            console.log('Nenhumm dos elementos doi encontrado')
                            return;
                        }
                        cancel.addEventListener("click",async() => {
                            //Livewire.dispatch("to-close-create");
                            await driverObj.destroy();
                            store.openMai = true;
                        });
                    
                        save.addEventListener("click", async () => {
                            console.log('clicou no salvar')
                            await driverObj.destroy();
                            store.openMai = true;   
                        });
                    },
                },            ]
        })
        return await driverObj.drive();
    })
}