
import { ContinueUploadBlock } from "./discipline/lesson";
import { AddButtonActions, configDriver } from "./utils";
Livewire.on("continue-created-content", async (event) => {
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
              setTimeout(resolve, 3000),
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
                alert("por favor preencha o campo de titulo");
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
            if (!btnCreate && !btnCancel) {
              return;
            }
            btnCancel.addEventListener("click", toCloseActions);
            btnCreate.addEventListener("click", async () => {
              Livewire.dispatch("to-teste-register", {
                open: false,
              });
              store.openMai = false;
              resolve(true);
              let lesson_id = document.querySelector

              await driverObj.destroy();
              await new Promise((resolve) => setTimeout(resolve, 3000));
              //console.log("O Id da discipline aqu")
              // Isolate find data-tour lesson 
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
    let moduleText;
    if (!lesson_id) {
      moduleText =
        modules.length > 1
          ? `Em qual aula deseja adicionar um bloco?`
          : `Como voce so tem uma aula vamos direto ao upload dos blocos`;
    }
    const lastLessonRegisted = modules[modules.length - 1].getAttribute("data-tour");
    moduleText = `Vamos adicionar um bloco a sua aula criada`

    const driverObj = drivertour({
      ...configDriver,
      steps: [
        {
          element: `#module-list-all`,
          popover: {
            description: moduleText,
            showButtons: [""],
          },
          onHighlighted: async (el) => {
            await new Promise((resolve) =>
              setTimeout(resolve, 1000),
            );
            let dataId;
            if (!lastLessonRegisted) {
              dataId = el.getAttribute("data-tour");
            }
            dataId = lastLessonRegisted;
            let moduleSelected = false;
            if (!modules || modules.length === 0) return;
            if (dataId != '') {
              console.log("modules para ir fazer o tour", modules.length, dataId);
              // passamos o dataId 
              let getModules = modules
              await driverObj.destroy();
              await new Promise((resolve) => setTimeout(resolve, 3000));
              await ContinueUploadBlock(modules, dataId)
              // 
              return;
            }
            if (modules.length === 1) {
              await new Promise((resolve) =>
                setTimeout(resolve, 1000),
              );
              dataId = modules[0].getAttribute("data-tour");
              console.log("caiu aqui gente", dataId)
              await driverObj.destroy();
              await new Promise((resolve) =>
                setTimeout(resolve, 1000),
              );
              const secondStep = [
                {
                  element: `#accordion-module-${dataId}`,
                  popover: {
                    description: `Vamos abrir as nessa unica aula`,
                    side: "bottom",
                    align: "start",
                    showButtons: [""],
                  },
                  onHighlighted: async (el) => {
                    await new Promise((resolve) =>
                      setTimeout(resolve, 3000),
                    );
                    await driverObj.moveNext();
                  },
                },
                {
                  element: `#open-accordion-block-${dataId}`, // Seleciona o elemento com base no ID
                  popover: {
                    description: `Vou clicar e abrir para listar os blocos`,
                    side: "bottom",
                    align: "start",
                    showButtons: [""],
                  },
                  onHighlighted: async (el) => {
                    await new Promise((resolve) =>
                      setTimeout(resolve, 3000),
                    );
                    const btnOpenAccordion =
                      document.querySelector(
                        `#open-accordion-block-${dataId}`,
                      );
                    await btnOpenAccordion.click();
                    await driverObj.moveNext();
                  },
                },
                {
                  element: `#block-open-new-block-${dataId}`,
                  popover: {
                    description: `Vou clicar aqui para abrir a opção de adiconar um bloco`,
                    side: "right",
                    align: "center",
                    showButtons: [""],
                  },
                  onHighlighted: async (el) => {
                    await new Promise((resolve) =>
                      setTimeout(resolve, 3500),
                    );
                    await el.click();
                    await driverObj.moveNext();
                  },
                },
                {
                  element: `#browser-file-${dataId}`, // Seleciona o elemento com base no ID
                  popover: {
                    description: `Clique no icone para adiconar um novo vídeo`,
                    side: "top",
                    align: "start",
                  },
                  onHighlighted: async (el) => {
                    //await new Promise((resolve) => setTimeout(resolve, 3000));
                    const videoElement =
                      document.querySelector(
                        `#temp-video-preview-${dataId}`,
                      );
                    if (!videoElement) {
                      console.error(
                        "Elemento de vídeo não encontrado",
                      );
                      return;
                    }

                    // Verifica se já tem src definido
                    if (
                      videoElement.src &&
                      videoElement.src !== ""
                    ) {
                      console.log(
                        "Vídeo já selecionado - avançando",
                      );
                      await driverObj.moveNext();
                      return;
                    }

                    // Cria um observador para detectar quando o src for alterado
                    const observer =
                      new MutationObserver(
                        async (mutations) => {
                          for (const mutation of mutations) {
                            if (
                              mutation.type ===
                              "attributes" &&
                              mutation.attributeName ===
                              "src"
                            ) {
                              if (
                                videoElement.src &&
                                videoElement.src !==
                                ""
                              ) {
                                console.log(
                                  "Vídeo selecionado detectado - avançando",
                                );
                                observer.disconnect(); // Para de observar
                                await driverObj.moveNext();
                              }
                            }
                          }
                        },
                      );

                    // Configura o observador
                    observer.observe(videoElement, {
                      attributes: true, // Observa mudanças em atributos
                      attributeFilter: ["src"], // Apenas para o atributo src
                    });

                    // Timeout de segurança (opcional)
                    setTimeout(() => {
                      observer.disconnect();
                      console.log(
                        "Timeout - observador removido",
                      );
                    }, 30000); // 30 segundos
                  },
                },
                {
                  element: `#temp-video-preview-${dataId}`, // Seleciona o elemento com base no ID
                  popover: {
                    description: `Verificar se está ok, clique em 'Próximo' para continuar o upload`,
                    side: "top",
                    align: "start",
                    onPopoverRender: (
                      popover,
                      { config, state },
                    ) => {
                      const actions =
                        AddButtonActions(
                          popover,
                          "Voltar ao inicio",
                        );
                      actions.addEventListener(
                        "click",
                        async () => {
                          //Livewire.dispatch('to-close')
                          await driverObj.destroy();
                          store.openMai = true;
                        },
                      );
                    },
                  },
                  onNextClick: async () => {
                    console.log("click para p roi");
                  },

                },
                {
                  element: `#temp-preview-actions-${dataId}`, // Seleciona o elemento com base no ID
                  popover: {
                    description: `Clique em 'Enviar' para enviar o vídeo`,
                    side: "top",
                    align: "start",
                    onPopoverRender: (
                      popover,
                      { config, state },
                    ) => {
                      const actions =
                        AddButtonActions(
                          popover,
                          "Voltar ao inicio",
                        );
                      actions.addEventListener(
                        "click",
                        async () => {
                          //Livewire.dispatch('to-close')
                          await driverObj.destroy();
                          store.openMai = true;
                        },
                      );
                    },
                  },
                  onHighlighted: async (el) => {
                    //await new Promise((resolve) => setTimeout(resolve, 3000));
                    const btnSendVideoBlock =
                      el.querySelector(
                        `#lesson-send-video-block-content-${dataId}`,
                      );
                    if (!btnSendVideoBlock) {
                      console.log("fudeo");
                      return;
                    }
                    btnSendVideoBlock.addEventListener(
                      "click",
                      async () => {
                        await driverObj.moveNext();
                      },
                    );
                    //console.log('Bateu para terminar')
                  },
                },
                {
                  element: `#progress-bar-container-${dataId}`, // Seleciona o elemento com base no ID
                  popover: {
                    description: "Aguarde enquanto o video e enviado",
                    side: "top",
                    align: "start",
                  },
                  onHighlighted: async (el) => {
                    await new Promise((resolve) =>
                      setTimeout(resolve, 1000),
                    );
                    //console.log('Bateu para terminar')
                    resolve(true);
                    await driverObj.destroy();
                    console.log("travamos here");
                  },
                },
              ];

              // Cria um novo tour com os passos atualizados
              const updateOnedDriverObj = drivertour({
                ...configDriver,
                steps: secondStep,
              });

              await updateOnedDriverObj.drive();
              return;


            }
            /** Refazer */
            modules.forEach((module) => {
              module.style.zIndex = "200000000";

              module.addEventListener("click", async (event) => {
                if (moduleSelected) return;
                event.preventDefault();
                moduleSelected = true;
                // Remove z-index das disciplinas não selecionadas
                modules.forEach((otherModule) => {
                  if (module !== otherModule) {
                    otherModule.style.zIndex = "0";
                  }
                });
                dataId = module.getAttribute("data-tour");
                await driverObj.destroy();
                // Cria um novo tour com os passos atualizados
                const secondStep = [
                  {
                    element: `#accordion-module-${dataId}`,
                    popover: {
                      description: `Vamos abrir as opções na aula selecionada`,
                      side: "bottom",
                      align: "start",
                      showButtons: [""],
                    },
                    onHighlighted: async (el) => {
                      await new Promise((resolve) =>
                        setTimeout(resolve, 3000),
                      );
                      await driverObj.moveNext();
                    },
                  },
                  {
                    element: `#open-accordion-block-${dataId}`, // Seleciona o elemento com base no ID
                    popover: {
                      description: `Vou clicar e abrir para listar os blocos`,
                      side: "bottom",
                      align: "start",
                      showButtons: [""],
                    },
                    onHighlighted: async (el) => {
                      await new Promise((resolve) =>
                        setTimeout(resolve, 3000),
                      );
                      const btnOpenAccordion =
                        document.querySelector(
                          `#open-accordion-block-${dataId}`,
                        );
                      await btnOpenAccordion.click();
                      await driverObj.moveNext();
                    },
                  },
                  {
                    element: `#block-open-new-block-${dataId}`,
                    popover: {
                      description: `Vou clicar aqui para abrir a opção de adicionar um bloco`,
                      side: "right",
                      align: "center",
                      showButtons: [""],
                    },
                    onHighlighted: async (el) => {
                      await new Promise((resolve) =>
                        setTimeout(resolve, 3500),
                      );
                      await el.click();
                      // el.click(); // Simula o clique no botão de edição
                      await driverObj.moveNext();
                    },
                  },
                  {
                    element: `#browser-file-${dataId}`, // Seleciona o elemento com base no ID
                    popover: {
                      description: `Clique no icone para adiconar um novo vídeo`,
                      side: "top",
                      align: "start",
                      // onPopoverRender: (popover, { config, state }) => {
                      //     const actions =  AddButtonActions(popover, "voltar ao inicio")
                      //     actions.addEventListener('click', async () => {
                      //         Livewire.dispatch('to-close')
                      //         await driverObj.destroy();
                      //         store.openMai = true;
                      //     })
                      // }
                    },
                    onHighlighted: async (el) => {
                      //await new Promise((resolve) => setTimeout(resolve, 3000));
                      const videoElement =
                        document.querySelector(
                          `#temp-video-preview-${dataId}`,
                        );
                      if (!videoElement) {
                        console.error(
                          "Elemento de vídeo não encontrado",
                        );
                        return;
                      }

                      // Verifica se já tem src definido
                      if (
                        videoElement.src &&
                        videoElement.src !== ""
                      ) {
                        console.log(
                          "Vídeo já selecionado - avançando",
                        );
                        await driverObj.moveNext();
                        return;
                      }

                      // Cria um observador para detectar quando o src for alterado
                      const observer =
                        new MutationObserver(
                          async (mutations) => {
                            for (const mutation of mutations) {
                              if (
                                mutation.type ===
                                "attributes" &&
                                mutation.attributeName ===
                                "src"
                              ) {
                                if (
                                  videoElement.src &&
                                  videoElement.src !==
                                  ""
                                ) {
                                  console.log(
                                    "Vídeo selecionado detectado - avançando",
                                  );
                                  observer.disconnect(); // Para de observar
                                  await driverObj.moveNext();
                                }
                              }
                            }
                          },
                        );

                      // Configura o observador
                      observer.observe(videoElement, {
                        attributes: true, // Observa mudanças em atributos
                        attributeFilter: ["src"], // Apenas para o atributo src
                      });

                      // Timeout de segurança (opcional)
                      setTimeout(() => {
                        observer.disconnect();
                        console.log(
                          "Timeout - observador removido",
                        );
                      }, 30000); // 30 segundos
                    },
                  },
                  {
                    element: `#temp-video-preview-${dataId}`, // Seleciona o elemento com base no ID
                    popover: {
                      description: `Verifique se está ok, clique em 'Próximo' para continuar o upload`,
                      side: "top",
                      align: "start",
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
                            //Livewire.dispatch('to-close')
                            await driverObj.destroy();
                            store.openMai = true;
                          },
                        );
                      },
                    },
                    onNextClick: async () => {
                      console.log("click para p roi");
                    },

                  },
                  {
                    element: `#temp-preview-actions-${dataId}`, // Seleciona o elemento com base no ID
                    popover: {
                      description: `Clique em 'Enviar' para enviar o vídeo`,
                      side: "top",
                      align: "start",
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
                            //Livewire.dispatch('to-close')
                            await driverObj.destroy();
                            store.openMai = true;
                          },
                        );
                      },
                    },
                    onHighlighted: async (el) => {
                      //await new Promise((resolve) => setTimeout(resolve, 3000));
                      const btnSendVideoBlock =
                        el.querySelector(
                          `#lesson-send-video-block-content-${dataId}`,
                        );
                      if (!btnSendVideoBlock) {
                        console.log("fudeo");
                        return;
                      }
                      btnSendVideoBlock.addEventListener(
                        "click",
                        async () => {
                          await driverObj.moveNext();
                        },
                      );
                      //console.log('Bateu para terminar')
                    },
                  },
                  {
                    element: `#progress-bar-container-${dataId}`, // Seleciona o elemento com base no ID
                    popover: {
                      description: "Aguarde enquanto o vídeo e enviado",
                      side: "top",
                      align: "start",
                    },
                    onHighlighted: async (el) => {
                      await new Promise((resolve) =>
                        setTimeout(resolve, 1000),
                      );
                      //console.log('Bateu para terminar')
                      resolve(true);
                      await driverObj.destroy();
                      console.log("travamos here");
                    },
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
          },
        },
      ],
    });
    await driverObj.drive();
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
