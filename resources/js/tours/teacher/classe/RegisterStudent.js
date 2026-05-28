import { configDriver, AddButtonActions } from "../../utils";

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
              description: `Clicando em "+ Adicionar Aluno"`,
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
  const actionsEl = document.querySelector('#student-input-actions');
  if (actionsEl) {
    const cancelBtn = actionsEl.querySelector('#student-input-cancel');
    const saveBtn = actionsEl.querySelector('#student-input-register');
    if (cancelBtn) cancelBtn.replaceWith(cancelBtn.cloneNode(true));
    if (saveBtn) saveBtn.replaceWith(saveBtn.cloneNode(true));
  }

  const addBtn = document.querySelector('#add-student-to-list-register');
  if (addBtn) addBtn.replaceWith(addBtn.cloneNode(true));
  const driverObj = drivertour({
    ...configDriver,
    steps: [
      {
        element: `#student-input-full_name`,
        popover: {
          description: "Digite o nome completo do aluno.",
          onNextClick: async () => {
            const input = document.querySelector("#input-student-full_name");
            if (!input || input.value.trim() === "") {
              alert("Por favor, insira o nome do aluno.");
              return;
            }
            await driverObj.moveNext();
          },
          onPopoverRender: (popover) => {
            const back = AddButtonActions(popover, "Voltar ao início");
            back.addEventListener("click", () => {
              Livewire.dispatch("student-register-cancel");
              driverObj.destroy();
              store.openMai = true;
            });
          },
        },
      },
      {
        element: `#student-input-email`,
        popover: {
          description: "Digite o email do aluno.",
          onNextClick: async () => {
            const input = document.querySelector("#input-student-input-email");
            const validEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!input || input.value.trim() === "") {
              alert("Por favor, insira o email do aluno.");
              return;
            }
            if (!validEmail.test(input.value)) {
              alert("Por favor, insira um email válido.");
              return;
            }

            await driverObj.moveNext();
          },
          onPopoverRender: (popover) => {
            const back = AddButtonActions(popover, "Voltar ao início");
            back.addEventListener("click", () => {
              Livewire.dispatch("student-register-cancel");
              driverObj.destroy();
              store.openMai = true;
            });
          },
        },
      },
      {
        element: "#add-student-to-list-register",
        popover: {
          description:"Clique no botão ' + Adicionar aluno' à lista de cadastro.",
          showButtons: ["previous"],
        },
        onHighlighted: async (el) => {
            if(!el) return
             const newEl = el.cloneNode(true);
            el.replaceWith(newEl);
              newEl.addEventListener("click", async () => {
                await new Promise((r) => setTimeout(r, 500));
                  const errorInput = document.querySelector("#input-student-input-email-error");
                  if(errorInput &&errorInput.textContent.trim() !== "") {
                    alert("Esse email ja esta cadastrado em nosso sistema.");
                    return;
                  }
                    // Aguarda a lista de alunos ser renderizada pelo Livewire
                await new Promise((r) => setTimeout(r, 1000));
                const list = document.querySelector("#student-register-list");
                if (list) {
                   await driverObj.moveNext();
                }
            });
        }
      },
      {
        element: "#student-register-list",
        popover: {
          description:
            "Aluno adicionado! Deseja que mostre novamente?",
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

            const back = AddButtonActions(popover, "Voltar ao início");
            back.addEventListener("click", () => {
              Livewire.dispatch("student-register-cancel");
              driverObj.destroy();
              store.openMai = true;
            });
          },
        },
      },

      {
        element: `#student-input-actions`,
        showButtons: [],
        popover: {
          description:
            'Caso você ainda queira adicionar alunos, clique em "Quero inserir mais alunos. <br> Se não, clique em "Registrar"',
          onPopoverRender: (popover) => {
            const nextBtn = popover.nextButton;
            const prevBtn = popover.previousButton;
            if (nextBtn) nextBtn.style.display = 'none';
            if (prevBtn) prevBtn.style.display = 'none';
            const insertMore = AddButtonActions(popover, "Quer inserir mais alunos?");
            insertMore.addEventListener("click", async () => {
              //Livewire.dispatch("student-register-cancel");
              //driverObj.destroy();
              //store.openMai = true;
                //await driverObj.destroy();
                //await runAddCycle(resolve);
                await driverObj.moveNext();
            });
          },
        },
        onHighlighted: async (el) => {
          const cancel = el.querySelector("#student-input-cancel");
          const save = el.querySelector("#student-input-register");
          if (cancel) {
            cancel.addEventListener("click", async () => {
              Livewire.dispatch("student-register-cancel");
              await driverObj.destroy();
              store.openMai = true;
              if (resolve) resolve();
            });
          }
          if (save) {
            save.addEventListener("click", async () => {
              Livewire.dispatch("student-register-cancel");
              await driverObj.destroy();
              store.openMai = false;
              if (resolve) resolve();
            });
          }
        },
      },
      {
        element: "#student-input-register",
        showButtons: [""],
        disableActiveInteraction:true,
        allowKeyboardControl:false,
        stageCursor:'not-allowed',

        popover: {
            description: "Lembre-se de clicar no botão 'Registrar' para finalizar o cadastro.",
            onPopoverRender: (popover) => {
                const nextBtn = popover.nextButton;
                const prevBtn = popover.previousButton;
                if (nextBtn) nextBtn.style.display = 'none';
                if (prevBtn) prevBtn.style.display = 'none';
                //console.log("Quando chegar aqui vamos destruir o driver");
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
