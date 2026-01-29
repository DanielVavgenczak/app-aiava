import { AddButtonActions, configDriver } from "./utils";
import { ContinueUploadBlock } from "./discipline/lesson";

Livewire.on("continue-created-content", async (event) => {
  console.log("Evento recebido:", event);
  try {
    if (!event?.continue) {
      console.warn("Evento ignorado: continue = false");
      return;
    }

    console.log("⏳ Iniciando o tour de criação de conteúdo...");
    await new Promise((resolve) => setTimeout(resolve, 3000));

    await StartTourDisciplineCreateContent();
    console.log("✅ Tour de conteúdo iniciado com sucesso.");
  } catch (e) {
    console.error("❌ Erro ao continuar o tour de criação de conteúdo:", e);
  } finally {
    console.log("📦 Iniciando bloco de upload...");
    try {
      await new Promise((resolve) => setTimeout(resolve, 3000));
      await StartTourDisciplineCreateContentUploadBlock();

    } catch (uploadErr) {
      console.error("❌ Erro ao iniciar bloco de upload:", uploadErr);
    }
  }
});


export async function StartTourDisciplineCreateContent() {
  let store = Alpine.store("tourguide");
  store.openMai = false;
  return new Promise(async (resolve) => {
    let disciplineID = null;
    const btnCreateLesson = document.querySelector(`#content-lesson-create`);
    if (!btnCreateLesson) {
      return;
    }
    const driverObj = drivertour({
      ...configDriver,
      steps: [
        {
          element: `#${btnCreateLesson.id}`,
          popover: {
            description: `Vou clicar no botão "+ Aula"`,
            showButtons: [""],
          },
          onHighlighted: async (el) => {
            await new Promise((resolve) =>
              setTimeout(resolve, 1000),
            );
            disciplineID = el.getAttribute("data-tour");
            el.click();
            await new Promise((resolve) =>
              setTimeout(resolve, 1000),
            );
            await driverObj.moveNext();
          },
        },
        {
          element: `#lesson-title`,
          popover: {
            description: "Escreva o Título da aula",
            onNextClick: async (el) => {
              const title = el.querySelector(
                `input.lesson-input-title`,
              )
              if (title.value.trim() === "") {
                alert("por favor preencha o campo título");
                return;
              }
              await driverObj.moveNext();
            },
            onPopoverRender: (popover, { config, state }) => {
              const actions = AddButtonActions(
                popover,
                "Voltar ao início",
              );
              actions.addEventListener("click", () => {
                Livewire.dispatch("open-modal-module", {
                  open: false,
                });
                driverObj.destroy();
                store.openMai = true;
              });
            },
          },
        },
        {
          element: `#lesson-actions-buttons`,
          popover: {
            description:
              "Clique em 'Cadastrar' para salvar a aula ou em 'Cancelar' para cancelar o cadastro.",
            onPopoverRender: (popover, { config, state }) => {
              const actions = AddButtonActions(
                popover,
                "Voltar ao início",
              );
              actions.addEventListener("click", () => {
                Livewire.dispatch("open-modal-module", {
                  open: false,
                });
                driverObj.destroy();
                store.openMai = true;
              });
            },
          },
          onHighlighted: async (el) => {
            const toCloseActions = () => {
              Livewire.dispatch("open-modal-module", {
                open: false,
              });
              store.openMai = false;
              driverObj.destroy();
            };
            const btnCancel = el.querySelector(`#module-input-cancel`);
            const btnCreate = el.querySelector(`#module-input-create`);
           
            await  btnCancel.addEventListener("click", toCloseActions);
            await btnCreate.addEventListener("click", async () => {
               await new Promise((resolve) => setTimeout(resolve, 1000));    
                
              const errorTitle = document.querySelector("#title_module_error");
                await new Promise((resolve) => setTimeout(resolve, 500));    
                if(errorTitle && errorTitle.textContent.trim() !== ""){                
                    alert("Aviso: Você ja possue uma aula com esse nome.");
                    return;
                }
              Livewire.dispatch("to-teste-register", {
                open: false,
              });
              store.openMai = false;
              resolve(true);
              await driverObj.destroy();
              await new Promise((resolve) => setTimeout(resolve, 1000));
              await StartTourDisciplineCreateContentUploadBlock();
            });
          },
        },
      ],
    });
    await driverObj.drive();
  });
}

export async function StartTourDisciplineCreateContentUploadBlock(lesson_id = null) {
  let store = Alpine.store("tourguide");
  store.openMai = false;
  return new Promise(async (resolve) => {
    const modules = document.querySelectorAll("div.module-card");
    const lastLessonRegisted = modules[modules.length - 1].getAttribute("data-tour");
    let moduleText = `Vamos adicionar um bloco à aula criada`
    await new Promise((resolve) => setTimeout(resolve, 1000)); 
    await ContinueUploadBlock(null, lastLessonRegisted);
    return;
  });
}

export async function StartTourDisciplineUploadBlockToLessonSelected() {
  let store = Alpine.store("tourguide");
  store.openMai = false;
  let running = false;
  return new Promise(async (resolve) => {
    const modulesCard = document.querySelectorAll("div.module-card");    
    await new Promise((resolve) => setTimeout(resolve, 1000))
    if(modulesCard.length === 1) {
       const uniqueModule = modulesCard[0].getAttribute('data-tour');
       await ContinueUploadBlock(null, uniqueModule);
       return;
    }
    
    const driverObj = drivertour({
      ...configDriver,
      steps: [
        {
          element: `#module-list-all`,
          popover: {
            description: `Selecione a aula onde deseja adicionar um bloco`,
            showButtons: [""],
          },
          onHighlighted: async (el) => {
            await new Promise((resolve) => setTimeout(resolve, 1000));                        
            let moduleSelected = false;            
            if(modulesCard.length > 1) {         
              modulesCard.forEach(module => {
                module.style.zIndex = "200000000"
                module.addEventListener('click', async (event) => {
                  if (moduleSelected) return;
                  
                  event.preventDefault();
                  moduleSelected = true;
                  running = true;
                  // Remove z-index das disciplinas não selecionadas
                  modulesCard.forEach((otherModule) => {
                    if (module !== otherModule) {
                      otherModule.style.zIndex = "0";
                    }
                  });
                  
                  const moduleId = module.getAttribute('data-tour');
                  await driverObj.destroy()
                  await ContinueUploadBlock(null, moduleId);
                  resolve();
                });       
              });
             }
          },
        }
      ],
    });
    
    await driverObj.drive();
    resolve();
  });
}




export function StartTourCreateActivitTask() {
  let store = Alpine.store("tourguide");
  store.openMai = false;
  const createTask = document.querySelector("#create-activity-task");
  if (!createTask) return;
  const disciplieID = createTask.getAttribute("data-tour");

  return new Promise(async (resolve) => {
    const driverObj = drivertour({
      ...configDriver,
      steps: [
        {
          element: `#create-title-question-${disciplieID}`,
          popover: {
            description: "Descreva aqui a primeira questão",
            //showButtons: [""],
            onNextClick: async () => {
              const titleOfQuestion =
                document.querySelector(`#id_titleofquestion`);
              if (!titleOfQuestion) return;
              if (!titleOfQuestion.value.trim()) {
                alert("por favor preencha o campo de texto");
                return;
              }
              await driverObj.moveNext();
            },
          },
        },
        {
          element: `#question-select-types-${disciplieID}`,
          popover: {
            description:
              "Agora escolha o tipo da questão que deseja criar",
          },
          onHighlighted: async (el) => {
            await new Promise((resolve) =>
              setTimeout(resolve, 100),
            );
            const buttonTypeRadio = el.querySelector(
              `#question-select-radio-${disciplieID}`,
            );
            const buttonTypeText = el.querySelector(
              `#question-select-text-${disciplieID}`,
            );
            if (!buttonTypeRadio || !buttonTypeText) {
              return;
            }
            buttonTypeRadio.addEventListener("click", async () => {
              if (driverObj) {
                await new Promise((resolve) =>
                  setTimeout(resolve, 100),
                );
                await driverObj.destroy();
              }
              await new Promise((resolve) =>
                setTimeout(resolve, 1000),
              );
              const updateDriveToRadioQuestion = drivertour({
                ...configDriver,
                steps: [
                  {
                    element: `#create-question-option-${disciplieID}`,
                    popover: {
                      description:
                        "Digite a opção da questão que deseja criar",

                    },

                  },
                  {
                    element: `#create-question-option-add-${disciplieID}`,
                    popover: {
                      description: "Clique no botão '+' para adicionar"
                    }
                  },
                  {
                    element: `#question-options-list-${disciplieID}`,
                    popover: {
                      description:
                        "Aqui esta a lista das opções criadas, crie no minimo 3 opções",
                      //showButtons: [""],
                      onPopoverRender: (
                        popover,
                        { config, state },
                      ) => {
                        const addMore =
                          AddButtonActions(
                            popover,
                            "+ Opção",
                          );
                        addMore.addEventListener(
                          "click",
                          async () => {
                            await updateDriveToRadioQuestion.moveNext([1]);
                          },
                        );
                      },
                    },
                  },
                  // {
                  //     element: `#create-question-associate-module-${disciplieID}`,
                  //     popover: {
                  //         description:
                  //             "Selecione a disciplina que deseja vincular essa Atividade ou 'Voltar ao inicio'",
                  //         onPopoverRender: (
                  //             popover,
                  //             { config, state },
                  //         ) => {
                  //             const actions =
                  //                 AddButtonActions(
                  //                     popover,
                  //                     "Voltar ao inicio",
                  //                 );
                  //             actions.addEventListener(
                  //                 "click",
                  //                 async () => {
                  //                     await updateDriveToTextQuestion.destroy();
                  //                     store.openMai = true;
                  //                 },
                  //             );
                  //         },
                  //     },
                  // },
                ],
              });
              await updateDriveToRadioQuestion.drive();
              //return;
            });
            buttonTypeText.addEventListener("click", async () => {
              if (driverObj) {
                await new Promise((resolve) =>
                  setTimeout(resolve, 100),
                );
                await driverObj.destroy();
              }
              await new Promise((resolve) =>
                setTimeout(resolve, 1000),
              );
              const updateDriveToTextQuestion = drivertour({
                ...configDriver,
                steps: [
                  {
                    element: `#create-question-button-text-${disciplieID}`,
                    popover: {
                      description:
                        "Vamos criar uma questão de texto",
                    },
                    onHighlighted: async (el) => {
                      await new Promise((resolve) =>
                        setTimeout(resolve, 1000),
                      );
                      el.click();
                      await new Promise((resolve) =>
                        setTimeout(resolve, 1000),
                      );
                      await driverObj.moveNext();
                    },
                  },
                  {
                    element: `#question-created-${disciplieID}`,
                    popover: {
                      description:
                        "Aqui a questão que você acabou de criar",
                      showButtons: [""],
                    },
                    onHighlighted: async (el) => {
                      await new Promise((resolve) =>
                        setTimeout(resolve, 3000),
                      );
                      await updateDriveToTextQuestion.moveNext();
                    },
                  },
                  {
                    element: `#create-question-associate-module-${disciplieID}`,
                    popover: {
                      description:
                        "Selecione a disciplina que deseja vincular essa Atividade ou 'Voltar ao início'",
                      onPopoverRender: (
                        popover,
                        { config, state },
                      ) => {
                        const actions =
                          AddButtonActions(
                            popover,
                            "Voltar ao início",
                          );
                        actions.addEventListener(
                          "click",
                          async () => {
                            await updateDriveToTextQuestion.destroy();
                            store.openMai = true;
                          },
                        );
                      },
                    },
                  },
                ],
              });
              await updateDriveToTextQuestion.drive();
            });
          },
        },
      ],
    });

    await driverObj.drive();
  });
}
