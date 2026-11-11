import { configDriver, isElementVisible, sleep } from "../utils";

const TOUR_START_EVENT = "student-activities-tour-start";
const MODAL_CLOSE_EVENT = "student-activities-close";
const DISCIPLINE_INDEX_URL = "/aluno/disciplina";
const AUTO_STEP_DESCRIPTION = "Interagindo com a ferramenta...";
const STEP_HOLD_MS = 2000;

let running = false;
let pageBlocker = null;
let keyboardBlocked = false;

function store() {
    return Alpine.store("tourguide");
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
        pageBlocker.id = "student-activities-tour-blocker";
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

function visibleElement(selector) {
    const element = document.querySelector(selector);

    return element && isElementVisible(element) ? element : null;
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

function activityContext(row) {
    return {
        activityId: row.dataset.activityId,
        disciplineId: row.dataset.disciplineId,
        activityTitle: row.dataset.activityTitle,
    };
}

function currentDisciplineId() {
    const match = window.location.pathname.match(
        /^\/aluno\/disciplina\/([^/]+)\/?$/,
    );

    return match?.[1] ?? null;
}

function currentActivityId() {
    return new URLSearchParams(window.location.search).get("activity");
}

function currentOpenActivityId() {
    return visibleElement("#student-activity-drawer")?.dataset.activityId ?? null;
}

function activityUrl(context) {
    return `/aluno/disciplina/${context.disciplineId}?activity=${context.activityId}#open-activity-${context.activityId}`;
}

async function runAutoStep({
    element,
    description = AUTO_STEP_DESCRIPTION,
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
        const row = event.target.closest?.("[data-student-activity-id]");

        if (!row || !list.contains(row) || selected) {
            event.preventDefault();
            event.stopImmediatePropagation();
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();
        selected = true;

        const context = { ...activityContext(row), row };
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
                        "Escolha a atividade que deseja abrir primeiro.",
                    side: "left",
                    align: "center",
                    showButtons: [""],
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

async function ensureActivityVisible(activityId) {
    let item = visibleElement(`#activity-${activityId}`);

    if (item) return item;

    const hidden = document.querySelector(`#activity-${activityId}`);
    const moduleWrapper = hidden?.closest('[id^="accordion-"]');
    const moduleId = moduleWrapper?.id?.replace("accordion-", "");
    const toggle = moduleId
        ? visibleElement(`#open-accordion-block-${moduleId}`)
        : null;

    if (toggle) {
        clickWithFocusTrapsDisabled(toggle);
        await sleep(600);
    }

    return tryWaitFor(`#activity-${activityId}`, 12000);
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

async function navigateToActivity(context) {
    if (currentOpenActivityId() === context.activityId) return;

    if (
        currentDisciplineId() === context.disciplineId &&
        currentActivityId() === context.activityId
    ) {
        return;
    }

    const navigation = waitForNavigation();
    navigateWithLivewire(activityUrl(context));
    await navigation;
    lockPage();
}

async function openDiscipline(context) {
    if (currentDisciplineId() === context.disciplineId) return;

    await navigateTo(DISCIPLINE_INDEX_URL);

    const card = await tryWaitFor("#discipline-card", 6000);

    if (card) {
        await runAutoStep({
            element: card,
            description: AUTO_STEP_DESCRIPTION,
            side: "bottom",
        });
    }

    const access =
        card?.querySelector("#discipline-access-btn") ??
        card?.querySelector("a[href]");

    if (!access) {
        await navigateToActivity(context);
        return;
    }

    const navigation = waitForNavigation();
    await runAutoStep({
        element: access,
        description: AUTO_STEP_DESCRIPTION,
        side: "top",
        after: () => navigateWithLivewire(access.href),
    });
    await navigation;
    lockPage();

    if (currentDisciplineId() !== context.disciplineId) {
        await navigateToActivity(context);
    }
}

async function openActivity(context) {
    if (currentOpenActivityId() === context.activityId) {
        await runAutoStep({
            element: visibleElement(
                `#student-activity-drawer[data-activity-id="${context.activityId}"]`,
            ),
            hold: 5000,
            side: "left",
            align: "start",
        });

        return;
    }

    if (currentOpenActivityId()) {
        const close = visibleElement(
            '#student-activity-drawer button[aria-label="Fechar atividade"]',
        );

        if (close) {
            clickWithFocusTrapsDisabled(close);
            await sleep(500);
        }
    }

    const item = await ensureActivityVisible(context.activityId);
    if (!item) return;

    item.scrollIntoView({ behavior: "smooth", block: "center" });
    await sleep(300);

    const openButton =
        item.querySelector(`#open-activity-${context.activityId}`) ??
        item.querySelector(".tasks-open");

    if (!openButton) return;

    if (openButton.disabled) {
        await runAutoStep({
            element: item,
            hold: 5000,
            side: "right",
        });

        return;
    }

    await runAutoStep({
        element: item,
        side: "right",
        after: () => clickWithFocusTrapsDisabled(openButton),
    });

    const drawer = await tryWaitFor(
        `#student-activity-drawer[data-activity-id="${context.activityId}"]`,
        10000,
    );

    if (!drawer) return;

    await runAutoStep({
        element: drawer,
        hold: 5000,
        side: "left",
        align: "start",
    });
}

async function openActivityFlow(context) {
    window.dispatchEvent(new CustomEvent(MODAL_CLOSE_EVENT));
    await sleep(500);
    await openDiscipline(context);
    await navigateToActivity(context);
    await openActivity(context);
}

export async function StartTourStudentActivities() {
    if (running) return;

    const list = await tryWaitFor("#student-activities-list", 4000);
    const rows = list
        ? Array.from(list.querySelectorAll("[data-student-activity-id]"))
        : [];

    if (rows.length === 0) return;

    running = true;
    const tourStore = store();
    tourStore.openMai = false;
    tourStore.isTourActive = true;

    try {
        guardWindowOpen();

        const context =
            rows.length === 1
                ? { ...activityContext(rows[0]), row: rows[0] }
                : await runSelectionStep(list);

        if (!context?.activityId || !context?.disciplineId) {
            return;
        }

        if (rows.length > 1) {
            context.row.scrollIntoView({
                block: "nearest",
                inline: "nearest",
            });
            clickWithoutNavigation(context.row);
        } else {
            await runAutoStep({
                element: list,
                side: "left",
                align: "center",
            });
        }

        lockPage();
        await openActivityFlow(context);
    } catch (error) {
        console.error("Erro no tour de atividades do aluno:", error);
    } finally {
        restoreWindowOpen();
        unlockPage();
        tourStore.isTourActive = false;
        running = false;
    }
}

window.addEventListener(TOUR_START_EVENT, () => {
    StartTourStudentActivities();
});
