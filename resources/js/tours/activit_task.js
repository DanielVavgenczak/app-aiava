export function StartTourCreateActivitTask() {
    let store = Alpine.store("tourguide");
    store.openMai = false;
    console.log('Vamos criar uma terefa aqui')
    // const driverObj = drivertour({
    //     ...configDriver,
    //     steps: [
    //         {
    //             element: `#activit-task-create`,
    //             popover: {
    //                 description: `<h2>Vou clicar no botão "Atividade +"</h2>`,
    //                 showButtons: [""],
    //             },
    //             onHighlighted: async (el) => {
    //                 await new Promise((resolve) => setTimeout(resolve, 3000));
    //                 if (!newActivitTaskButton) {
    //                     console.error("não encontramos ela aqui");
    //                 }
    //                 newActivitTaskButton.click();
    //                 await new Promise((resolve) => setTimeout(resolve, 1000));
    //                 await driverObj.moveNext();
    //             },
    //         },
    //     ],
    // });
}
