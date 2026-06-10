import { StartTourDisciplineCreateContent } from "./teacher/discipline/content";
document.addEventListener("alpine:init", () => {
    Alpine.store("tourguide", {
        openMai: false,
        loading: false,
        pages: [
            {
                exact:true,
                isDynamic: false,
                path: "/professor",
                tourModule: () => import("./home"),
                tours: ["HomeTour"],
            },
            {
                exact:true,
                isDynamic: false,
                path: "/professor/disciplina",
                tourModule: () => import("./teacher/discipline/discipline"),
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
                path: "/professor/disciplina",
                tourModule: () => import("./teacher/discipline/content"),
                tours: [
                    "StartTourDisciplineCreateContent",
                    "StartTourDisciplineCreateContentUploadBlock",
                    "StartTourDisciplineUploadBlockToLessonSelected",
                    "StartTourCreateActivitTask",
                    "StartEditLesson"
                ],
            },
            {
                exact:true,
                isDynamic: false,
                path: "/professor/turmas",
                tourModule: () => import("./classe_list"),
                tours: [
                    "StartTourClasseCreate",
                    "StartTourClasseDelete",
                    "StartTourClasseEdit",
                    "StartTourClasseAccess",
                    "StartTourClasseEmptyStudents",
                ],
            },
            {
                exact: false,
                isDynamic: true,
                path: "/professor/turmas",
                tourModule: () => import("./classe_content"),
                tours: [
                    "StartTourStudentRegister",
                    "StartTourStudentFilter",
                    "StartTourStudentEdit",
                    "StartTourStudentAccess",
                    "StartTourStudentDelete"
                ],
            },
            {
                exact: true,
                isDynamic: false,
                path: "/professor/mensagens",
                tourModule: () => import("./teacher/messages"),
                tours: ["StartTourTeacherMessages"],
            },
            {
                exact: true,
                isDynamic: false,
                path: "/professor/forum",
                tourModule: () => import("./teacher/forum/forum"),
                tours: ["StartTourTeacherForum", "StartTourTeacherForumInteract"],
            },
            {
                exact: false,
                isDynamic: true,
                path: "/professor/forum",
                tourModule: () => import("./teacher/forum/forum"),
                tours: [
                    "StartTourTeacherForumShowNewTopic",
                    "StartTourTeacherForumShowSelectTopic",
                    "StartTourTeacherForumShowMyTopics",
                ],
            },

            {
                exact: true,
                isDynamic: false,
                path: "/professor/perfil",
                tourModule: () => import("./teacher/profile"),
                tours: ["StartTourTeacherProfile"],
            },
            {
                exact: true,
                isDynamic: false,
                path: "/aluno/perfil",
                tourModule: () => import("./student/profile"),
                tours: ["StartTourStudentProfile"],
            },
            // painel do estudante
            {
                exact: true,
                isDynamic: false,
                path: "/aluno/disciplina",
                tourModule: () => import("./student/discipline"),
                tours: ["StartTourStudentDiscipline"],
            },
            // painel do estudante
            {
                exact: true,
                isDynamic: false,
                path: "/aluno/disciplina",
                tourModule: () => import("./student/discipline"),
                tours: ["StartTourStudentDiscipline"],
            },
            {
                exact: false,
                isDynamic: true,
                path: "/aluno/disciplina",
                tourModule: () => import("./student/discipline"),
                tours: [
                    "StartTourStudentDiscipline",
                    "StartTourStudentDisciplineShow",
                    "StartTourStudentDisciplineShowActivitie",
                ],
            },
            {
                exact: true,
                isDynamic: false,
                path: "/aluno/forum",
                tourModule: () => import("./student/forum"),
                tours: ["StartTourStudentForum"],
            },
            {
                exact: false,
                isDynamic: true,
                path: "/aluno/forum",
                tourModule: () => import("./student/forum"),
                tours: [
                    "StartTourStudentForumShow",
                    "StartTourStudentForumShowNewTopic",
                    "StartTourStudentForumShowSelectTopic",
                    "StartTourStudentForumShowMyTopics",
                ],
            },
            {
                exact: false,
                isDynamic: true,
                path: "/aluno/forum/replies",
                tourModule: () => import("./student/forum"),
                tours: ["StartTourStudentForumReplies"],
            },
            // {
            //     exact: true,
            //     isDynamic: false,
            //     path: "/student/mensagens",
            //     tourModule: () => import("./student/messages"),
            //     tours: ["StartTourStudentMessages"],
            // },

        ],

        cancelOptionsTourPage() {
            this.openMai = false;
        },
        async startEditLesson() {
            await this.start('StartEditLesson');
        },
        async initTour() {
            console.log("init tour");
            this.loading = true;
            const currentPath = window.location.pathname.trim();
            const uuidRegex = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;

            for (const page of this.pages) {
                //console.log("Verificando página para tour:", page.path, "Current path:", currentPath);
                if (page.exact && currentPath === page.path) {
                    console.log("Iniciando tour...", currentPath);
                    //console.log("Iniciando tour...", currentPath);
                    this.openMai = true;
                    try {
                        const tourModule = await page.tourModule();
                        return { module: tourModule, tours: page.tours};
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
