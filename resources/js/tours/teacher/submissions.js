import { AddButtonActions, configDriver, isElementVisible, sleep } from "../utils.js";

const TOUR_START_EVENT = "teacher-submissions-tour-start";
const MODAL_CLOSE_EVENT = "teacher-submissions-close";
const DISCIPLINE_INDEX_URL = "/professor/disciplina";
const AUTO_STEP_DESCRIPTION = "Interagindo com a ferramenta...";
const MISSING_TAB_DESCRIPTION =
    "These students have not submitted the activity.";
const CANCEL_SELECTOR = "[data-tour-cancel='teacher-submissions']";
const BACK_SELECTOR = "[data-tour-back='teacher-submissions']";
const STEP_HOLD_MS = 2000;

let running = false;
let pageBlocker = null;
let keyboardBlocked = false;

function store() {
    return Alpine.store("tourguide");
}

function closeSubmissionsModal() {
    window.dispatchEvent(new CustomEvent(MODAL_CLOSE_EVENT));
}

function addCancelButton(popover) {
    popover.nextButton?.remove();
    popover.previousButton?.remove();
    popover.progress?.remove();

    const button = AddButtonActions(popover, "Cancelar operação");
    button.dataset.tourCancel = "teacher-submissions";

    if (popover.footer) {
        popover.footer.style.display = "flex";
    }
}

function addBackButton(popover) {
    popover.nextButton?.remove();
    popover.previousButton?.remove();
    popover.progress?.remove();

    const button = AddButtonActions(popover, "Voltar");
    button.dataset.tourBack = "teacher-submissions";

    if (popover.footer) {
        popover.footer.style.display = "flex";
    }
}

function addMissingReviewActions(popover) {
    addCancelButton(popover);
    addBackButton(popover);
}

function blockKeyboard(event) {
    event.preventDefault();
    event.stopImmediatePropagation();
}

function clickWithoutNavigation(element) {
    const stopClick = (event) => {
        event.preventDefault();
        event.stopImmediatePropagation();
    };

    window.addEventListener("click", stopClick, true);

    try {
        element.click();
    } finally {
        window.removeEventListener("click", stopClick, true);
    }
}

function disableFocusTraps() {
    document.querySelectorAll("*").forEach((element) => {
        Array.from(element.attributes).forEach((attribute) => {
            if (attribute.name.startsWith("x-trap")) {
                element.removeAttribute(attribute.name);
            }
        });
    });
}

let nativeWindowOpen = null;

function guardWindowOpen() {
    if (nativeWindowOpen) return;

    nativeWindowOpen = window.open;
    const open = nativeWindowOpen;

    window.open = function (...args) {
        if (this !== window && this != null) return false;

        return open.apply(window, args);
    };
}

function restoreWindowOpen() {
    if (!nativeWindowOpen) return;

    window.open = nativeWindowOpen;
    nativeWindowOpen = null;
}

function clickWithFocusTrapsDisabled(element) {
    disableFocusTraps();
    element.click();
}

function navigateWithLivewire(url) {
    if (!window.Livewire?.navigate) {
        window.location.href = url;

        return;
    }

    disableFocusTraps();
    window.Livewire.navigate(url);
}

function lockPage() {
    if (!keyboardBlocked) {
        document.addEventListener("keydown", blockKeyboard, true);
        keyboardBlocked = true;
    }

    if (pageBlocker?.isConnected) return;

    if (!pageBlocker) {
        pageBlocker = document.createElement("div");
        pageBlocker.id = "teacher-submissions-tour-blocker";
        pageBlocker.style.cssText =
            "position:fixed;inset:0;z-index:9999;background:transparent;cursor:not-allowed;";
    }

    document.body.appendChild(pageBlocker);
}

function unlockPage() {
    pageBlocker?.remove();
    pageBlocker = null;

    if (keyboardBlocked) {
        document.removeEventListener("keydown", blockKeyboard, true);
        keyboardBlocked = false;
    }
}

async function tryWaitFor(selector, timeout = 8000) {
    const startedAt = Date.now();

    while (Date.now() - startedAt < timeout) {
        const element = document.querySelector(selector);

        if (element && isElementVisible(element)) return element;

        await sleep(100);
    }

    return null;
}

function waitForNavigation(timeout = 12000) {
    return new Promise((resolve) => {
        let settled = false;
        let timer = null;
        const finish = () => {
            if (settled) return;
            settled = true;
            document.removeEventListener("livewire:navigated", finish);
            clearTimeout(timer);
            resolve();
        };
        timer = setTimeout(finish, timeout);
        document.addEventListener("livewire:navigated", finish);
    });
}

async function navigateTo(url) {
    if (window.location.pathname === url) return;

    if (!window.Livewire?.navigate) {
        window.location.href = url;
        return;
    }

    const navigation = waitForNavigation();
    navigateWithLivewire(url);
    await navigation;
    lockPage();
}

function submissionContext(row) {
    return {
        studentId: row.dataset.studentId,
        studentName: row.dataset.studentName,
        disciplineId: row.dataset.disciplineId,
        activityId: row.dataset.activityId,
    };
}

function currentDisciplineId() {
    const match = window.location.pathname.match(
        /^\/professor\/disciplina\/([^/]+)\/?$/,
    );

    return match?.[1] ?? null;
}

function visibleElement(selector) {
    const element = document.querySelector(selector);

    return element && isElementVisible(element) ? element : null;
}

function currentOpenActivityId() {
    return visibleElement("#teacher-activity-tabs")?.dataset.activityId ?? null;
}

async function runAutoStep({
    element,
    description,
    hold = STEP_HOLD_MS,
    side = "bottom",
    align = "center",
    after = null,
}) {
    let resolveDone;
    const done = new Promise((resolve) => {
        resolveDone = resolve;
    });

    const driver = drivertour({
        ...configDriver,
        steps: [
            {
                element,
                disableActiveInteraction: true,
                allowKeyboardControl: false,
                popover: {
                    description,
                    side,
                    align,
                    showButtons: [""],
                },
                onHighlighted: async () => {
                    try {
                        await sleep(hold);
                        await driver.destroy();
                        if (after) await after();
                    } finally {
                        resolveDone();
                    }
                },
            },
        ],
    });

    driver.drive();
    await done;
}

async function runSelectionStep(list) {
    const startedAt = Date.now();
    let selected = false;
    let resolveSelection;
    let driver;

    const selection = new Promise((resolve) => {
        resolveSelection = resolve;
    });

    const onWindowClick = (event) => {
        const cancel = event.target.closest?.(CANCEL_SELECTOR);
        const row = event.target.closest?.("[data-submission-id]");

        if (cancel) {
            event.preventDefault();
            event.stopImmediatePropagation();
            selected = true;
            resolveSelection(null);

            return;
        }

        if (!row || !list.contains(row) || selected) {
            event.preventDefault();
            event.stopImmediatePropagation();
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();
        selected = true;

        const context = { ...submissionContext(row), row };
        const remaining = Math.max(
            0,
            STEP_HOLD_MS - (Date.now() - startedAt),
        );

        setTimeout(() => resolveSelection(context), remaining);
    };

    window.addEventListener("click", onWindowClick, true);

    driver = drivertour({
        ...configDriver,
        steps: [
            {
                element: list,
                allowKeyboardControl: false,
                popover: {
                    description:
                        "Escolha a atividade do aluno que deseja corrigir primeiro.",
                    side: "left",
                    align: "center",
                    showButtons: [""],
                    onPopoverRender: addCancelButton,
                },
            },
        ],
    });

    driver.drive();

    const context = await selection;
    window.removeEventListener("click", onWindowClick, true);

    try {
        await driver.destroy();
    } catch {}

    return context;
}

async function runTabSelectionStep(tablist) {
    const tabs = Array.from(
        tablist.querySelectorAll("[data-submissions-tab]"),
    );
    let selected = false;
    let resolveSelection;
    let driver;

    const selection = new Promise((resolve) => {
        resolveSelection = resolve;
    });

    const onWindowClick = (event) => {
        const cancel = event.target.closest?.(CANCEL_SELECTOR);
        const tab = event.target.closest?.("[data-submissions-tab]");

        if (cancel) {
            event.preventDefault();
            event.stopImmediatePropagation();
            selected = true;
            resolveSelection(null);

            return;
        }

        if (!tab || !tabs.includes(tab) || selected) {
            event.preventDefault();
            event.stopImmediatePropagation();
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();
        selected = true;
        resolveSelection(tab);
    };

    window.addEventListener("click", onWindowClick, true);

    driver = drivertour({
        ...configDriver,
        steps: [
            {
                element: tablist,
                allowKeyboardControl: false,
                popover: {
                    description: "Escolha a aba que deseja explorar.",
                    side: "bottom",
                    align: "center",
                    showButtons: [""],
                    onPopoverRender: addCancelButton,
                },
            },
        ],
    });

    driver.drive();

    const tab = await selection;
    window.removeEventListener("click", onWindowClick, true);

    try {
        await driver.destroy();
    } catch {}

    return tab;
}

async function runMissingReviewStep(list) {
    let resolveSelection;
    let driver;

    const selection = new Promise((resolve) => {
        resolveSelection = resolve;
    });

    const onWindowClick = (event) => {
        const cancel = event.target.closest?.(CANCEL_SELECTOR);
        const back = event.target.closest?.(BACK_SELECTOR);

        if (cancel || back) {
            event.preventDefault();
            event.stopImmediatePropagation();
            resolveSelection(back ? "back" : null);

            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();
    };

    window.addEventListener("click", onWindowClick, true);

    driver = drivertour({
        ...configDriver,
        steps: [
            {
                element: list,
                allowKeyboardControl: false,
                popover: {
                    description: MISSING_TAB_DESCRIPTION,
                    side: "left",
                    align: "center",
                    showButtons: [""],
                    onPopoverRender: addMissingReviewActions,
                },
            },
        ],
    });

    driver.drive();

    const action = await selection;
    window.removeEventListener("click", onWindowClick, true);

    try {
        await driver.destroy();
    } catch {}

    return action;
}

async function runSubmittedTabFlow() {
    const list = await tryWaitFor("#teacher-submissions-list", 4000);
    const rows = list
        ? Array.from(list.querySelectorAll("[data-submission-id]"))
        : [];

    if (rows.length === 0) return;

    const context =
        rows.length === 1
            ? { ...submissionContext(rows[0]), row: rows[0] }
            : await runSelectionStep(list);

    if (!context) {
        closeSubmissionsModal();
        return;
    }

    if (
        !context.studentId ||
        !context?.disciplineId ||
        !context?.activityId
    ) {
        return;
    }

    if (rows.length > 1) {
        context.row.scrollIntoView({
            block: "nearest",
            inline: "nearest",
        });
        clickWithoutNavigation(context.row);
    }

    lockPage();
    await openSubmissionFlow(context);
}

function findDeliveriesTab(tabs) {
    return (
        Array.from(tabs.querySelectorAll("button, a, [role='tab']")).find(
            (element) =>
                element.textContent.trim().toLowerCase() === "entregas",
        ) ?? null
    );
}

async function openDiscipline(context) {
    if (currentDisciplineId() === context.disciplineId) return;

    await navigateTo(DISCIPLINE_INDEX_URL);

    const card = await tryWaitFor(
        `#discipline-${context.disciplineId}`,
        6000,
    );

    if (!card) {
        await navigateTo(`/professor/disciplina/${context.disciplineId}`);
        return;
    }

    await runAutoStep({
        element: card,
        description: AUTO_STEP_DESCRIPTION,
        side: "bottom",
    });

    const manager =
        card.querySelector(
            `#discipline-manager-content-${context.disciplineId}`,
        ) ?? card.querySelector("a[href]");

    if (!manager) {
        await navigateTo(`/professor/disciplina/${context.disciplineId}`);
        return;
    }

    const navigation = waitForNavigation();
    await runAutoStep({
        element: manager,
        description: AUTO_STEP_DESCRIPTION,
        side: "top",
        after: () => navigateWithLivewire(manager.href),
    });
    await navigation;
    lockPage();

    if (
        !window.location.pathname.startsWith(
            `/professor/disciplina/${context.disciplineId}`,
        )
    ) {
        await navigateTo(`/professor/disciplina/${context.disciplineId}`);
    }
}

async function openActivity(context) {
    const openActivityId = currentOpenActivityId();

    if (openActivityId && openActivityId !== context.activityId) {
        const close = visibleElement('[data-tour="teacher-activity-close"]');

        if (close) {
            clickWithFocusTrapsDisabled(close);
            await sleep(400);
        }
    }

    if (currentOpenActivityId() !== context.activityId) {
        const task = await tryWaitFor(
            `#tasks-list-${context.activityId}`,
            10000,
        );

        if (!task) return;

        await runAutoStep({
            element: task,
            description: AUTO_STEP_DESCRIPTION,
            side: "right",
            after: () => clickWithFocusTrapsDisabled(task),
        });
    }

    const tabs = await tryWaitFor("#teacher-activity-tabs", 10000);
    if (!tabs) return;

    if (!visibleElement("#teacher-deliveries-list")) {
        const deliveriesTab = findDeliveriesTab(tabs);
        if (!deliveriesTab) return;

        await runAutoStep({
            element: deliveriesTab,
            description: AUTO_STEP_DESCRIPTION,
            side: "bottom",
            align: "start",
            after: () => clickWithFocusTrapsDisabled(deliveriesTab),
        });

        await tryWaitFor("#teacher-deliveries-list", 10000);
    }

    const allFilter = await tryWaitFor('[data-delivery-filter="all"]', 6000);
    if (allFilter && !allFilter.classList.contains("btn-primary")) {
        await runAutoStep({
            element: allFilter,
            description: AUTO_STEP_DESCRIPTION,
            side: "bottom",
            align: "start",
            after: () => clickWithFocusTrapsDisabled(allFilter),
        });
    }

    let detail = visibleElement(
        `#teacher-deliveries-detail[data-selected-student-id="${context.studentId}"]`,
    );

    if (!detail) {
        const studentRow = await tryWaitFor(
            `#delivery-student-${context.studentId}`,
            10000,
        );
        if (!studentRow) return;

        const openStudent =
            studentRow.querySelector(
                `#delivery-student-open-${context.studentId}`,
            ) ?? studentRow.querySelector(
                'button[wire\\:click*="selectStudent"]',
            );

        if (!openStudent) return;

        await runAutoStep({
            element: studentRow,
            description: AUTO_STEP_DESCRIPTION,
            side: "right",
            align: "start",
            after: () => clickWithFocusTrapsDisabled(openStudent),
        });

        detail = await tryWaitFor(
            `#teacher-deliveries-detail[data-selected-student-id="${context.studentId}"]`,
            10000,
        );
    }

    if (!detail) return;

    await runAutoStep({
        element: detail,
        description: AUTO_STEP_DESCRIPTION,
        hold: 5000,
        side: "left",
        align: "start",
    });
}

async function openSubmissionFlow(context) {
    closeSubmissionsModal();
    await sleep(500);
    await openDiscipline(context);
    await openActivity(context);
}

export async function StartTourTeacherSubmissions() {
    if (running) return;

    const submittedTab = await tryWaitFor(
        "#teacher-submissions-tab-submitted",
        4000,
    );
    const tablist = submittedTab?.closest('[role="tablist"]');

    if (!tablist) return;

    running = true;
    const tourStore = store();
    tourStore.openMai = false;
    tourStore.isTourActive = true;

    try {
        guardWindowOpen();

        let selectedTab = await runTabSelectionStep(tablist);

        while (selectedTab?.dataset.submissionsTab === "missing") {
            selectedTab.click();

            const missingList = await tryWaitFor(
                "#teacher-submissions-missing-list",
                4000,
            );

            if (!missingList) return;

            const action = await runMissingReviewStep(missingList);

            if (!action) {
                closeSubmissionsModal();
                return;
            }

            selectedTab = await runTabSelectionStep(tablist);
        }

        if (!selectedTab) {
            closeSubmissionsModal();
            return;
        }

        selectedTab.click();
        await runSubmittedTabFlow();
    } catch (error) {
        console.error("Erro no tour de entregas:", error);
    } finally {
        restoreWindowOpen();
        unlockPage();
        tourStore.isTourActive = false;
        running = false;
    }
}

window.addEventListener(TOUR_START_EVENT, () => {
    StartTourTeacherSubmissions();
});
