import { configDriver, AddButtonActions } from "../utils.js";
const store = Alpine.store("tourguide");

export async function StartTourTeacherProfile() {
    store.openMai = false;

    const driverObj = drivertour({
        ...configDriver,
        onDestroyed: () => {
            store.openMai = true;
        },
        steps: [
            {
                element: `#teacher-profile-avatar`,
                popover: {
                    description: "Esta é sua foto de perfil. Clique em 'Editar Perfil' para alterá-la.",
                    side: "bottom",
                    align: "start",
                },
            },
            {
                element: `#btn-edit-profile`,
                popover: {
                    description: "Clique neste botão para abrir o editor de perfil. Lá você pode trocar sua foto e recortá-la no tamanho ideal.",
                    side: "left",
                    align: "start",
                },
            },
            {
                element: `#btn-open-password-change`,
                popover: {
                    description: "Aqui você pode alterar sua senha de acesso. Mantenha uma senha forte para proteger sua conta.",
                    side: "left",
                    align: "start",
                    onPopoverRender: (popover) => {
                        const actions = AddButtonActions(popover, "Finalizar Tour");
                        actions.addEventListener("click", async () => {
                            await driverObj.destroy();
                        });
                    },
                },
            },
        ],
    });

    return await driverObj.drive();
}
