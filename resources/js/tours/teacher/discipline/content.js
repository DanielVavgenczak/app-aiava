import { AddButtonActions, configDriver } from "../../utils";
import { ContinueUploadBlock } from "./lesson";
export { StartEditLesson, StartDeleteLesson } from "./lesson";
export {
  StartTourManageActivityTask,
  StartTourActivityDeliveries,
  StartTourEditActivityTask,
} from "./task";

// aqu
Livewire.on("continue-created-content", async (event) => {
  try {
    if (!event?.continue) {
      return;
    }
    await new Promise((resolve) => setTimeout(resolve, 2000));

    await StartTourDisciplineCreateContent(true);
  } catch (e) {
    console.error("Erro ao continuar o tour de criação de conteúdo:", e);
  } finally {
    try {
      await new Promise((resolve) => setTimeout(resolve, 2000));
      await StartTourDisciplineCreateContentUploadBlock();

    } catch (uploadErr) {
      console.error("Erro ao iniciar bloco de upload:", uploadErr);
    }
  }
});


export async function StartTourDisciplineCreateContent(skipUpload = false) {
  // tour.js sempre passa o UUID como primeiro argumento.
  // skipUpload == true (booleano) = chamado do continue-created-content (pular upload)
  // skipUpload == string (UUID) = chamado direto do tour (prosseguir upload)
  const shouldSkipUpload = skipUpload === true;
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
            description: `Interagindo com a ferramenta...`,
            showButtons: [""],
          },
          onHighlighted: async (el) => {
            await new Promise((resolve) =>
              setTimeout(resolve, 1500),
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
          element: `#new-lesson-and-block`,
          popover: {
            description: "Digite Título da aula",
            onNextClick: async (el) => {
              const title = el.querySelector(
                `input.lesson-input-title`,
              )
              if (title.value.trim() === "") {
                alert("por favor preencha o campo título");
                return;
              }
              // await driverObj.moveNext();
            },
            onPopoverRender: (popover, { config, state }) => {
              const btnPreviews = popover.previousButton;
              const btnNext = popover.nextButton;
              if (btnNext) btnNext.style.display = "none";
              if (btnPreviews) btnPreviews.style.display = "none";

              const actions = AddButtonActions(
                popover,
                "Cancelar operação",
              );
              actions.addEventListener("click", () => {
                Livewire.dispatch("tour-teacher::close-modal-new-lesson", {
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

            btnCancel.addEventListener("click", toCloseActions);
            btnCreate.addEventListener("click", async () => {
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
              if (!shouldSkipUpload) {
                await new Promise((resolve) => setTimeout(resolve, 1000));
                await StartTourDisciplineCreateContentUploadBlock();
              }
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
    const waitForModuleCards = (timeout = 10000) => {
      return new Promise((resolve, reject) => {
        const modules = document.querySelectorAll("div.module-card");
        if (modules.length > 0) return resolve(modules);
        const observer = new MutationObserver(() => {
          const modules = document.querySelectorAll("div.module-card");
          if (modules.length > 0) {
            observer.disconnect();
            resolve(modules);
          }
        });
        observer.observe(document.body, { childList: true, subtree: true });
        setTimeout(() => {
          observer.disconnect();
          reject(new Error("Timeout aguardando module cards"));
        }, timeout);
      });
    };

    try {
      await waitForModuleCards();
    } catch (e) {
      console.error("Module cards não encontrados:", e);
      return;
    }

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

    const openModules = document.querySelectorAll('div.module-card[data-expanded="true"]');
    if(openModules.length > 0) {
        openModules.forEach(module => {
            const toggleBtn = module.querySelector('button[x-on\\:click*="isExpanded"]');
            if (toggleBtn) {
                toggleBtn.click();
            }
        });
    }

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

export async function StartTourCreateActivitTask() {
  let store = Alpine.store("tourguide");
  store.openMai = false;

  const modalContainer = document.querySelector("#create-activity-task");
  const modalIsOpen = modalContainer && modalContainer.offsetParent !== null;

  let disciplieID = modalContainer
    ? modalContainer.getAttribute("data-tour")
    : null;

  if (!disciplieID) {
    const parentWithTour = document.querySelector("[data-tour]");
    if (parentWithTour) {
      disciplieID = parentWithTour.getAttribute("data-tour");
    }
  }

  if (!disciplieID) return;

  return new Promise(async (resolve) => {
    if (!modalIsOpen) {
      const openTaskButton = document.querySelector("#content-task-create");
      if (!openTaskButton) return;

      const openDriver = drivertour({
        ...configDriver,
        steps: [
          {
            element: "#content-task-create",
            popover: {
              description:
                'Vou clicar no botão "Atividade / Avaliação" para abrir o criador de atividades.',
              showButtons: [""],
            },
            onHighlighted: async (el) => {
              await new Promise((r) => setTimeout(r, 1500));
              el.click();
              await new Promise((r) => setTimeout(r, 2000));
              await openDriver.destroy();
              await startTaskMainTour(disciplieID, store, resolve);
            },
          },
        ],
      });
      await openDriver.drive();
    } else {
      await startTaskMainTour(disciplieID, store, resolve);
    }
  });
}

async function startTaskMainTour(disciplieID, store, resolve) {
  const driverObj = drivertour({
    ...configDriver,
    steps: [
      {
        element: `#create-task-header-${disciplieID}`,
        popover: {
          description:
            "Bem-vindo ao criador de atividades! Aqui você pode criar questões para seus alunos. No painel esquerdo você configura cada questão, e no direito visualiza o resultado e finaliza a configuração.",
          onPopoverRender: (popover, { config, state }) => {
            const actions = AddButtonActions(
              popover,
              "Voltar ao início",
            );
            actions.addEventListener("click", () => {
              driverObj.destroy();
              store.openMai = true;
            });
          },
        },
      },
      {
        element: `#create-task-avaliative-${disciplieID}`,
        popover: {
          description:
            'Marque "Atividade avaliativa" se esta atividade terá nota. Ao ativar, campos de nota da atividade e nota por questão serão exibidos para que você atribua pontuações.',
          showButtons: [""],
          onPopoverRender: (popover, { config, state }) => {
            const actions = AddButtonActions(
              popover,
              "Voltar ao início",
            );
            actions.addEventListener("click", () => {
              driverObj.destroy();
              store.openMai = true;
            });
          },
        },
        onHighlighted: async (el) => {
          const checkbox = el.querySelector('input[type="checkbox"]');
          if (checkbox && !checkbox.checked) {
            checkbox.click();
            for (let i = 0; i < 20; i++) {
              const valueEl = document.querySelector(
                `#create-task-value-activity-${disciplieID}`,
              );
              if (valueEl) break;
              await new Promise((r) => setTimeout(r, 300));
            }
          }
          await driverObj.moveNext();
        },
      },
      {
        element: `#create-task-value-activity-${disciplieID}`,
        popover: {
          description:
            "Defina a nota total da atividade. Use o teclado numérico e o botão de backspace para corrigir. O valor máximo permitido é 100. A soma das notas de todas as questões não pode ultrapassar este valor.",
          onPopoverRender: (popover, { config, state }) => {
            const actions = AddButtonActions(
              popover,
              "Voltar ao início",
            );
            actions.addEventListener("click", () => {
              driverObj.destroy();
              store.openMai = true;
            });
          },
        },
      },
      {
        element: `#create-title-question-${disciplieID}`,
        popover: {
          description:
            "Digite aqui o enunciado da questão. Seja claro e objetivo para que o aluno entenda o que está sendo perguntado. Você pode usar Enter para quebras de linha.",
          onPopoverRender: (popover, { config, state }) => {
            const actions = AddButtonActions(
              popover,
              "Voltar ao início",
            );
            actions.addEventListener("click", () => {
              driverObj.destroy();
              store.openMai = true;
            });
          },
        },
      },
      {
        element: `#question-select-types-${disciplieID}`,
        popover: {
          description:
            'Escolha o tipo da questão: "Múltipla escolha" para questões com alternativas pré-definidas, ou "Dissertativa" para respostas textuais livres.',
          onPopoverRender: (popover, { config, state }) => {
            const actions = AddButtonActions(
              popover,
              "Voltar ao início",
            );
            actions.addEventListener("click", () => {
              driverObj.destroy();
              store.openMai = true;
            });
          },
        },
        onHighlighted: async (el) => {
          const radioTypeBtn = el.querySelector(
            `#question-select-radio-${disciplieID}`,
          );
          if (radioTypeBtn) {
            radioTypeBtn.click();
          }
        },
      },
      {
        element: `#create-question-option-${disciplieID}`,
        popover: {
          description:
            "Digite o texto de cada alternativa e pressione Enter ou clique no botão '+' ao lado para adicioná-la à lista. Crie no mínimo 2 opções para a questão de múltipla escolha.",
          onPopoverRender: (popover, { config, state }) => {
            const actions = AddButtonActions(
              popover,
              "Voltar ao início",
            );
            actions.addEventListener("click", () => {
              driverObj.destroy();
              store.openMai = true;
            });
          },
        },
      },
      {
        element: `#question-options-list-${disciplieID}`,
        popover: {
          description:
            "Aqui aparecem as opções que você criou. Você pode editar o texto diretamente no campo ou remover uma opção clicando no ícone da lixeira.",
          onPopoverRender: (popover, { config, state }) => {
            const actions = AddButtonActions(
              popover,
              "Voltar ao início",
            );
            actions.addEventListener("click", () => {
              driverObj.destroy();
              store.openMai = true;
            });
          },
        },
      },
      {
        element: `#create-task-question-value-${disciplieID}`,
        popover: {
          description:
            "Defina o valor/peso desta questão na nota final da atividade. A soma das notas de todas as questões não pode ultrapassar o valor total da atividade definido anteriormente.",
          onPopoverRender: (popover, { config, state }) => {
            const actions = AddButtonActions(
              popover,
              "Voltar ao início",
            );
            actions.addEventListener("click", () => {
              driverObj.destroy();
              store.openMai = true;
            });
          },
        },
      },
      {
        element: `#create-question-button-radio-${disciplieID}`,
        popover: {
          description:
            "Clique em 'Criar questão' para adicionar esta questão ao questionário. Após criada, ela aparecerá no painel direito de preview.",
          onPopoverRender: (popover, { config, state }) => {
            const actions = AddButtonActions(
              popover,
              "Voltar ao início",
            );
            actions.addEventListener("click", () => {
              driverObj.destroy();
              store.openMai = true;
            });
          },
        },
      },
      {
        element: `#create-task-preview-panel-${disciplieID}`,
        popover: {
          description:
            "Este é o painel de preview. Aqui você visualiza todas as questões criadas, acompanha o total de questões e gerencia cada uma delas.",
          onPopoverRender: (popover, { config, state }) => {
            const actions = AddButtonActions(
              popover,
              "Voltar ao início",
            );
            actions.addEventListener("click", () => {
              driverObj.destroy();
              store.openMai = true;
            });
          },
        },
      },
      {
        element: `#create-task-question-count-${disciplieID}`,
        popover: {
          description:
            "Aqui é exibida a quantidade total de questões já criadas no formulário.",
          onPopoverRender: (popover, { config, state }) => {
            const actions = AddButtonActions(
              popover,
              "Voltar ao início",
            );
            actions.addEventListener("click", () => {
              driverObj.destroy();
              store.openMai = true;
            });
          },
        },
      },
      {
        element: `#question-created-${disciplieID}`,
        popover: {
          description:
            "Cada questão criada aparece aqui. Você pode visualizar o enunciado, a nota (se avaliativa), e usar os botões 'editar' para alterar o texto ou 'remover' para excluir a questão.",
          onPopoverRender: (popover, { config, state }) => {
            const actions = AddButtonActions(
              popover,
              "Voltar ao início",
            );
            actions.addEventListener("click", () => {
              driverObj.destroy();
              store.openMai = true;
            });
          },
        },
        onHighlighted: async (el) => {
          await new Promise((r) => setTimeout(r, 1500));
          const editBtn = el.querySelector('[data-tour="edit-question"]');
          const removeBtn = el.querySelector(
            '[data-tour="remove-question"]',
          );
          if (editBtn) {
            editBtn.style.outline = "2px solid #ec4899";
            editBtn.style.outlineOffset = "2px";
          }
          if (removeBtn) {
            removeBtn.style.outline = "2px solid #ef4444";
            removeBtn.style.outlineOffset = "2px";
          }
        },
      },
      {
        element: `#question-created-${disciplieID}`,
        popover: {
          description:
            "Para questões de múltipla escolha, selecione a alternativa correta clicando no botão de rádio ao lado da opção desejada. Um ícone de check verde indicará a opção marcada como correta.",
          onPopoverRender: (popover, { config, state }) => {
            const actions = AddButtonActions(
              popover,
              "Voltar ao início",
            );
            actions.addEventListener("click", () => {
              driverObj.destroy();
              store.openMai = true;
            });
          },
        },
      },
      {
        element: `#create-question-associate-module-${disciplieID}`,
        popover: {
          description:
            "Selecione a aula à qual esta atividade será vinculada. Os alunos encontrarão a atividade dentro da aula escolhida. A seleção é obrigatória para salvar.",
          onPopoverRender: (popover, { config, state }) => {
            const actions = AddButtonActions(
              popover,
              "Voltar ao início",
            );
            actions.addEventListener("click", () => {
              driverObj.destroy();
              store.openMai = true;
            });
          },
        },
      },
      {
        element: `#create-question-dates-${disciplieID}`,
        popover: {
          description:
            "Defina o período de disponibilidade da atividade. A data de início é quando a atividade fica visível para os alunos, e a data final é o prazo máximo de entrega. Deixe em branco se não houver prazo.",
          onPopoverRender: (popover, { config, state }) => {
            const actions = AddButtonActions(
              popover,
              "Voltar ao início",
            );
            actions.addEventListener("click", () => {
              driverObj.destroy();
              store.openMai = true;
            });
          },
        },
      },
      {
        element: `#create-task-save-btn-${disciplieID}`,
        popover: {
          description:
            'Após criar todas as questões, selecionar a aula e configurar as datas, clique em "Criar questionário" para salvar tudo. A atividade será disponibilizada aos alunos na aula vinculada.',
          onPopoverRender: (popover, { config, state }) => {
            const actions = AddButtonActions(
              popover,
              "Voltar ao início",
            );
            actions.addEventListener("click", () => {
              driverObj.destroy();
              store.openMai = true;
            });
          },
        },
      },
    ],
  });

  await driverObj.drive();
  if (resolve) resolve();
}
