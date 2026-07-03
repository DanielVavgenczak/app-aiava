import { configDriver, AddButtonActions } from "../../utils";

let store;

const waitForElement = (selector, timeout = 3000) => {
  return new Promise((resolve, reject) => {
    const element = document.querySelector(selector);
    if (element) return resolve(element);
    const observer = new MutationObserver((mutations, obs) => {
      const el = document.querySelector(selector);
      if (el) { obs.disconnect(); resolve(el); }
    });
    observer.observe(document.body, { childList: true, subtree: true });
    setTimeout(() => { observer.disconnect(); reject(new Error(`Elemento ${selector} não encontrado após ${timeout}ms`)); }, timeout);
  });
};

// ─── TOUR DE VÍDEO ────────────────────────────────────────────
async function startVideoTour(dataId) {
  store = Alpine.store("tourguide");
  const steps = [
    {
      element: `#browser-file-${dataId}`,
      popover: {
        description: `Clique para selecionar um arquivo de vídeo do seu computador.`,
        side: "top", align: "start",
        onPopoverRender: (popover) => {
          popover.previousButton?.remove();
          popover.nextButton?.remove();
          const actions = AddButtonActions(popover, "Cancelar operação");
          actions.addEventListener("click", async () => { await driverObj.destroy(); store.openMai = true; });
        },
      },
      onHighlighted: async () => {
        const videoEl = document.querySelector(`#temp-video-preview-${dataId}`);
        if (!videoEl) return;
        if (videoEl.src && videoEl.src !== "") { await driverObj.moveNext(); return; }
        const observer = new MutationObserver(async (mutations) => {
          for (const m of mutations) {
            if (m.type === "attributes" && m.attributeName === "src" && videoEl.src) {
              observer.disconnect();
              await new Promise(r => setTimeout(r, 100));
              await driverObj.moveNext();
            }
          }
        });
        observer.observe(videoEl, { attributes: true, attributeFilter: ["src"] });
      },
    },
    {
      element: `#temp-preview-${dataId}`,
      popover: {
        description: `Confira o vídeo e clique em "Enviar" para fazer o upload.`,
        side: "top", align: "start",
        onPopoverRender: (popover) => {
          popover.previousButton?.remove();
          popover.nextButton?.remove();
          const actions = AddButtonActions(popover, "Cancelar operação");
          actions.addEventListener("click", async () => { await driverObj.destroy(); store.openMai = true; });
        },
      },
      onHighlighted: async () => {
        const btnSend = document.querySelector(`#lesson-send-video-block-content-${dataId}`);
        if (!btnSend) return;
        btnSend.addEventListener("click", async () => {
          await new Promise(r => setTimeout(r, 300));
          await driverObj.moveNext();
        });
      },
    },
    {
      element: `#progress-bar-container-${dataId}`,
      popover: {
        description: "Aguarde enquanto o vídeo é processado e enviado.",
        showButtons: [""],
        onPopoverRender: (popover) => {
          const actions = AddButtonActions(popover, "Cancelar operação");
          actions.addEventListener("click", async () => { await driverObj.destroy(); store.openMai = true; });
        },
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
              steps[3].element = `button[data-lesson-id="${event.lesson_id}"]`;
              steps[4].element = `#video-player-${event.lesson_id}`;
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
        description: "Clique no botão da aula para carregar o vídeo.",
        side: "right", align: "center", showButtons: [""],
        onPopoverRender: (popover) => {
          const actions = AddButtonActions(popover, "Cancelar operação");
          actions.addEventListener("click", async () => { await driverObj.destroy(); store.openMai = true; });
        },
      },
      onHighlighted: async () => {
        const btn = document.querySelector(`button[data-lesson-id="${window._lastLessonId}"]`);
        if (btn) btn.click();
        for (let i = 0; i < 50; i++) {
          if (document.querySelector(`#video-player-${window._lastLessonId}`)) break;
          await new Promise(r => setTimeout(r, 100));
        }
        await new Promise(r => setTimeout(r, 1000));
        await driverObj.moveNext();
      },
    },
    {
      popover: {
        description: "Este é o vídeo que foi adicionado e está sendo reproduzido.",
        side: "top", align: "center", showButtons: [""],
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
          actions.addEventListener("click", async () => { await driverObj.destroy(); store.openMai = true; });
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
              steps[3].element = `button[data-lesson-id="${event.lesson_id}"]`;
              resolve();
            }
          };
          cleanup = Livewire.on("after-video-upload", handler);
          setTimeout(() => { if (typeof cleanup === "function") cleanup(); resolve(); }, 1500);
        });
        await driverObj.moveNext();
      },
    },
    {
      popover: {
        description: "PDF adicionado! Clique no botão da aula para visualizar.",
        side: "right", align: "center", showButtons: [""],
        onPopoverRender: (popover) => {
          const actions = AddButtonActions(popover, "Cancelar operação");
          actions.addEventListener("click", async () => { await driverObj.destroy(); store.openMai = true; });
        },
      },
      onHighlighted: async () => {
        const btn = document.querySelector(`button[data-lesson-id="${window._lastLessonId}"]`);
        if (btn) btn.click();
        await new Promise(r => setTimeout(r, 1500));
        await driverObj.moveNext();
      },
    },
    {
      popover: {
        description: "Pronto! O PDF foi adicionado à aula.",
        side: "top", align: "center", showButtons: [""],
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
  const steps = [
    {
      element: `#link-field-${dataId}`,
      popover: {
        description: `Digite ou cole o link externo que deseja adicionar à aula.`,
        side: "top", align: "start",
        onPopoverRender: (popover) => {
          popover.previousButton?.remove();
          popover.nextButton?.remove();
          const actions = AddButtonActions(popover, "Cancelar operação");
          actions.addEventListener("click", async () => { await driverObj.destroy(); store.openMai = true; });
        },
      },
      onHighlighted: async () => {
        const input = document.querySelector(`#link-field-${dataId} input`);
        if (!input) return;
        input.addEventListener("blur", async () => {
          if (input.value.trim() !== "") {
            await new Promise(r => setTimeout(r, 300));
            await driverObj.moveNext();
          }
        });
      },
    },
    {
      element: `#link-save-btn-${dataId}`,
      popover: {
        description: `Clique em "Salvar" para registrar o link na aula.`,
        side: "left", align: "center",
        onPopoverRender: (popover) => {
          popover.previousButton?.remove();
          popover.nextButton?.remove();
          const actions = AddButtonActions(popover, "Cancelar operação");
          actions.addEventListener("click", async () => { await driverObj.destroy(); store.openMai = true; });
        },
      },
      onHighlighted: async () => {
        const btn = document.querySelector(`#link-save-btn-${dataId}`);
        if (!btn) return;
        btn.addEventListener("click", async () => {
          await new Promise(r => setTimeout(r, 300));
          await driverObj.moveNext();
        });
      },
    },
    {
      popover: {
        description: "Aguarde...",
        showButtons: [""],
      },
      onHighlighted: async () => {
        await new Promise((resolve) => {
          let cleanup;
          const handler = (event) => {
            if (event.module_id == dataId) {
              if (typeof cleanup === "function") cleanup();
              resolve();
            }
          };
          cleanup = Livewire.on("after-video-upload", handler);
          setTimeout(() => { if (typeof cleanup === "function") cleanup(); resolve(); }, 10000);
        });
        await driverObj.moveNext();
      },
    },
    {
      popover: {
        description: "Pronto! O link foi registrado na aula.",
        side: "top", align: "center", showButtons: [""],
        onPopoverRender: (popover) => {
          const actions = AddButtonActions(popover, "Voltar ao início");
          actions.addEventListener("click", async () => { await driverObj.destroy(); store.openMai = true; });
        },
      },
      onHighlighted: async () => {
        await new Promise(r => setTimeout(r, 2000));
        await driverObj.destroy();
      },
    },
  ];
  const driverObj = drivertour({ ...configDriver, steps: [...steps] });
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
        try { await waitForElement(`#option-block-${dataId}`, 1000); }
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
