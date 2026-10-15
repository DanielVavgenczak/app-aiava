import {
  configDriver,
  AddButtonActions,
  addTourAction,
  waitForElement,
  waitForVideoPreview,
  waitForUploadComplete,
  whenElementHidden,
  sleep,
} from "../../utils";

let store;

// ─── TOUR DE VÍDEO ────────────────────────────────────────────
async function startVideoTour(dataId) {
  store = Alpine.store("tourguide");

  const moduleId = dataId;
  let lessonId = null;

  const cancelAction = (popover) =>
    addTourAction(popover, driverObj, {
      label: "Cancelar operação",
      onFinish: () =>
        Livewire.dispatch("tour-close-modal-upload_block", { open: false }),
    });

  const abort = async (error) => {
    console.warn(
      `Tour de upload de vídeo interrompido: ${error?.message ?? error}`,
    );
    await driverObj.destroy();
    store.openMai = true;
  };

  const steps = [
    {
      element: `#browser-file-${moduleId}`,
      popover: {
        description: `Clique para selecionar um arquivo de vídeo do seu computador.`,
        side: "top",
        align: "start",
        onPopoverRender: (popover) => cancelAction(popover),
      },
      onHighlighted: async () => {
        try {
          await waitForVideoPreview(moduleId, { timeout: 15000 });
          await sleep(100);
          await driverObj.moveNext();
        } catch (error) {
          await abort(error);
        }
      },
    },
    {
      element: `#temp-preview-${moduleId}`,
      popover: {
        description: `Confira o vídeo e clique em "Enviar" para fazer o upload.`,
        side: "top",
        align: "start",
        onPopoverRender: (popover) => cancelAction(popover),
      },
      onHighlighted: async () => {
        const btnSend = document.querySelector(
          `#lesson-send-video-block-content-${moduleId}`,
        );
        if (!btnSend) return;
        // `once` evita listeners duplicados quando o passo é revisitado.
        btnSend.addEventListener("click", () => driverObj.moveNext(), {
          once: true,
        });
      },
    },
    {
      element: `#progress-bar-container-${moduleId}`,
      popover: {
        description: "Aguarde enquanto o vídeo é processado e enviado.",
        onPopoverRender: (popover) => {
          cancelAction(popover);
          // Ao esconder a barra, recentraliza o popover. Usa `attributes: true`
          // (o observer antigo nunca disparava por omitir essa flag).
          const container = document.querySelector(
            `#progress-bar-container-${moduleId}`,
          );
          whenElementHidden(container, () => {
            const popoverEl = document.querySelector(".driver-popover");
            if (!popoverEl) return;
            popoverEl.style.position = "fixed";
            popoverEl.style.top = "50%";
            popoverEl.style.left = "50%";
            popoverEl.style.transform = "translate(-50%, -50%)";
          });
        },
      },
      onHighlighted: async () => {
        try {
          const event = await waitForUploadComplete(moduleId, {
            timeout: 60000,
          });
          lessonId = event.lesson_id;
          steps[3].element = `button[data-lesson-id="${lessonId}"]`;
          steps[4].element = `#video-player-${lessonId}`;
          await driverObj.moveNext();
        } catch (error) {
          await abort(error);
        }
      },
    },
    {
      element: `button[data-lesson-id="${lessonId}"]`,
      popover: {
        description: "Interagindo com a ferramenta...",
        showButtons: [],
      },
      onHighlighted: async () => {
        const btn = document.querySelector(
          `button[data-lesson-id="${lessonId}"]`,
        );
        if (btn) btn.click();
        await waitForElement(`#video-player-${lessonId}`, {
          timeout: 5000,
        }).catch(() => null);
        await sleep(1000);
        await driverObj.moveNext();
      },
    },
    {
      element: `#video-player-${lessonId}`,
      popover: {
        description: "Este é o vídeo que foi adicionado e está sendo reproduzido.",
        side: "top",
        align: "center",
        showButtons: [],
      },
      onHighlighted: async () => {
        await sleep(3000);
        await driverObj.destroy();
      },
    },
  ];

  const driverObj = drivertour({ ...configDriver, steps: [...steps] });
  await driverObj.drive();
}

// ─── TOUR DE PDF ────────────────────────────────────────────
async function startPdfTour(dataId) {
  store = Alpine.store("tourguide");
  const steps = [
    {
      element: `#pdf-upload-area-${dataId}`,
      popover: {
        description: `Clique para selecionar um arquivo PDF do seu computador.`,
        side: "top", align: "start",
        onPopoverRender: (popover) => {
          popover.previousButton?.remove();
          popover.nextButton?.remove();
          const actions = AddButtonActions(popover, "Cancelar operação");
          actions.addEventListener("click", async () => {
            Livewire.dispatch("tour-close-modal-upload_block", { open: false });
            await driverObj.destroy();
            store.openMai = true;
        });
        },
      },
      onHighlighted: async () => {
        const input = document.querySelector(`#pdf-upload-area-${dataId} input[type=file]`);
        if (!input) return;
        input.addEventListener("change", async () => {
          await new Promise(r => setTimeout(r, 300));
          await driverObj.moveNext();
        });
      },
    },
    {
      element: `#pdf-progress-${dataId}`,
      popover: {
        description: "Aguarde enquanto o PDF é enviado.",
        showButtons: [""],
        onPopoverRender: (popover) => {
          const actions = AddButtonActions(popover, "Cancelar operação");
          actions.addEventListener("click", async () => { await driverObj.destroy(); store.openMai = true; });
        },
      },
      onHighlighted: async () => {
        const targetEl = document.querySelector(`#pdf-progress-${dataId}`);
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
              steps[2].element = `button[data-lesson-id="${event.lesson_id}"]`;
              steps[3].element = `#pdf-viewer-${event.lesson_id}`;
              resolve();
            }
          };
          cleanup = Livewire.on("after-video-upload", handler);
          setTimeout(() => { if (typeof cleanup === "function") cleanup(); resolve(); }, 15000);
        });
        await driverObj.moveNext();
      },
    },
    {
      popover: {
        description: "PDF adicionado!",
        side: "right", align: "center", showButtons: [""],
       showButtons: [""],
      },
      onHighlighted: async () => {
        const btn = document.querySelector(`button[data-lesson-id="${window._lastLessonId}"]`);
        if (btn) btn.click();
        for (let i = 0; i < 50; i++) {
          if (document.querySelector(`#pdf-viewer-${window._lastLessonId}`)) break;
          await new Promise(r => setTimeout(r, 100));
        }
        await new Promise(r => setTimeout(r, 1000));
        await driverObj.moveNext();
      },
    },
    {
      popover: {
        description: "Este é o PDF que foi adicionado à aula.",
        showButtons: [""],
        onPopoverRender: (popover) => {
          const actions = AddButtonActions(popover, "Voltar ao início");
          actions.addEventListener("click", async () => { await driverObj.destroy(); store.openMai = true; });
        },
      },
      onHighlighted: async () => {
        await new Promise(r => setTimeout(r, 3000));
        await driverObj.destroy();
      },
    },
  ];
  const driverObj = drivertour({ ...configDriver, steps: [...steps] });
  await driverObj.drive();
}

// ─── TOUR DE LINK ────────────────────────────────────────────
async function startLinkTour(dataId) {
    store = Alpine.store("tourguide");

    let driverObj = null;
    let newLessonId = null;

    // Step 1: Input do link
    const step1 = {
        element: `#link-input-group-${dataId}`,
        popover: {
            description: `Digite ou cole o link externo e clique em "Salvar".`,
            side: "top", align: "start",
            onPopoverRender: (popover) => {
                popover.previousButton?.remove();
                popover.nextButton?.remove();
                const actions = AddButtonActions(popover, "Cancelar operação");
                actions.addEventListener("click", async () => {
                    Livewire.dispatch("tour-close-modal-upload_block", { open: false });
                    await driverObj.destroy();
                    store.openMai = true;
                });
            },
        },
        onHighlighted: async () => {
            const btn = document.querySelector(`#link-save-btn-${dataId}`);
            if (!btn) return;

            btn.addEventListener("click", async (e) => {
                // Espera o Livewire processar
                await new Promise(r => setTimeout(r, 1500));

                // Pega o último botão criado
                const allButtons = document.querySelectorAll(`[data-lesson-id]`);
                const lastButton = allButtons[allButtons.length - 1];
                newLessonId = lastButton?.dataset.lessonId;

                if (newLessonId) {
                    // Destroi o tour atual
                    await driverObj.destroy();
                    await new Promise(r => setTimeout(r, 500));
                    // Cria novo tour com o ID correto
                    const newSteps = [
                        {
                            element: `#lesson-btn-${newLessonId}`,
                            popover: {
                                description: "✅ Bloco adicionado com sucesso!",
                                showButtons: [""],
                            },
                            onHighlighted: async (el) => {
                                await new Promise(r => setTimeout(r, 1000));
                                await el.click();
                                // Aqui você pode adicionar mais ações
                                 await driverObj.moveNext();
                            }
                        },
                        {
                            element: `#link-viewer-${newLessonId}`,
                            popover: {
                                description: "Este é o link que foi adicionado à aula.",
                            showButtons: [""],
                            },
                            onHighlighted: async (el) => {
                                await new Promise(r => setTimeout(r, 3000));
                                await driverObj.destroy();
                            }
                        }
                    ];

                    const updatedriverObj = drivertour({
                        ...configDriver,
                        steps: newSteps,
                        start: true
                    });
                    await updatedriverObj.drive();
                }
            });
        },
    };

    driverObj = drivertour({ ...configDriver, steps: [step1] });
    await driverObj.drive();
}
// ─── TOUR PRINCIPAL DE UPLOAD ────────────────────────────────
export async function ContinueUploadBlock(modules, dataId) {
  store = Alpine.store("tourguide");
  let currentTour = null;
  const steps = [
    {
      element: `#accordion-module-${dataId}`,
      popover: {
        description: `Interagindo com a ferramenta...`,
        side: "bottom", align: "start", showButtons: [""],
      },
      onHighlighted: async (el) => {
        await new Promise(r => setTimeout(r, 2000));
        await driverObj.moveNext();
      },
    },
    {
      element: `#open-accordion-block-${dataId}`,
      popover: {
        description: `Interagindo com a ferramenta...`,
        side: "bottom", align: "start", showButtons: [""],
      },
      onHighlighted: async (el) => {
        await new Promise(r => setTimeout(r, 2000));
        await el.click();
        await new Promise(r => setTimeout(r, 1000));
        await driverObj.moveNext();
      },
    },
    {
      element: `#block-open-new-block-${dataId}`,
      popover: {
        description: `Interagindo com a ferramenta...`,
        side: "right", align: "center", showButtons: [""],
      },
      onHighlighted: async (el) => {
        await new Promise(r => setTimeout(r, 800));
        await el.click();
        await new Promise(r => setTimeout(r, 800));
        try { await waitForElement(`#option-block-${dataId}`, { timeout: 1000 }); }
        catch (e) { console.error("Modal não renderizou a tempo:", e); }
        await new Promise(r => setTimeout(r, 1000));
        await driverObj.moveNext();
      },
    },
    // {
    //   element: `#option-block-${dataId}`,
    //   popover: {
    //     description: `Interagindo com a ferramenta...`,
    //     side: "right", align: "center", showButtons: [""],
    //   },
    //   onHighlighted: async (el) => {
    //     await new Promise(r => setTimeout(r, 1000));
    //     await driverObj.moveNext();
    //   },
    // },
    {
      element: `.options-${dataId}`,
      popover: {
        description: `Selecione o tipo de mídia que deseja adicionar.`,
        side: "top", align: "start",
        onPopoverRender: (popover) => {
          popover.nextButton?.remove();
          popover.previousButton?.remove();
          const actions = AddButtonActions(popover, "Parar operação");
          actions.addEventListener("click", async () => {
            Livewire.dispatch("tour-close-modal-upload_block", { open: false });
            await driverObj.destroy();
            store.openMai = true;
          });
        },
      },
      onHighlighted: async (el) => {
        await new Promise(r => setTimeout(r, 1000));
        const tabs = el.querySelectorAll(`.options-${dataId} a div.whitespace-nowrap`);
        tabs.forEach((tab) => {
          tab.addEventListener("click", async (e) => {
            const tabText = e.target.textContent.trim();
            await new Promise(r => setTimeout(r, 500));
            if (tabText === "Video") {
              await driverObj.destroy();
              currentTour = await startVideoTour(dataId);
              return;
            } else if (tabText === "Texto") {
              await driverObj.destroy();
              currentTour = await startPdfTour(dataId);
              return;
            } else if (tabText === "Link") {
              await driverObj.destroy();
              currentTour = await startLinkTour(dataId);
              return;
            }
          });
        });
      },
    },
  ];

  const driverObj = drivertour({ ...configDriver, steps: [...steps] });
  await driverObj.drive();
}

export async function StartEditLesson() {
  store = Alpine.store("tourguide");
  store.openMai = false;

  const modulesCard = document.querySelectorAll("div.module-card");
  await new Promise(r => setTimeout(r, 1000));
  const openModules = document.querySelectorAll('div.module-card[data-expanded="true"]');
  if (openModules.length > 0) {
    openModules.forEach(module => {
      const toggleBtn = module.querySelector('button[x-on\\:click*="isExpanded"]');
      if (toggleBtn) toggleBtn.click();
    });
  }
  let driverObj;

  const buildSequentialSteps = (moduleId) => {
    return [
      {
        element: `#accordion-${moduleId}`,
        popover: { description: `Interagindo com a ferramenta...`, side: "bottom", align: "start", showButtons: [""] },
        onHighlighted: async (el) => { await new Promise(r => setTimeout(r, 2000)); el.style.zIndex = "0"; await driverObj.moveNext(); },
      },
      {
        element: `#accordion-module-${moduleId}`,
        popover: { description: `Interagindo com a ferramenta...`, side: "bottom", align: "start", showButtons: [""] },
        onHighlighted: async (el) => { await new Promise(r => setTimeout(r, 1500)); await driverObj.moveNext(); },
      },
      {
        element: `.btn-set-dropdown-${moduleId}`,
        popover: { description: `Interagindo com a ferramenta...`, showButtons: [""] },
        onHighlighted: async (el) => { await new Promise(r => setTimeout(r, 500)); el.click(); await new Promise(r => setTimeout(r, 500)); await driverObj.moveNext(); },
      },
      {
        element: `.content-lesson-edit-btn-${moduleId}`,
        popover: { description: `Interagindo com a ferramenta...`, side: "right", align: "center", showButtons: [""] },
        onHighlighted: async (el) => { await new Promise(r => setTimeout(r, 1000)); el.click(); await new Promise(r => setTimeout(r, 1000)); await driverObj.moveNext(); },
      },
      {
        element: `#edit-module-${moduleId}`,
        popover: {
          description: `Edite o título da aula e clique em 'Cancelar' para desistir ou 'Editar' para salvar`,
          side: "top", align: "start",
          onPopoverRender: (popover) => {
            popover.nextButton?.remove();
            const actions = AddButtonActions(popover, "Cancelar operação");
            actions.addEventListener("click", async () => { Livewire.dispatch("tour::clear-edit-lesson-modal"); await driverObj.destroy(); store.openMai = true; });
          },
        },
        onHighlighted: async (el) => {
          const cancelButton = el.querySelector(`#module-edit-cancel-${moduleId}`);
          const save = el.querySelector(`#module-edit-send-${moduleId}`);
          const close = async () => { Livewire.dispatch("tour::clear-edit-lesson-modal"); await driverObj.destroy(); store.openMai = true; };
          if (cancelButton) cancelButton.addEventListener("click", close);
          if (save) save.addEventListener("click", close);
        },
      },
    ];
  };

  const startTour = async (moduleId) => {
    driverObj = drivertour({ ...configDriver, steps: buildSequentialSteps(moduleId) });
    await driverObj.drive();
  };

  if (modulesCard.length === 1) {
    const moduleId = modulesCard[0].getAttribute("data-tour");
    await startTour(moduleId);
    return;
  }

  driverObj = drivertour({
    ...configDriver,
    steps: [
      {
        element: `#module-list-all`,
        popover: {
          description: `Selecione a aula que deseja editar`,
          onPopoverRender: (popover) => {
            popover.nextButton?.remove();
            popover.previousButton?.remove();
            const actions = AddButtonActions(popover, "Cancelar operação");
            actions.addEventListener("click", async () => { driverObj.destroy(); modulesCard.forEach(item => item.style.zIndex = "0"); store.openMai = true; });
          },
        },
        onHighlighted: async () => {
          let moduleSelected = false;
          modulesCard.forEach(module => {
            module.style.zIndex = "200000000";
            module.addEventListener("click", async (event) => {
              if (moduleSelected) return;
              event.preventDefault();
              moduleSelected = true;
              modulesCard.forEach(other => { if (module !== other) other.style.zIndex = "0"; });
              const moduleId = module.getAttribute("data-tour");
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
  store = Alpine.store("tourguide");
  store.openMai = false;

  const modulesCard = document.querySelectorAll("div.module-card");
  await new Promise(r => setTimeout(r, 1000));
  let driverObj;

  const buildDeleteSteps = (moduleId) => {
    return [
      {
        element: `#accordion-${moduleId}`,
        popover: { description: `Interagindo com a ferramenta...`, side: "bottom", align: "start", showButtons: [""] },
        onHighlighted: async (el) => { await new Promise(r => setTimeout(r, 2000)); el.style.zIndex = "0"; await driverObj.moveNext(); },
      },
      {
        element: `#accordion-module-${moduleId}`,
        popover: { description: `Interagindo com a ferramenta...`, side: "bottom", align: "start", showButtons: [""] },
        onHighlighted: async (el) => { await new Promise(r => setTimeout(r, 1500)); await driverObj.moveNext(); },
      },
      {
        element: `.btn-set-dropdown-${moduleId}`,
        popover: { description: `Interagindo com a ferramenta...`, showButtons: [""] },
        onHighlighted: async (el) => { await new Promise(r => setTimeout(r, 500)); el.click(); await new Promise(r => setTimeout(r, 500)); await driverObj.moveNext(); },
      },
      {
        element: `.content-lesson-delete-btn-${moduleId}`,
        popover: { description: `Interagindo com a ferramenta...`, side: "right", align: "center", showButtons: [""] },
        onHighlighted: async (el) => { await new Promise(r => setTimeout(r, 1000)); el.click(); await new Promise(r => setTimeout(r, 1000)); await driverObj.moveNext(); },
      },
      {
        element: `#module-delete-lesson-${moduleId}`,
        popover: {
          description: `Clique em 'Cancelar' para desistir ou 'Deletar' para confirmar a exclusão`,
          side: "top", align: "start",
          onPopoverRender: (popover) => {
            popover.nextButton?.remove();
            const actions = AddButtonActions(popover, "Cancelar operação");
            actions.addEventListener("click", async () => { Livewire.dispatch("tour::close-delete-lesson-modal"); await driverObj.destroy(); store.openMai = true; });
          },
        },
        onHighlighted: async (el) => {
          const cancelButton = el.querySelector('[wire\\:click="cancelEdit"]');
          const deleteBtn = el.querySelector('[wire\\:click="deleteModule"]');
          const close = async () => { await driverObj.destroy(); store.openMai = true; };
          if (cancelButton) cancelButton.addEventListener("click", close);
          if (deleteBtn) deleteBtn.addEventListener("click", close);
        },
      },
    ];
  };

  const startDeleteTour = async (moduleId) => {
    driverObj = drivertour({ ...configDriver, steps: buildDeleteSteps(moduleId) });
    await driverObj.drive();
  };

  const openModules = document.querySelectorAll('div.module-card[data-expanded="true"]');
  if (openModules.length > 0) {
    openModules.forEach(module => {
      const toggleBtn = module.querySelector('button[x-on\\:click*="isExpanded"]');
      if (toggleBtn) toggleBtn.click();
    });
  }

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
          onPopoverRender: (popover) => {
            popover.nextButton?.remove();
            popover.previousButton?.remove();
            const actions = AddButtonActions(popover, "Cancelar operação");
            actions.addEventListener("click", async () => { driverObj.destroy(); modulesCard.forEach(item => item.style.zIndex = "0"); store.openMai = true; });
          },
        },
        onHighlighted: async () => {
          let moduleSelected = false;
          modulesCard.forEach(module => {
            module.style.zIndex = "200000000";
            module.addEventListener("click", async (event) => {
              if (moduleSelected) return;
              event.preventDefault();
              moduleSelected = true;
              modulesCard.forEach(other => { if (module !== other) other.style.zIndex = "0"; });
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
