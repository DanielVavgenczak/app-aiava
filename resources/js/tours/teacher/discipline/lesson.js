import { configDriver, AddButtonActions } from "../../utils";

export async function ContinueUploadBlock(modules, dataId) {
 let store = Alpine.store("tourguide");
  // Função utilitária para esperar um elemento estar presente no DOM
 const waitForElement = (selector, timeout = 3000) => {
    return new Promise((resolve, reject) => {
      const element = document.querySelector(selector);
      if (element) {
        return resolve(element);
      }

      const observer = new MutationObserver((mutations, obs) => {
        const element = document.querySelector(selector);
        if (element) {
          obs.disconnect();
          resolve(element);
        }
      });

      observer.observe(document.body, {
        childList: true,
        subtree: true
      });

      // Timeout de segurança
      setTimeout(() => {
        observer.disconnect();
        reject(new Error(`Elemento ${selector} não encontrado após ${timeout}ms`));
      }, timeout);
    });
  };

  const steps = [
    {
      element: `#accordion-module-${dataId}`,
      popover: {
        description: `Interagindo com a ferramenta...`,
        side: "bottom",
        align: "start",
        showButtons: [""],
      },
      onHighlighted: async (el) => {
        await new Promise((resolve) => setTimeout(resolve, 2000));
        await updateOnedDriverObj.moveNext();
      },
    },
    {
      element: `#open-accordion-block-${dataId}`,
      popover: {
        description: `Interagindo com a ferramenta...`,
        side: "bottom",
        align: "start",
        showButtons: [""],
      },
      onHighlighted: async (el) => {
        await new Promise((resolve) => setTimeout(resolve, 2000));
        //const btnOpenAccordion = document.querySelector(`#open-accordion-block-${dataId}`);
        await el.click();
        // Aguarda o accordion expandir
        await new Promise((resolve) => setTimeout(resolve, 1000));
        await updateOnedDriverObj.moveNext();
      },
    },
    {
      element: `#block-open-new-block-${dataId}`,
      popover: {
        description: `Interagindo com a ferramenta...`,
        side: "right",
        align: "center",
        showButtons: [""],
      },
      onHighlighted: async (el) => {
        await new Promise((resolve) => setTimeout(resolve, 2000));
        await el.click();
        await new Promise((resolve) => setTimeout(resolve, 1000));
        // Aguarda o modal de upload renderizar antes de prosseguir
        try {
          await waitForElement(`#browser-file-${dataId}`, 2000);
        } catch (error) {
          console.error("Modal de upload não renderizou a tempo:", error);
        }
        await new Promise((resolve) => setTimeout(resolve, 500));
        await updateOnedDriverObj.moveNext();
      },
    },
    {
      element: `#browser-file-${dataId}`,
      popover: {
        description: `Clique no ícone de vídeo para adicionar um novo bloco`,
        side: "top",
        align: "start",
        // showButtons: [""],
        onPopoverRender: (popover, { config, state }) => {
            const nextBtn = popover.nextButton;
            const prevBtn = popover.previousButton;
            if (nextBtn) nextBtn.remove();  // remove do DOM
            if (prevBtn) prevBtn.remove();
              const actions = AddButtonActions(
                popover,
                "Parar operação",
              );
              actions.addEventListener("click", async () => {
                Livewire.dispatch("tour-close-modal-upload_block", {
                  open: false,
                });
                await updateOnedDriverObj.destroy();
                store.openMai = true;
              });
            }
      },
      onHighlighted: async (el) => {
        await new Promise((resolve) => setTimeout(resolve, 2000));

        // Não clica programaticamente: browser bloqueia input.click()
        // após um await (perde user activation).
        // O step já instrui o usuário a clicar manualmente.

        const videoElement = document.querySelector(`#temp-video-preview-${dataId}`);
        if (!videoElement) return;

        if (videoElement.src && videoElement.src !== "") {
          await updateOnedDriverObj.moveNext();
          return;
        }

        const observer = new MutationObserver(async (mutations) => {
          for (const mutation of mutations) {
            if (
              mutation.type === "attributes" &&
              mutation.attributeName === "src"
            ) {
              if (videoElement.src && videoElement.src !== "") {
                observer.disconnect();
                await new Promise((resolve) => setTimeout(resolve, 100));
                console.log("Quando bater aqui ele vai sozinho para o todo")
                await updateOnedDriverObj.moveNext();
              }
            }
          }
        });

        observer.observe(videoElement, {
          attributes: true,
          attributeFilter: ["src"],
        });

        // setTimeout(() => {
        //   observer.disconnect();
        //   console.warn("Timeout aguardando seleção de vídeo");
        // }, 1000);
      },
    },
    {
      element: `#temp-preview-${dataId}`,
      popover: {
        description: `Confira o vídeo e clique em "Enviar" para fazer o upload.`,
        side: "top",
        align: "start",
        onPopoverRender: (popover, { config, state }) => {
         const prevBtn = popover.previousButton;
         const nextBtn = popover.nextButton;
         if (nextBtn) nextBtn.remove();
         if (prevBtn) prevBtn.remove();
         const actions = AddButtonActions(popover, "Cancelar operação");
         actions.addEventListener("click", async () => {
            await updateOnedDriverObj.destroy();
            store.openMai = true;
          });
        },
      },
      onHighlighted: async (el) => {
        const btnSend = document.querySelector(
          `#lesson-send-video-block-content-${dataId}`
        );
        if (!btnSend) return;

        btnSend.addEventListener("click", async () => {
          await new Promise((r) => setTimeout(r, 300));
          await updateOnedDriverObj.moveNext();
        });
      },
    },
    {
      element: `#progress-bar-container-${dataId}`,
      popover: {
        description: "Aguarde enquanto o vídeo é processado e enviado.",
        showButtons: [""],
        onPopoverRender: (popover, { config, state }) => {
          const actions = AddButtonActions(popover, "Cancelar operação");
          actions.addEventListener("click", async () => {
            await updateOnedDriverObj.destroy();
            store.openMai = true;
          });
        }
      },
      onHighlighted: async () => {
        const targetEl = document.querySelector(`#progress-bar-container-${dataId}`);
        if (targetEl) {
          const obs = new MutationObserver(() => {
            if (targetEl.style.display === 'none') {
              const popoverEl = document.querySelector('.driver-popover');
              if (popoverEl) {
                popoverEl.style.position = 'fixed';
                popoverEl.style.top = '50%';
                popoverEl.style.left = '50%';
                popoverEl.style.transform = 'translate(-50%, -50%)';
              }
              obs.disconnect();
            }
          });
          obs.observe(targetEl, { attributeFilter: ['style'] });
        }

        await new Promise((resolve) => {
          let cleanup;
          const handler = (event) => {
            if (event.module_id == dataId) {
              if (typeof cleanup === "function") cleanup();
              window._lastLessonId = event.lesson_id;
              steps[6].element = `button[data-lesson-id="${event.lesson_id}"]`;
              steps[7].element = `#video-player-${event.lesson_id}`;
              resolve();
            }
          };
          cleanup = Livewire.on("after-video-upload", handler);
          setTimeout(() => {
            if (typeof cleanup === "function") cleanup();
            resolve();
          }, 15000);
        });

        await updateOnedDriverObj.moveNext();
      },
    },
    {
      popover: {
        description: "Clique no botão da aula para carregar o vídeo.",
        side: "right",
        align: "center",
        showButtons: [""],
        onPopoverRender: (popover, { config, state }) => {
          const actions = AddButtonActions(popover, "Cancelar opera");
          actions.addEventListener("click", async () => {
            await updateOnedDriverObj.destroy();
            store.openMai = true;
          });
        },
      },
      onHighlighted: async (element) => {
        // Clica no botão da aula para disparar load-video-block
        element.click();

        // Aguarda o player de vídeo renderizar no DOM
        const lessonId = window._lastLessonId;
        if (lessonId) {
          const playerSelector = `#video-player-${lessonId}`;
          for (let i = 0; i < 50; i++) {
            if (document.querySelector(playerSelector)) break;
            await new Promise((r) => setTimeout(r, 100));
          }
        }

        await new Promise((resolve) => setTimeout(resolve, 1000));
        await updateOnedDriverObj.moveNext();
      },
    },
    {
      popover: {
        description: "Este é o vídeo que foi adicionado e está sendo reproduzido.",
        side: "top",
        align: "center",
        showButtons: [""],
        onPopoverRender: (popover, { config, state }) => {
          const actions = AddButtonActions(popover, "Voltar ao início");
          actions.addEventListener("click", async () => {
            await updateOnedDriverObj.destroy();
            store.openMai = true;
          });
        },
      },
      onHighlighted: async () => {
        await new Promise((resolve) => setTimeout(resolve, 3000));
        await updateOnedDriverObj.destroy();
      },
    },
  ];

  // Cria um novo tour com os passos atualizados
  const updateOnedDriverObj = drivertour({
    ...configDriver,
    steps: [...steps],
  });

  await updateOnedDriverObj.drive();
  return;
}

export async function StartEditLesson() {
  let store = Alpine.store("tourguide");
  store.openMai = false;

  const modulesCard = document.querySelectorAll("div.module-card");
  await new Promise((resolve) => setTimeout(resolve), 1000);

  let driverObj;

  const buildSequentialSteps = (moduleId) => {
    const formActionsId = `module-edit-form-actions-${moduleId}`;
    return [
      {
        element: `#accordion-${moduleId}`,
        popover: {
          description: `Interagindo com a ferramenta...`,
          side: "bottom",
          align: "start",
          showButtons: [""],
        },
        onHighlighted: async (el) => {
          await new Promise((r) => setTimeout(r, 2000));
          el.style.zIndex = "0";
          await driverObj.moveNext();
        },
      },
      {
        element: `#accordion-module-${moduleId}`,
        popover: {
          description: `Abrindo menu de opções`,
          side: "bottom",
          align: "start",
          showButtons: [""],
        },
        onHighlighted: async (el) => {
          await new Promise((r) => setTimeout(r, 1500));
         // el.click();
          await driverObj.moveNext();
        },
      },
      {
        element: `.btn-set-dropdown-${moduleId}`,
        popover: {
            description: `Interagindo com a ferramenta...`,
            showButtons:[""]
        },
        onHighlighted: async (el) => {
          await new Promise((r) => setTimeout(r, 500));
          el.click();
          await new Promise((r) => setTimeout(r, 500));
          await driverObj.moveNext();
        }
      },
      {
        element: `.content-lesson-edit-btn-${moduleId}`,
        popover: {
          description: `Interagindo com a ferramenta...`,
          side: "right",
          align: "center",
          showButtons: [""],
        },
        onHighlighted: async (el) => {
          await new Promise((r) => setTimeout(r, 1000));
          el.click();
          await new Promise((r) => setTimeout(r, 1000));
          await driverObj.moveNext();
        },
      },
      {
        element: `#edit-module-${moduleId}`,
        popover: {
          description: `Edite o título da aula e clique em 'Cancelar' para desistir ou 'Editar' para salvar`,
          side: "top",
          align: "start",
          onPopoverRender: (popover, { config, state }) => {
            const nextBtn = popover.nextButton;
            const prevBtn = popover.previousButton;
            if (nextBtn) nextBtn.remove(); // remove do DOM
            const actions = AddButtonActions(popover, "Cancelar operação");
            actions.addEventListener("click", async () => {
              await driverObj.destroy();
              store.openMai = true;
            });
          },
        },
        onHighlighted: async (el) => {
          const cancelButton = el.querySelector(
            `#module-edit-cancel-${moduleId}`,
          );
          const save = el.querySelector(
            `#module-edit-send-${moduleId}`,
          );
          const close = async () => {
            await driverObj.destroy();
            store.openMai = true;
          };
          if (cancelButton) cancelButton.addEventListener("click", close);
          if (save) save.addEventListener("click", close);
        },
      },
    ];
  };

  const startTour = async (moduleId) => {
    driverObj = drivertour({
      ...configDriver,
      steps: buildSequentialSteps(moduleId),
    });
    await driverObj.drive();
  };

  // Apenas UMA aula: tour sequencial sem interação do professor
  if (modulesCard.length === 1) {
    const moduleId = modulesCard[0].getAttribute("data-tour");
    console.log("Module id achado", moduleId)
    await startTour(moduleId);
    return;
  }

  // Múltiplas aulas: professor seleciona qual editar
  driverObj = drivertour({
    ...configDriver,
    steps: [
      {
        element: `#module-list-all`,
        popover: {
          description: `Selecione a aula que deseja editar`,
          onPopoverRender: (popover, { config, state }) => {
            const nextBtn = popover.nextButton;
            const prevBtn = popover.previousButton;
            if (nextBtn) nextBtn.remove();
            if (prevBtn) prevBtn.remove();
            const actions = AddButtonActions(popover, "Cancelar operação");
            actions.addEventListener("click", async () => {
              driverObj.destroy();
              modulesCard.forEach((item) => (item.style.zIndex = "0"));
              store.openMai = true;
            });
          },
        },
        onHighlighted: async () => {
          let moduleSelected = false;
          modulesCard.forEach((module) => {
            module.style.zIndex = "200000000";
            module.addEventListener("click", async (event) => {
              if (moduleSelected) return;
              event.preventDefault();
              moduleSelected = true;
              modulesCard.forEach((other) => {
                if (module !== other) other.style.zIndex = "0";
              });
              const moduleId = module.getAttribute("data-tour");
              console.log("O module id com mais de 1 opção aqui", moduleId)
              //await driverObj.destroy();
              await startTour(moduleId);
            });
          });
        },
      },
    ],
  });
  return await driverObj.drive();
}

export async function StartDeleteLesson() {
  let store = Alpine.store("tourguide");
  store.openMai = false;

  const modulesCard = document.querySelectorAll("div.module-card");
  await new Promise((resolve) => setTimeout(resolve), 1000);

  let driverObj;

  const buildDeleteSteps = (moduleId) => {
    return [
      {
        element: `#accordion-${moduleId}`,
        popover: {
          description: `Interagindo com a ferramenta...`,
          side: "bottom",
          align: "start",
          showButtons: [""],
        },
        onHighlighted: async (el) => {
          await new Promise((r) => setTimeout(r, 2000));
          el.style.zIndex = "0";
          await driverObj.moveNext();
        },
      },
      {
        element: `#accordion-module-${moduleId}`,
        popover: {
          description: `Abrindo menu de opções`,
          side: "bottom",
          align: "start",
          showButtons: [""],
        },
        onHighlighted: async (el) => {
          await new Promise((r) => setTimeout(r, 1500));
          await driverObj.moveNext();
        },
      },
      {
        element: `.btn-set-dropdown-${moduleId}`,
        popover: {
          description: `Interagindo com a ferramenta...`,
          showButtons: [""],
        },
        onHighlighted: async (el) => {
          await new Promise((r) => setTimeout(r, 500));
          el.click();
          await new Promise((r) => setTimeout(r, 500));
          await driverObj.moveNext();
        },
      },
      {
        element: `.btn-to-delete`,
        popover: {
          description: `Interagindo com a ferramenta...`,
          side: "right",
          align: "center",
          showButtons: [""],
        },
        onHighlighted: async (el) => {
          await new Promise((r) => setTimeout(r, 1000));
          el.click();
          await new Promise((r) => setTimeout(r, 1000));
          await driverObj.moveNext();
        },
      },
      {
        element: `#module-delete-actions-${moduleId}`,
        popover: {
          description: `Clique em 'Cancelar' para desistir ou 'Deletar' para confirmar a exclusão`,
          side: "top",
          align: "start",
          onPopoverRender: (popover, { config, state }) => {
            const nextBtn = popover.nextButton;
            const prevBtn = popover.previousButton;
            if (nextBtn) nextBtn.remove();
            const actions = AddButtonActions(popover, "Cancelar operação");
            actions.addEventListener("click", async () => {
              await driverObj.destroy();
              store.openMai = true;
            });
          },
        },
        onHighlighted: async (el) => {
          const cancelButton = el.querySelector('[wire\\:click="cancelEdit"]');
          const deleteBtn = el.querySelector('[wire\\:click="deleteModule"]');
          const close = async () => {
            await driverObj.destroy();
            store.openMai = true;
          };
          if (cancelButton) cancelButton.addEventListener("click", close);
          if (deleteBtn) deleteBtn.addEventListener("click", close);
        },
      },
    ];
  };

  const startDeleteTour = async (moduleId) => {
    driverObj = drivertour({
      ...configDriver,
      steps: buildDeleteSteps(moduleId),
    });
    await driverObj.drive();
  };

  if (modulesCard.length === 1) {
    const moduleId = modulesCard[0].getAttribute("data-tour");
    await startDeleteTour(moduleId);
    return;
  }

  driverObj = drivertour({
    ...configDriver,
    steps: [
      {
        element: `#module-list-all`,
        popover: {
          description: `Selecione a aula que deseja deletar`,
          onPopoverRender: (popover, { config, state }) => {
            const nextBtn = popover.nextButton;
            const prevBtn = popover.previousButton;
            if (nextBtn) nextBtn.remove();
            if (prevBtn) prevBtn.remove();
            const actions = AddButtonActions(popover, "Cancelar operação");
            actions.addEventListener("click", async () => {
              driverObj.destroy();
              modulesCard.forEach((item) => (item.style.zIndex = "0"));
              store.openMai = true;
            });
          },
        },
        onHighlighted: async () => {
          let moduleSelected = false;
          modulesCard.forEach((module) => {
            module.style.zIndex = "200000000";
            module.addEventListener("click", async (event) => {
              if (moduleSelected) return;
              event.preventDefault();
              moduleSelected = true;
              modulesCard.forEach((other) => {
                if (module !== other) other.style.zIndex = "0";
              });
              const moduleId = module.getAttribute("data-tour");
              await startDeleteTour(moduleId);
            });
          });
        },
      },
    ],
  });
  return await driverObj.drive();
}
