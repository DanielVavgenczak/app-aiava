document.addEventListener("alpine:init", () => {
    Alpine.store("tourguide", {
        openMai: false,
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
                path: "/app/teacher/discipline",
                tourModule: () => import("./discipline_content"),
                tours: [
                    "StartTourDisciplineCreateContent",
                    "StartTourDisciplineCreateContentUploadBlock",
                    "StartTourCreateActivitTask"
                ],
                isDynamic: true
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
                ],
            }
        ],
        cancelOptionsTourPage() {
            this.openMai = false;
        },
        async initTour() {
            this.loading = true;
            const currentPath = window.location.pathname.trim();
            const uuidRegex = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;
            console.log("Current Path:", currentPath);

            for (const page of this.pages) {
                console.log("Verificando página:", page.path, "Exact:", page.exact, "Dynamic:", page.isDynamic);
                if (page.exact && currentPath === page.path) {
                    console.log("Correspondência exata encontrada:", page.path);
                    this.openMai = true;
                    try {
                        const tourModule = await page.tourModule();
                        console.log("Módulo carregado:", tourModule);
                        return { module: tourModule, tours: page.tours };
                    } catch (error) {
                        console.error("Erro ao carregar tour:", error);
                        return null;
                    } finally {
                        this.loading = false;
                    }
                } else if (page.isDynamic && currentPath.startsWith(page.path + "/")) {
                    const possibleUuid = currentPath.substring(page.path.length + 1);
                    console.log("Possible UUID:", possibleUuid, "Valid:", uuidRegex.test(possibleUuid));
                    if (uuidRegex.test(possibleUuid)) {
                        console.log("Correspondência dinâmica encontrada com UUID:", possibleUuid);
                        this.openMai = true;
                        try {
                            const tourModule = await page.tourModule();
                            console.log("Módulo dinâmico carregado:", tourModule);
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

            // Movido para fora do loop
            console.log("Nenhuma correspondência encontrada para o caminho:", currentPath);
            this.loading = false;
            return null;
        },
        async start(tourName) {
            const tourData = await this.initTour();
            console.log('Carregamdp essa merda', tourData);
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
