import test from "node:test";
import assert from "node:assert/strict";
import { JSDOM } from "jsdom";

const CANCEL_SELECTOR = "[data-tour-cancel='student-activities']";
const BACK_SELECTOR = "[data-tour-back='student-activities']";

function setupDom({
    pendingActivities = [],
    reviewedActivities = ["a1"],
} = {}) {
    const activityIds = [
        ...new Set([...pendingActivities, ...reviewedActivities]),
    ];
    const pendingRows = pendingActivities
        .map(
            (id) => `
                <button
                    data-student-activity-id="${id}"
                    data-activity-id="${id}"
                    data-discipline-id="d1"
                    data-activity-title="Atividade pendente ${id}"
                >Atividade pendente ${id}</button>
            `,
        )
        .join("");
    const reviewedRows = reviewedActivities
        .map(
            (id) => `
                <button
                    data-student-reviewed-activity-id="${id}"
                    data-activity-id="${id}"
                    data-discipline-id="d1"
                    data-activity-title="Atividade corrigida ${id}"
                >Atividade corrigida ${id}</button>
            `,
        )
        .join("");
    const activityItems = activityIds
        .map(
            (id) => `
                <div id="activity-${id}">
                    <button id="open-activity-${id}">Abrir</button>
                    <button id="open-activity-result-${id}">Resultado</button>
                </div>
            `,
        )
        .join("");
    const firstActivityId = activityIds[0] ?? "a1";

    const dom = new JSDOM(
        `<!DOCTYPE html><html><body>
            <div id="student-activities-tabs" role="tablist">
                <button type="button" id="student-activities-tab-pending" data-activities-tab="pending">Pendentes</button>
                <button type="button" id="student-activities-tab-reviewed" data-activities-tab="reviewed">Corrigidas</button>
            </div>
            <div id="student-activities-list" style="display:none">${pendingRows}</div>
            <div id="student-activities-reviewed-list">${reviewedRows}</div>
            ${activityItems}
        </body></html>`,
        {
            pretendToBeVisual: true,
            url: `http://localhost/aluno/disciplina/d1?activity=${firstActivityId}`,
        },
    );

    globalThis.window = dom.window;
    globalThis.document = dom.window.document;
    globalThis.CustomEvent = dom.window.CustomEvent;
    dom.window.HTMLElement.prototype.checkVisibility = function () {
        return this.style.display !== "none";
    };
    dom.window.HTMLElement.prototype.scrollIntoView = function () {};

    const pendingList = document.querySelector("#student-activities-list");
    const reviewedList = document.querySelector(
        "#student-activities-reviewed-list",
    );

    document
        .querySelector('[data-activities-tab="pending"]')
        .addEventListener("click", () => {
            pendingList.style.display = "";
            reviewedList.style.display = "none";
        });
    document
        .querySelector('[data-activities-tab="reviewed"]')
        .addEventListener("click", () => {
            pendingList.style.display = "none";
            reviewedList.style.display = "";
        });

    const store = { openMai: false, isTourActive: false };
    const drivers = [];
    let activityClicks = 0;
    let resultClicks = 0;

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

    activityIds.forEach((id) => {
        document
            .querySelector(`#open-activity-${id}`)
            .addEventListener("click", () => {
                activityClicks += 1;

                if (
                    document.querySelector(
                        `#student-activity-drawer[data-activity-id="${id}"]`,
                    )
                ) {
                    return;
                }

                const drawer = document.createElement("div");
                drawer.id = "student-activity-drawer";
                drawer.dataset.activityId = id;
                drawer.textContent = "Atividade aberta";
                document.body.appendChild(drawer);
            });

        document
            .querySelector(`#open-activity-result-${id}`)
            .addEventListener("click", () => {
                resultClicks += 1;

                if (document.querySelector("#student-result-drawer")) return;

                const drawer = document.createElement("div");
                drawer.id = "student-result-drawer";
                drawer.dataset.activityId = id;
                drawer.textContent = "Resultado da atividade";
                document.body.appendChild(drawer);
            });
    });

    return {
        store,
        drivers,
        pendingList,
        reviewedList,
        get activityClicks() {
            return activityClicks;
        },
        get resultClicks() {
            return resultClicks;
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

function assertCancelOnly(driver) {
    const buttons = driver.footer.querySelectorAll("button");

    assert.equal(buttons.length, 1);
    assert.ok(buttons[0].matches(CANCEL_SELECTOR));
}

function assertCancelAndBack(driver) {
    assert.ok(driver.footer.querySelector(CANCEL_SELECTOR));
    assert.ok(driver.footer.querySelector(BACK_SELECTOR));
}

test("tour de atividades abre a aba de corrigidas e o resultado da atividade", async () => {
    const tourContext = setupDom();
    const { store, drivers, pendingList, reviewedList } = tourContext;
    const restoreTimers = capTimers();

    let closed = 0;
    window.addEventListener("student-activities-close", () => {
        closed += 1;
    });

    try {
        const { StartTourStudentActivities } = await import(
            "../../resources/js/tours/student/activities.js"
        );

        const tour = StartTourStudentActivities();
        await waitFor(() => drivers.length === 1);

        assertCancelOnly(drivers[0]);

        document.querySelector('[data-activities-tab="reviewed"]').click();

        await tour;

        assert.equal(closed, 1);
        assert.equal(pendingList.style.display, "none");
        assert.equal(reviewedList.style.display, "");
        assert.equal(tourContext.resultClicks, 1);
        assert.equal(tourContext.activityClicks, 0);
        assert.ok(drivers.length >= 3);
        assert.equal(drivers.every((driver) => driver.destroyed), true);
        assert.equal(store.isTourActive, false);
        assert.ok(document.querySelector("#student-result-drawer"));
    } finally {
        restoreTimers();
    }
});

test("cancelar operação na escolha da aba fecha o modal e encerra o tour", async () => {
    const tourContext = setupDom();
    const { store, drivers } = tourContext;
    const restoreTimers = capTimers();

    let closed = 0;
    window.addEventListener("student-activities-close", () => {
        closed += 1;
    });

    try {
        const { StartTourStudentActivities } = await import(
            "../../resources/js/tours/student/activities.js"
        );

        const tour = StartTourStudentActivities();
        await waitFor(() => drivers.length === 1);

        const cancel = drivers[0].footer.querySelector(CANCEL_SELECTOR);
        assert.ok(cancel);

        cancel.click();
        await tour;

        assert.equal(closed, 1);
        assert.equal(drivers.length, 1);
        assert.equal(drivers[0].destroyed, true);
        assert.equal(store.isTourActive, false);
        assert.equal(tourContext.resultClicks, 0);
        assert.equal(tourContext.activityClicks, 0);
    } finally {
        restoreTimers();
    }
});

test("volta de aba corrigida vazia e continua nas pendentes com múltiplas atividades", async () => {
    const tourContext = setupDom({
        pendingActivities: ["a1", "a2"],
        reviewedActivities: [],
    });
    const { store, drivers, pendingList, reviewedList } = tourContext;
    const restoreTimers = capTimers();

    let closed = 0;
    window.addEventListener("student-activities-close", () => {
        closed += 1;
    });

    try {
        const { StartTourStudentActivities } = await import(
            "../../resources/js/tours/student/activities.js"
        );

        const tour = StartTourStudentActivities();
        await waitFor(() => drivers.length === 1);

        document.querySelector('[data-activities-tab="reviewed"]').click();
        await waitFor(() => drivers.length === 2);

        assertCancelAndBack(drivers[1]);
        drivers[1].footer.querySelector(BACK_SELECTOR).click();

        await waitFor(() => drivers.length === 3);
        assertCancelOnly(drivers[2]);

        document.querySelector('[data-activities-tab="pending"]').click();
        await waitFor(() => drivers.length === 4);

        assertCancelAndBack(drivers[3]);

        document
            .querySelector(
                '#student-activities-list [data-student-activity-id="a1"]',
            )
            .click();

        await tour;

        assert.equal(closed, 1);
        assert.equal(pendingList.style.display, "");
        assert.equal(reviewedList.style.display, "none");
        assert.equal(tourContext.activityClicks, 1);
        assert.equal(tourContext.resultClicks, 0);
        assert.equal(drivers.every((driver) => driver.destroyed), true);
        assert.equal(store.isTourActive, false);
        assert.ok(
            document.querySelector(
                '#student-activity-drawer[data-activity-id="a1"]',
            ),
        );
    } finally {
        restoreTimers();
    }
});

test("segue automaticamente quando a aba pendente tem uma única atividade", async () => {
    const tourContext = setupDom({
        pendingActivities: ["a1"],
        reviewedActivities: [],
    });
    const { store, drivers } = tourContext;
    const restoreTimers = capTimers();

    let closed = 0;
    window.addEventListener("student-activities-close", () => {
        closed += 1;
    });

    try {
        const { StartTourStudentActivities } = await import(
            "../../resources/js/tours/student/activities.js"
        );

        const tour = StartTourStudentActivities();
        await waitFor(() => drivers.length === 1);

        document.querySelector('[data-activities-tab="reviewed"]').click();
        await waitFor(() => drivers.length === 2);

        drivers[1].footer.querySelector(BACK_SELECTOR).click();
        await waitFor(() => drivers.length === 3);

        document.querySelector('[data-activities-tab="pending"]').click();

        await tour;

        assert.equal(closed, 1);
        assert.equal(tourContext.activityClicks, 1);
        assert.equal(tourContext.resultClicks, 0);
        assert.equal(drivers.every((driver) => driver.destroyed), true);
        assert.equal(store.isTourActive, false);
        assert.ok(
            document.querySelector(
                '#student-activity-drawer[data-activity-id="a1"]',
            ),
        );
    } finally {
        restoreTimers();
    }
});

test("volta da escolha de atividade para as abas quando não há pendentes", async () => {
    const tourContext = setupDom({
        pendingActivities: [],
        reviewedActivities: ["a1"],
    });
    const { store, drivers } = tourContext;
    const restoreTimers = capTimers();

    let closed = 0;
    window.addEventListener("student-activities-close", () => {
        closed += 1;
    });

    try {
        const { StartTourStudentActivities } = await import(
            "../../resources/js/tours/student/activities.js"
        );

        const tour = StartTourStudentActivities();
        await waitFor(() => drivers.length === 1);

        document.querySelector('[data-activities-tab="pending"]').click();
        await waitFor(() => drivers.length === 2);

        assertCancelAndBack(drivers[1]);
        drivers[1].footer.querySelector(BACK_SELECTOR).click();

        await waitFor(() => drivers.length === 3);
        assertCancelOnly(drivers[2]);

        document.querySelector('[data-activities-tab="reviewed"]').click();

        await tour;

        assert.equal(closed, 1);
        assert.equal(tourContext.activityClicks, 0);
        assert.equal(tourContext.resultClicks, 1);
        assert.equal(drivers.every((driver) => driver.destroyed), true);
        assert.equal(store.isTourActive, false);
        assert.ok(document.querySelector("#student-result-drawer"));
    } finally {
        restoreTimers();
    }
});

test("voltar da escolha de múltiplas pendentes retorna para as abas", async () => {
    const tourContext = setupDom({
        pendingActivities: ["a1", "a2"],
        reviewedActivities: [],
    });
    const { store, drivers } = tourContext;
    const restoreTimers = capTimers();

    let closed = 0;
    window.addEventListener("student-activities-close", () => {
        closed += 1;
    });

    try {
        const { StartTourStudentActivities } = await import(
            "../../resources/js/tours/student/activities.js"
        );

        const tour = StartTourStudentActivities();
        await waitFor(() => drivers.length === 1);

        document.querySelector('[data-activities-tab="pending"]').click();
        await waitFor(() => drivers.length === 2);

        assertCancelAndBack(drivers[1]);
        drivers[1].footer.querySelector(BACK_SELECTOR).click();

        await waitFor(() => drivers.length === 3);
        assertCancelOnly(drivers[2]);

        drivers[2].footer.querySelector(CANCEL_SELECTOR).click();
        await tour;

        assert.equal(closed, 1);
        assert.equal(tourContext.activityClicks, 0);
        assert.equal(tourContext.resultClicks, 0);
        assert.equal(drivers.every((driver) => driver.destroyed), true);
        assert.equal(store.isTourActive, false);
    } finally {
        restoreTimers();
    }
});
