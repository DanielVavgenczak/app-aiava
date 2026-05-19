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
              description: `Vou clicar no botão "Adicionar Aluno" para abrir o formulário de cadastro.`,
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
        element: `#student-input-phone`,
        popover: {
          description:
            "Caso queira, insira o telefone do aluno (opcional). Depois clique em Próximo.",
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
        element: `#student-input-add`,
        popover: {
          description:
            "Clique no botão '+' para adicionar este aluno à lista de cadastro.",
          onPopoverRender: (popover) => {
            popover.footerButtons.innerHTML = "";
            const back = AddButtonActions(popover, "Voltar ao início");
            back.addEventListener("click", () => {
              Livewire.dispatch("student-register-cancel");
              driverObj.destroy();
              store.openMai = true;
            });
          },
        },
        onHighlighted: async (el) => {
          const btnAdd = el.querySelector("#student-input-add-to-list");
          if (!btnAdd) return;
          btnAdd.addEventListener("click", async () => {
            await new Promise((r) => setTimeout(r, 500));
            await driverObj.moveNext();
          });
        },
      },
      {
        element: `#student-input-list`,
        popover: {
          description:
            "Aluno adicionado à lista! Deseja adicionar outro aluno ou já entendeu o processo de como funciona?",
          onPopoverRender: (popover) => {
            popover.footerButtons.innerHTML = "";
            const addMore = AddButtonActions(popover, "Não entendi");
            addMore.addEventListener("click", async () => {
              await driverObj.destroy();
              await runAddCycle(resolve);
            });

            const understood = AddButtonActions(popover, "Entendi");
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
        element: `#student-input-list`,
        popover: {
          description:
            "Veja abaixo a lista completa de alunos adicionados. Você pode remover algum clicando no ícone da lixeira, se necessário.",
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
        element: `#student-input-actions`,
        popover: {
          description:
            'Clique em "Cancelar" para descartar tudo ou em "Registrar" para salvar todos os alunos da turma.',
          onPopoverRender: (popover) => {
            const back = AddButtonActions(popover, "Voltar ao início");
            back.addEventListener("click", () => {
              Livewire.dispatch("student-register-cancel");
              driverObj.destroy();
              store.openMai = true;
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
              store.openMai = true;
              if (resolve) resolve();
            });
          }
        },
      },
    ],
  });

  await driverObj.drive();
}
