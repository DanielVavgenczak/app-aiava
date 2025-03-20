document.addEventListener("alpine:init", () => {
    Alpine.store("tourguide", {
        openMai: false,
        loading: false,
        pages: [
            {
                path: "/app/teacher",
                tourModule: () => import("./home"),
                tours: ["HomeTour"],
            },
            {
                path: "/app/teacher/discipline",
                tourModule: () => import("./disciplines"),
                tours: [
                    "StartTourCreateDiscipline",
                    "StartTourEditDiscipline",
                    "StartTourDeleteDiscipline",
                    "StartTourSearchDiscipline",
                    "StartTourManagerDiscipline"
                ],
            },
        ],
        cancelOptionsTourPage() {
            this.openMai = false;
        },
        async initTour() {
            this.loading = true;
            const currentPath = window.location.pathname.trim();
            const uuidRegex =
                /^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/;
            for (const page of this.pages) {
                const isExactMatch = currentPath === page.path;
                const isDynamicMatch =
                    currentPath.startsWith(page.path + "/") &&
                    uuidRegex.test(currentPath.substring(page.path.length + 1));
                if (isExactMatch || isDynamicMatch) {
                    this.openMai = true;
                    try {
                        const tourModule = await page.tourModule();
                        return { module: tourModule, tours: page.tours };
                    } catch (error) {
                        console.error("no catch", error);
                        return null;
                    } finally {
                        this.loading = false; // Desativa o indicador de carregamento
                    }
                }
            }
        },
        async start(tourName) {
            const tourData = await this.initTour();
            if (!tourData && !tourData.module[tourName]) {
                console.error(`Função de tour "${tourName}" não encontrada.`);
            }
            tourData.module[tourName]();
        },
    });
});
