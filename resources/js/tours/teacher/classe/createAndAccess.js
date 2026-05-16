import { configDriver, AddButtonActions } from "../../utils";
import { ContinueRegisterStudentTour } from "./RegisterStudent";
export async function ContinueCreateAcessClasseTour(dataTour) {
  let dataId = dataTour;
  return new Promise(async (resolve) => {
    const driverObj = drivertour({
      ...configDriver,
      steps: [
        {
          element: `#classe-${dataId}`,
          popover: {
            description: `Vou selecionar a última turma criada`,
            side: "bottom",
            align: "start",
            showButtons: ['']
          },
          onHighlighted: async (el) => {
            await new Promise((resolve) => setTimeout(resolve, 3000));
            el.style.zIndex = "0";
            await driverObj.moveNext();
          }
        },
        {
          element: `#classe-access-${dataId}`, // Seleciona o elemento com base no ID
          popover: {
            description: `Vou redirecionar para a página de registro de alunos.`,
            side: "bottom",
            align: "start",
            showButtons: ['']
          },
          onHighlighted: async (el) => {
            await new Promise((resolve) => setTimeout(resolve, 3000));
            const btnAccess = document.querySelector(`#classe-access-${dataId}`)
            if (!btnAccess) return;
            await btnAccess.click()
            el.style.zIndex = "0";
            await driverObj.destroy();
            await new Promise((resolve) => setTimeout(resolve, 1000));
            await ContinueRegisterStudentTour();
          }
        },
      ]
    });

    // Inicia o novo tour
    await driverObj.drive();
  });

}
