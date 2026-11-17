import test from "node:test";
import assert from "node:assert/strict";
import { JSDOM } from "jsdom";

const PENDING_TOUR_START_EVENT = "student-pending-activities-tour-start";
const CONTINUE_SELECTOR = "[data-tour-pending='continue']";
const DISMISS_SELECTOR = "[data-tour-pending='dismiss']";

function setupDom({ activityId = "a1", disciplineId = "d1" } = {}) {
    const dom = new JSDOM(
        `<!DOCTYPE html><html><body>
            <div
                data-pending-activities-alert
                data-pending-activities-count="2"
                data-activity-id="${activityId}"
                data-discipline-id="${disciplineId}"
                data-activity-title="Atividade pendente"
            >
                <p>Você tem 2 atividades pendentes para enviar.</p>
                <button type="button" data-pending-activities-open>Ver atividades</button>
                <button type="button" data-pending-activities-dismiss>Fechar</button>
            </div>
            <div id="activity-${activityId}">
                <button type="button" id="open-activity-${activityId}">Abrir</button>
            </div>
        </body></html>`,
        {
            pretendToBeVisual: true,
            url: `http://localhost/aluno/disciplina/${disciplineId}?activity=${activityId}`,
        },
    );

    globalThis.window = dom.window;
    globalThis.document = dom.window.document;
    globalThis.CustomEvent = dom.window.CustomEvent;
    dom.window.HTMLElement.prototype.checkVisibility = function () {
        return this.style.display !== "none";
    };
    dom.window.HTMLElement.prototype.scrollIntoView = function () {};

    const store = { openMai: false, isTourActive: false };
    const drivers = [];
    let openClicks = 0;
    let dismissClicks = 0;
    let modalClosed = 0;

    document
        .querySelector("[data-pending-activities-open]")
        .addEventListener("click", () => {
            openClicks += 1;
        });

    document
        .querySelector("[data-pending-activities-dismiss]")
        .addEventListener("click", (event) => {
            dismissClicks += 1;
            event.target.closest("[data-pending-activities-alert]")?.remove();
        });

    document
        .querySelector(`#open-activity-${activityId}`)
        .addEventListener("click", () => {
            openClicks += 1;

            if (
                document.querySelector(
                    `#student-activity-drawer[data-activity-id="${activityId}"]`,
                )
            ) {
                return;
            }

            const drawer = document.createElement("div");
            drawer.id = "student-activity-drawer";
            drawer.dataset.activityId = activityId;
            drawer.textContent = "Atividade aberta";
            document.body.appendChild(drawer);
        });

    window.addEventListener("student-activities-close", () => {
        modalClosed += 1;
    });

    globalThis.Alpine = {
        store: () => store,
    };

    globalThis.drivertour = (config) => {
        const footer = document.createElement("div");
        footer.style.display = "none";
        document.body.appendChild(footer);

        const previousButton = document.createElement("button");
        previousButton.className = "driver-popover-prev-btn";
        const nextButton = document.createElement("button");
        nextButton.className = "driver-popover-next-btn";
        const progress = document.createElement("span");
        progress.className = "driver-popover-progress-text";
        progress.textContent = "1 of 1";
        footer.append(progress, previousButton, nextButton);

        const driver = {
            config,
            footer,
            destroyed: false,
            drive() {
                drivers.push(driver);
                const step = config.steps[0];

                step.popover?.onPopoverRender?.({
                    footer,
                    footerButtons: footer,
                    nextButton,
                    previousButton,
                    progress,
                });

                if (step.onHighlighted) {
                    Promise.resolve().then(() => step.onHighlighted());
                }
            },
            async destroy() {
                driver.destroyed = true;
                footer.remove();
            },
        };

        return driver;
    };

    return {
        dom,
        store,
        drivers,
        alert: document.querySelector("[data-pending-activities-alert]"),
        get openClicks() {
            return openClicks;
        },
        get dismissClicks() {
            return dismissClicks;
        },
        get modalClosed() {
            return modalClosed;
        },
    };
}

async function waitFor(condition, timeout = 1000) {
    const startedAt = Date.now();

    while (!condition()) {
        if (Date.now() - startedAt > timeout) {
            throw new Error("Timeout aguardando condição do tour");
        }

        await new Promise((resolve) => setTimeout(resolve, 5));
    }
}

function capTimers() {
    const realSetTimeout = globalThis.setTimeout;

    globalThis.setTimeout = (callback, delay, ...args) =>
        realSetTimeout(callback, Math.min(delay, 2), ...args);

    return () => {
        globalThis.setTimeout = realSetTimeout;
    };
}

test("tour do alerta pendente segue direto para a atividade ao continuar", async () => {
    const context = setupDom();
    const restoreTimers = capTimers();

    try {
        const { StartTourStudentPendingActivities } = await import(
            "../../resources/js/tours/student/activities.js"
        );

        context.dom.window.dispatchEvent(
            new CustomEvent(PENDING_TOUR_START_EVENT),
        );

        await waitFor(() => context.drivers.length === 1);

        assert.equal(context.store.isTourActive, true);
        assert.match(
            context.drivers[0].config.steps[0].popover.description,
            /2 atividades pendentes/,
        );
        assert.ok(context.drivers[0].footer.querySelector(CONTINUE_SELECTOR));
        assert.ok(context.drivers[0].footer.querySelector(DISMISS_SELECTOR));

        context.drivers[0].footer.querySelector(CONTINUE_SELECTOR).click();

        await waitFor(
            () =>
                context.drivers.length >= 3 &&
                context.openClicks === 1 &&
                Boolean(
                    document.querySelector(
                        '#student-activity-drawer[data-activity-id="a1"]',
                    ),
                ) &&
                !context.store.isTourActive,
        );

        assert.equal(context.dismissClicks, 1);
        assert.equal(context.modalClosed, 1);
        assert.equal(context.alert.isConnected, false);
        assert.equal(context.drivers.every((driver) => driver.destroyed), true);
    } finally {
        restoreTimers();
    }
});

test("dispensar o tour mantém o alerta e evita tours duplicados", async () => {
    const context = setupDom();
    const restoreTimers = capTimers();

    try {
        const { StartTourStudentPendingActivities } = await import(
            "../../resources/js/tours/student/activities.js"
        );

        const tour = StartTourStudentPendingActivities();
        await waitFor(() => context.drivers.length === 1);

        context.drivers[0].footer.querySelector(DISMISS_SELECTOR).click();
        await tour;

        assert.equal(context.drivers[0].destroyed, true);
        assert.equal(context.store.isTourActive, false);
        assert.equal(context.openClicks, 0);
        assert.equal(context.dismissClicks, 0);
        assert.ok(context.alert.isConnected);
        assert.equal(context.alert.dataset.pendingTourStarted, "true");

        await StartTourStudentPendingActivities();
        await new Promise((resolve) => setTimeout(resolve, 30));

        assert.equal(context.drivers.length, 1);
    } finally {
        restoreTimers();
    }
});

test("não inicia o tour pendente quando outro tour está ativo", async () => {
    const context = setupDom();
    const restoreTimers = capTimers();

    try {
        const { StartTourStudentPendingActivities } = await import(
            "../../resources/js/tours/student/activities.js"
        );

        context.store.isTourActive = true;
        await StartTourStudentPendingActivities();
        assert.equal(context.drivers.length, 0);

        context.store.isTourActive = false;
        context.store.openMai = true;
        await StartTourStudentPendingActivities();
        assert.equal(context.drivers.length, 0);
    } finally {
        restoreTimers();
    }
});
