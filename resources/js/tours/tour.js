import { StartTourDisciplineCreateContent } from "./discipline_content";

document.addEventListener("alpine:init", () => {
    Alpine.store("tourguide", {
        openMai: false,
        continueTour: false,
        loading: false,
        pages: [
            {
                exact:true,
                isDynamic: false,
                path: "/app/teacher",
                tourModule: () => import("./home"),
                tours: ["HomeTour"],
            },
            {
                exact:true,
                isDynamic: false,
                path: "/app/teacher/discipline",
                tourModule: () => import("./discipline"),
                tours: [
                    "StartTourCreateDiscipline",
                    "StartTourEditDiscipline",
                    "StartTourDeleteDiscipline",
                    "StartTourSearchDiscipline",
                    "StartTourManagerDisciplineContent"
                ],
            },
            {
                exact: false,
                isDynamic: true,
                path: "/app/teacher/discipline",
                tourModule: () => import("./discipline_content"),
                tours: [
                    "StartTourDisciplineCreateContent",
                    "StartTourDisciplineCreateContentUploadBlock",
                    "StartTourCreateActivitTask"
                ],
            },
             {
                exact: true,
                isDynamic: false,
                path: "/app/teacher/classes",
                tourModule: () => import("./classe_list"),
                tours: [
                    "StartTourClasseCreate",
                    "StartTourClasseDelete",
                    "StartTourClasseEdit",
                    "StartTourClasseAccess",
                ],
            },
             {
                exact: false,
                isDynamic: true,
                path: "/app/teacher/classes",
                tourModule: () => import("./classe_content"),
                tours: [
                    "StartTourStudentRegister",
                    "StartTourStudentFilter",
                    "StartTourStudentEdit",
                    "StartTourStudentAccess",
                    "StartTourStudentDelete"
                ],
            }
        ],
        // listeners:[],
        // init(){
        //     const currentPageTest = window.location.pathname;
        //     console.log("Página atual:", currentPageTest);
        //       this.listeners.push(
        //             Livewire.on('continue-created-content', async () => {
        //                 console.log("Continuar tour de criação de conteúdo");
        //                 await StartTourDisciplineCreateContent();
        //             })
        //         );

        // },
        //  destroy() {
        //     this.listeners.forEach((listener) => {
        //         listener();
        //     });
        // },
        cancelOptionsTourPage() {
            this.openMai = false;
        },
        async initTour() {
            this.loading = true;
            const currentPath = window.location.pathname.trim();
            const uuidRegex = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;
            for (const page of this.pages) {
                if (page.exact && currentPath === page.path) {
                    this.openMai = true;
                    try {
                        const tourModule = await page.tourModule();
                        return { module: tourModule, tours: page.tours };
                    } catch (error) {
                        console.error("Erro ao carregar tour:", error);
                        return null;
                    } finally {
                        this.loading = false;
                    }
                } else if (page.isDynamic && currentPath.startsWith(page.path + "/")) {
                    const possibleUuid = currentPath.substring(page.path.length + 1);
                    if (uuidRegex.test(possibleUuid)) {
                        this.openMai = true;
                        try {
                            const tourModule = await page.tourModule();
                            return { module: tourModule, tours: page.tours, uuid: possibleUuid };
                        } catch (error) {
                            console.error("Erro ao carregar tour dinâmico:", error);
                            return null;
                        } finally {
                            this.loading = false;
                        }
                    }
                }
            }

            this.loading = false;
            return null;
        },
        async start(tourName) {
            const tourData = await this.initTour();
            if (!tourData || !tourData.module[tourName]) {
                console.error(`Tour "${tourName}" não encontrado`);
                return;
            }
            try {
                tourData.module[tourName](tourData.uuid);
            } catch (error) {
                console.error(`Erro ao iniciar o tour "${tourName}":`, error);
            }
        },
    });
});
