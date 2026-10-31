import { StartTourDisciplineCreateContent } from "./teacher/discipline/content";
import "./teacher/submissions";
document.addEventListener("alpine:init", () => {
    Alpine.store("tourguide", {
        openMai: false,
        loading: false,
        isTourActive: false,
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
                    "StartEditLesson",
                    "StartDeleteLesson",
                    "StartTourManageActivityTask",
                    "StartTourActivityDeliveries",
                    "StartTourEditActivityTask",
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
                tours: ["StartTourTeacherMessageToDiscipline","StartTourTeacherMessages","StartTourInitChatInClasse"],
            },
            {
                exact: true,
                isDynamic: false,
                path: "/professor/forum",
                tourModule: () => import("./teacher/forum/forum"),
                tours: ["StartTourTeacherForumInteract"],
            },
            {
                exact: false,
                isDynamic: true,
                path: "/professor/forum",
                tourModule: () => import("./teacher/forum/forum"),
                tours: [
                    "StartTourTeacherForumShowNewTopic",
                    "StartTourTeacherForumShowSelectTopic",
                    "StartTourTeacherForumShowAllTopics",
                    "StartTourTeacherForumShowMyTopics",
                    "StartTourTeacherForumShowEditTopic",
                    "StartTourTeacherForumShowDeleteTopic",
                ],
            },
            // Forum > topico > respostas (interacao entre aluno e professor).
            // A entrada "/professor/forum" acima nao intercepta esta URL: o
            // trecho depois do path seria "replies/<uuid>" e a regex de UUID
            // reprova, entao o loop segue para esta entrada.
            {
                exact: false,
                isDynamic: true,
                path: "/professor/forum/replies",
                tourModule: () => import("./teacher/forum/forum"),
                tours: [
                    "StartTourTeacherForumReplies",
                    "StartTourTeacherForumRepliesNewReply",
                    "StartTourTeacherForumRepliesReplyToReply",
                    "StartTourTeacherForumRepliesEditReply",
                    "StartTourTeacherForumRepliesDeleteReply",
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
            {
                exact: false,
                isDynamic: true,
                path: "/aluno/disciplina",
                tourModule: () => import("./student/discipline"),
                tours: [
                    "StartTourStudentDiscipline",
                    "StartTourStudentDisciplineShow",
                    "StartTourStudentDisciplineShowActivitie",
                    "StartTourStudentAnswerActivity",
                    "StartTourStudentActivityResult",
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
            {
                exact: true,
                isDynamic: false,
                path: "/aluno/mensagens",
                tourModule: () => import("./student/messages"),
                tours: ["StartTourStudentMessages"],
            },

        ],

        cancelOptionsTourPage() {
            this.openMai = false;
        },
        async startEditLesson() {
            await this.start('StartEditLesson');
        },
        async startDeleteLesson() {
            await this.start('StartDeleteLesson');
        },
        async initTour() {
            this.loading = true;

            Livewire.dispatch("tour::close-opened-modals");
            const currentPath = window.location.pathname.trim();
            const uuidRegex = /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;

            for (const page of this.pages) {
                if (page.exact && currentPath === page.path) {
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
