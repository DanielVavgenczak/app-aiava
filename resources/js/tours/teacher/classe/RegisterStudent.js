import { configDriver, AddButtonActions } from "../../utils";

const activeTrackedListeners = [];

function trackListener(el, type, handler) {
  el.addEventListener(type, handler);
  activeTrackedListeners.push({ el, type, handler });
}

function clearTrackedListeners() {
  while (activeTrackedListeners.length > 0) {
    const { el, type, handler } = activeTrackedListeners.pop();
    el.removeEventListener(type, handler);
  }
}

export async function ContinueRegisterStudentTour() {
  const store = Alpine.store("tourguide");

  return new Promise(async (resolve) => {
    const btnStudentRegister = document.querySelector("button#student-register");
    if (!btnStudentRegister) return resolve();
    const modalAlreadyOpen = document.querySelector(
      "#student-form",
    )?.closest('[wire\\:model="open"]');

    if (modalAlreadyOpen) {
      await runAddCycle(resolve);
    } else {
      const openDriver = drivertour({
        ...configDriver,
        steps: [
          {
            element: `#${btnStudentRegister.id}`,
            popover: {
              description: `Interagindo com a ferramenta...`,
              showButtons: [""],
            },
            onHighlighted: async (el) => {
              await new Promise((r) => setTimeout(r, 2000));
              el.click();
              await new Promise((r) => setTimeout(r, 1500));
              await openDriver.destroy();
              await runAddCycle(resolve);
            },
          },
        ],
      });
      await openDriver.drive();
    }
  });
}

async function runAddCycle(resolve) {
  const store = Alpine.store("tourguide");
  clearTrackedListeners();

  const driverObj = drivertour({
    ...configDriver,
    steps: [
      // ============================================
      // STEP 1: Formulário de cadastro
      // ============================================
      {
        element: `#student-inputs`,
        disableActiveInteraction: true,
        popover: {
          description: "Preencha o formulário de cadastro do aluno, e clique em 'Adicionar aluno' para adicioná-lo à lista.",
          onPopoverRender: (popover) => {
            popover.nextButton.remove();
            popover.previousButton.remove();

            const back = AddButtonActions(popover, "Cancelar operação");
            back.addEventListener("click", () => {
              Livewire.dispatch("student-register-cancel");
              driverObj.destroy();
              store.openMai = true;
            });
          },
        },
        onHighlighted: async (el) => {
          if (!el) return;

          // --- Função: Remove o overlay do driver ---
          const removeOverlay = () => {
            document.querySelectorAll('.driver-overlay').forEach(overlay => {
              overlay.style.pointerEvents = 'none';
            });
          };

          // --- Função: Força inputs e botões a serem clicáveis ---
          const fixInteractiveElements = () => {
            // Inputs dentro do container
            el.querySelectorAll('input, textarea, select').forEach(input => {
              input.style.setProperty('pointer-events', 'auto', 'important');
              input.style.setProperty('position', 'relative', 'important');
              input.style.setProperty('z-index', '999999999', 'important');
              input.disabled = false;
              input.readOnly = false;
            });

            // Botão "Adicionar aluno"
            const addButton = document.querySelector('#action-add-student');
            if (addButton) {
              addButton.style.setProperty('pointer-events', 'auto', 'important');
              addButton.style.setProperty('position', 'relative', 'important');
              addButton.style.setProperty('z-index', '999999999', 'important');
              addButton.disabled = false;
            }

            // Container do botão
            const buttonContainer = document.querySelector('#add-student-to-list-register');
            if (buttonContainer) {
              buttonContainer.style.setProperty('pointer-events', 'auto', 'important');
              buttonContainer.style.setProperty('z-index', '999999999', 'important');
            }

            removeOverlay();
          };

          // Aplica imediatamente e monitora continuamente
          fixInteractiveElements();
          const fixInterval = setInterval(fixInteractiveElements, 300);

          // Foca no primeiro input
          setTimeout(() => {
            const firstInput = document.querySelector("#input-student-full_name");
            if (firstInput) firstInput.focus();
          }, 300);

          const addButton = document.querySelector('#action-add-student');
        if (addButton) {
            trackListener(addButton, "click", async () => {
            // Aguarda o Livewire processar
            await new Promise((r) => setTimeout(r, 1500));

            // Verifica erro de email
            const errorInput = document.querySelector("#input-student-input-email-error");
            if (errorInput && errorInput.textContent.trim() !== "") {
                alert("Esse email já está cadastrado em nosso sistema.");
                return;
            }

            // Aguarda mais um pouco para o DOM atualizar
            await new Promise((r) => setTimeout(r, 1000));

            // Verifica se o aluno foi adicionado à lista
            const list = document.querySelector("#student-register-list");
            if (list && list.children.length > 0) {
                clearInterval(fixInterval);
                await driverObj.moveNext();
            }
            });
        }
        }
      },

      // ============================================
      // STEP 2: Aluno adicionado - Continuar?
      // ============================================
      {
        element: "#student-register-list",
        popover: {
          description: "Aluno adicionado! Deseja que mostre novamente?",
          onPopoverRender: (popover) => {
            popover.footerButtons.innerHTML = "";

            const addMore = AddButtonActions(popover, "Sim");
            addMore.addEventListener("click", async () => {
              await driverObj.destroy();
              await runAddCycle(resolve);
            });

            const understood = AddButtonActions(popover, "Não");
            understood.addEventListener("click", async () => {
              await driverObj.moveNext();
            });

            const back = AddButtonActions(popover, "Cancelar operação");
            back.addEventListener("click", () => {
              Livewire.dispatch("student-register-cancel");
              driverObj.destroy();
              store.openMai = true;
            });
          },
        },
        // onHighlighted removido — não era necessário
      },

      // ============================================
      // STEP 3: Ações (inserir mais / registrar)
      // ============================================
      {
        element: `#student-input-actions`,
        showButtons: [],
        popover: {
          description: 'Caso você ainda queira adicionar alunos, clique em "Quero inserir mais alunos. <br> Se não, clique em "Registrar"',
          onPopoverRender: (popover) => {
            const nextBtn = popover.nextButton;
            const prevBtn = popover.previousButton;
            if (nextBtn) nextBtn.style.display = 'none';
            if (prevBtn) prevBtn.style.display = 'none';

            const insertMore = AddButtonActions(popover, "Quer inserir mais alunos?");
            insertMore.addEventListener("click", async () => {
              await driverObj.moveNext();
            });
          },
        },
        onHighlighted: async (el) => {
          const cancel = el.querySelector("#student-input-cancel");
          const save = el.querySelector("#student-input-register");

          if (cancel) {
            trackListener(cancel, "click", async () => {
              Livewire.dispatch("student-register-cancel");
              await driverObj.destroy();
              store.openMai = true;
              if (resolve) resolve();
            });
          }

          if (save) {
            trackListener(save, "click", async () => {
              Livewire.dispatch("student-register-cancel");
              await driverObj.destroy();
              store.openMai = false;
              if (resolve) resolve();
            });
          }
        },
      },

      // ============================================
      // STEP 4: Finalizar cadastro
      // ============================================
      {
        element: "#student-input-register",
        showButtons: [""],
        disableActiveInteraction: true,
        allowKeyboardControl: false,
        stageCursor: 'not-allowed',
        popover: {
          description: "Lembre-se de clicar no botão 'Registrar' para finalizar o cadastro.",
          onPopoverRender: (popover) => {
            const nextBtn = popover.nextButton;
            const prevBtn = popover.previousButton;
            if (nextBtn) nextBtn.style.display = 'none';
            if (prevBtn) prevBtn.style.display = 'none';
          }
        },
        onHighlighted: async (el) => {
          await new Promise((r) => setTimeout(r, 3000));
          await driverObj.destroy();
        }
      }
    ],
  });

  await driverObj.drive();
}
