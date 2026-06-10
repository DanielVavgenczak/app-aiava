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
        await new Promise((resolve) => {
          let cleanup;
          const handler = (event) => {
            if (event.module_id == dataId) {
              if (typeof cleanup === "function") cleanup();
              window._lastLessonId = event.lesson_id;
              steps[6].element = `button[data-lesson-id="${event.lesson_id}"]`;
              steps[7].element = `#video-player-${event.lesson_id}`;
              setTimeout(resolve, 1500);
            }
          };
          cleanup = Livewire.on("after-video-upload", handler);
          setTimeout(() => {
            if (typeof cleanup === "function") cleanup();
            resolve();
          }, 3000);
        });

        await new Promise((resolve) => setTimeout(resolve, 1500));
        await updateOnedDriverObj.moveNext();
      },
    },
    {
      popover: {
        description: "Este é o vídeo que foi adicionado à aula.",
        side: "right",
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
        await new Promise((resolve) => setTimeout(resolve, 2000));
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
  await new Promise((resolve) => setTimeout(resolve, 1000));

  const steps = [
    {
      element: `#module-list-all`,
      popover: {
        description: `Clique na aula que deseja editar`,
        showButtons: [""],
        onPopoverRender: (popover, { config, state }) => {
          const nextBtn = popover.nextButton;
          const prevBtn = popover.previousButton;
          if (nextBtn) nextBtn.remove();
          if (prevBtn) prevBtn.remove();
          const actions = AddButtonActions(popover, "Cancelar operação");
          actions.addEventListener("click", async () => {
            await updateOnedDriverObj.destroy();
            store.openMai = true;
          });
        },
      },
      onHighlighted: async () => {
        await new Promise((resolve) => setTimeout(resolve, 1000));
        let moduleSelected = false;

        modulesCard.forEach((module) => {
          module.style.zIndex = "200000000";
          module.addEventListener("click", async (event) => {
            if (moduleSelected) return;
            event.preventDefault();
            moduleSelected = true;

            modulesCard.forEach((otherModule) => {
              if (module !== otherModule) {
                otherModule.style.zIndex = "0";
              }
            });

            const moduleId = module.getAttribute("data-tour");

            Livewire.dispatch("teacher::open-edit-module", {
              module_id: moduleId,
            });

            steps[1].element = `#module-edit-title-${moduleId}`;

            await new Promise((resolve) => setTimeout(resolve, 1500));

            await updateOnedDriverObj.moveNext();
          });
        });
      },
    },
    {
      popover: {
        description: `Edite o título da aula e clique em "Editar" para salvar as alterações.`,
        side: "bottom",
        align: "start",
        showButtons: [""],
        onPopoverRender: (popover, { config, state }) => {
          const nextBtn = popover.nextButton;
          const prevBtn = popover.previousButton;
          if (nextBtn) nextBtn.remove();
          if (prevBtn) prevBtn.remove();
          const actions = AddButtonActions(popover, "Voltar ao início");
          actions.addEventListener("click", async () => {
            await updateOnedDriverObj.destroy();
            store.openMai = true;
          });
        },
      },
      onHighlighted: async () => {
        await new Promise((resolve) => setTimeout(resolve, 5000));
        await updateOnedDriverObj.destroy();
      },
    },
  ];

  const updateOnedDriverObj = drivertour({
    ...configDriver,
    steps: [...steps],
  });

  await updateOnedDriverObj.drive();
}
