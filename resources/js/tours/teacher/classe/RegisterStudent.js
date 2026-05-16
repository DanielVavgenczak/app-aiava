import { configDriver, AddButtonActions } from "../../utils";
export async function ContinueRegisterStudentTour() {

  return new Promise(async (resolve) => {
          const btnStudentRegister = document.querySelector("button#student-register");
          if(!btnStudentRegister) return;
          const driverObj = drivertour({
              ...configDriver,
              steps: [
                  {
                      element: `#${btnStudentRegister.id}`,
                      popover: {
                          description: `Vou clicar no botão "+ Aluno"`,
                          showButtons: [""],
                      },
                      onHighlighted: async (el) => {
                          await new Promise((resolve) => setTimeout(resolve, 3000));
                          if (!btnStudentRegister) {
                              return;
                          }
                          btnStudentRegister.click();
                          await new Promise((resolve) => setTimeout(resolve, 1000));
                          await driverObj.moveNext();
                      },
                  },

                  {
                      element: `#student-input-full_name`,
                      popover: {
                          description: "Insira o nome completo do aluno",
                          onNextClick:async () => {
                              let nameStudent = document.querySelector("#input-student-full_name");
                              if(!nameStudent || nameStudent.value === "" || !nameStudent.value){
                                  alert('Por favor, insira o nome do aluno.');
                                  return;
                              }
                              await driverObj.moveNext();
                          }
                      },
                  },
                  {
                      element: `#student-input-email`,
                      popover: {
                          description: "Insira o email do aluno",
                          onNextClick:async () => {
                              let emailStudent = document.querySelector("#input-student-input-email");
                              let validEmailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                              if(!emailStudent || emailStudent.value === "" || !emailStudent.value){
                                  alert('Por favor, insira o email do aluno.');
                                  return;
                              }

                              if(!validEmailRegex.test(emailStudent.value)){
                                  alert('Por favor, insira um email válido.');
                                  return;
                              }


                              await driverObj.moveNext();
                          }

                      },
                  },
                  {
                      element: `#student-input-phone`,
                      popover: {
                          description: "Caso queira insira o telefone do aluno",
                      },
                  },
                  {
                      element: `#student-input-add`,
                      popover: {
                          description: "Clique em '+', para adicionar o aluno a lista de inscrição",
                      },
                      onHighlighted: async (el) => {
                          await new Promise((resolve) => setTimeout(resolve, 1000));
                          const btnAddStudent = el.querySelector("#student-input-add-to-list");
                          if (!btnAddStudent) {
                              return;
                          }
                          btnAddStudent.addEventListener("click", async () => {
                              await new Promise((resolve) => setTimeout(resolve, 1000));
                              await driverObj.moveNext();
                          })
                          //btnAddStudent.click();
                      }
                  },
                  {
                      element: `#student-input-list`,
                      popover: {
                          description: "Verifique os dados do aluno. Caso estejam errados, exclua-o da lista clicando na lixeira.",
                          // onPopoverRender: (popover, { config, state }) => {
                          //     const actions = AddButtonActions(
                          //         popover,
                          //         "Voltar ao início",
                          //     );
                          //     actions.addEventListener("click", () => {
                          //         Livewire.dispatch("student-register-cancel");
                          //         driverObj.destroy();
                          //         store.openMai = true;
                          //     });
                          // },
                      },
                      onHighlighted:async(el) => {
                          await new Promise((resolve) => setTimeout(resolve, 2000));
                      }
                  },
                  {
                      element: `#student-input-actions`,
                      popover: {
                          description: `Escolha “Cancelar” para desistir do registro ou “Registrar” para concluir o cadastro dos alunos`,
                          // onPopoverRender: (popover, { config, state }) => {
                          //     const actions = AddButtonActions(
                          //         popover,
                          //         "Voltar ao início",
                          //     );
                          //     actions.addEventListener("click", async() => {
                          //         Livewire.dispatch("student-register-cancel");
                          //         await driverObj.destroy();
                          //         store.openMai = true;
                          //     });
                          // },
                      },
                      onHighlighted:async(el) => {
                          const cancel = el.querySelector("#student-input-cancel");
                          const save = el.querySelector("#student-input-register");
                          if (!cancel && !save) {
                              return;
                          }
                          cancel.addEventListener("click",async(event) => {
                              event.preventDefault();

                              Livewire.dispatch("student-register-cancel");
                              await driverObj.destroy();
                              store.openMai = true;
                          });

                          save.addEventListener("click", async () => {
                              Livewire.dispatch("student-register-cancel");
                              await driverObj.destroy();
                              store.openMai = true;
                          });
                      },
                  },
              ]
          })
          return await driverObj.drive();
      })


}
