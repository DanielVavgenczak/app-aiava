import test from "node:test";
import assert from "node:assert/strict";
import { JSDOM } from "jsdom";

function setupDom() {
    const dom = new JSDOM(
        `<!DOCTYPE html><html><body>
            <div id="student-activities-tabs" role="tablist">
                <button type="button" id="student-activities-tab-pending" data-activities-tab="pending">Pendentes</button>
                <button type="button" id="student-activities-tab-reviewed" data-activities-tab="reviewed">Corrigidas</button>
            </div>
            <div id="student-activities-list" style="display:none"></div>
            <div id="student-activities-reviewed-list">
                <button
                    data-student-reviewed-activity-id="a1"
                    data-activity-id="a1"
                    data-discipline-id="d1"
                    data-activity-title="Atividade corrigida"
                >Atividade corrigida</button>
            </div>
            <div id="activity-a1">
                <button id="open-activity-result-a1">Resultado</button>
            </div>
        </body></html>`,
        {
            pretendToBeVisual: true,
            url: "http://localhost/aluno/disciplina/d1?activity=a1",
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

    const store = { openMai: true, isTourActive: false };
    const drivers = [];
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

    document
        .querySelector("#open-activity-result-a1")
        .addEventListener("click", () => {
            resultClicks += 1;

            if (document.querySelector("#student-result-drawer")) return;

            const drawer = document.createElement("div");
            drawer.id = "student-result-drawer";
            drawer.dataset.activityId = "a1";
            drawer.textContent = "Resultado da atividade";
            document.body.appendChild(drawer);
        });

    return {
        store,
        drivers,
        pendingList,
        reviewedList,
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

test("tour de atividades abre a aba de corrigidas e o resultado da atividade", async () => {
    const tourContext = setupDom();
    const { store, drivers, pendingList, reviewedList } = tourContext;
    const realSetTimeout = globalThis.setTimeout;

    globalThis.setTimeout = (callback, delay, ...args) =>
        realSetTimeout(callback, Math.min(delay, 2), ...args);

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

        await tour;

        assert.equal(closed, 1);
        assert.equal(pendingList.style.display, "none");
        assert.equal(reviewedList.style.display, "");
        assert.equal(tourContext.resultClicks, 1);
        assert.ok(drivers.length >= 3);
        assert.equal(drivers.every((driver) => driver.destroyed), true);
        assert.equal(store.isTourActive, false);
        assert.ok(document.querySelector("#student-result-drawer"));
    } finally {
        globalThis.setTimeout = realSetTimeout;
    }
});

test("cancelar operação na escolha da aba fecha o modal e encerra o tour", async () => {
    const tourContext = setupDom();
    const { store, drivers } = tourContext;
    const realSetTimeout = globalThis.setTimeout;

    globalThis.setTimeout = (callback, delay, ...args) =>
        realSetTimeout(callback, Math.min(delay, 2), ...args);

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

        const cancel = drivers[0].footer.querySelector(
            "[data-tour-cancel='student-activities']",
        );
        assert.ok(cancel);

        cancel.click();
        await tour;

        assert.equal(closed, 1);
        assert.equal(drivers.length, 1);
        assert.equal(drivers[0].destroyed, true);
        assert.equal(store.isTourActive, false);
        assert.equal(tourContext.resultClicks, 0);
    } finally {
        globalThis.setTimeout = realSetTimeout;
    }
});
