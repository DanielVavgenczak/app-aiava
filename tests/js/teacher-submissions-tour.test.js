import test from "node:test";
import assert from "node:assert/strict";
import { JSDOM } from "jsdom";

const CANCEL_SELECTOR = "[data-tour-cancel='teacher-submissions']";
const BACK_SELECTOR = "[data-tour-back='teacher-submissions']";

function setupDom(html) {
    const dom = new JSDOM(`<!DOCTYPE html><html><body>${html}</body></html>`, {
        pretendToBeVisual: true,
        url: "http://localhost/professor",
    });

    globalThis.window = dom.window;
    globalThis.document = dom.window.document;
    globalThis.CustomEvent = dom.window.CustomEvent;
    dom.window.HTMLElement.prototype.checkVisibility = function () {
        return this.style.display !== "none";
    };

    const submittedList = dom.window.document.querySelector(
        "#teacher-submissions-list",
    );
    const missingList = dom.window.document.querySelector(
        "#teacher-submissions-missing-list",
    );
    const submittedTab = dom.window.document.querySelector(
        "[data-submissions-tab='submitted']",
    );
    const missingTab = dom.window.document.querySelector(
        "[data-submissions-tab='missing']",
    );

    missingList.style.display = "none";
    submittedTab.addEventListener("click", () => {
        submittedList.style.display = "";
        missingList.style.display = "none";
    });
    missingTab.addEventListener("click", () => {
        submittedList.style.display = "none";
        missingList.style.display = "";
    });

    const store = { openMai: true, isTourActive: false };
    const drivers = [];

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
            destroyed: false,
            footer,
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

    return { dom, store, drivers };
}

function assertOnlyCancel(driver) {
    assert.equal(driver.footer.style.display, "flex");
    assert.equal(
        driver.footer.querySelector(".driver-popover-next-btn"),
        null,
    );
    assert.equal(
        driver.footer.querySelector(".driver-popover-prev-btn"),
        null,
    );
    assert.equal(
        driver.footer.querySelector(".driver-popover-progress-text"),
        null,
    );

    const buttons = driver.footer.querySelectorAll("button");
    assert.equal(buttons.length, 1);
    assert.equal(buttons[0].dataset.tourCancel, "teacher-submissions");
}

function assertMissingReviewActions(driver) {
    assert.equal(driver.footer.style.display, "flex");
    assert.equal(
        driver.footer.querySelector(".driver-popover-next-btn"),
        null,
    );
    assert.equal(
        driver.footer.querySelector(".driver-popover-prev-btn"),
        null,
    );
    assert.equal(
        driver.footer.querySelector(".driver-popover-progress-text"),
        null,
    );

    const back = driver.footer.querySelector(BACK_SELECTOR);
    const cancel = driver.footer.querySelector(CANCEL_SELECTOR);

    assert.ok(back);
    assert.ok(cancel);
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

const markup = `
    <div role="tablist">
        <div data-submissions-tab="submitted" id="teacher-submissions-tab-submitted">Enviadas</div>
        <div data-submissions-tab="missing" id="teacher-submissions-tab-missing">Não enviadas</div>
    </div>
    <div id="teacher-submissions-list">
        <button data-submission-id="1" data-student-id="s1" data-discipline-id="d1" data-activity-id="a1">Entrega 1</button>
        <button data-submission-id="2" data-student-id="s2" data-discipline-id="d2" data-activity-id="a2">Entrega 2</button>
    </div>
    <div id="teacher-submissions-missing-list"></div>
`;

test("cancelar na escolha da aba fecha o modal e encerra o tour", async () => {
    const { drivers, store } = setupDom(markup);
    let closed = false;
    window.addEventListener("teacher-submissions-close", () => {
        closed = true;
    });

    const { StartTourTeacherSubmissions } = await import(
        "../../resources/js/tours/teacher/submissions.js"
    );

    const tour = StartTourTeacherSubmissions();
    await waitFor(() => drivers.length === 1);

    assertOnlyCancel(drivers[0]);

    const cancel = drivers[0].footer.querySelector(CANCEL_SELECTOR);
    assert.ok(cancel);

    cancel.click();
    await tour;

    assert.equal(closed, true);
    assert.equal(store.isTourActive, false);
    assert.equal(drivers.length, 1);
    assert.equal(drivers[0].destroyed, true);
});

test("cancelar na escolha da entrega fecha o modal e não abre a atividade", async () => {
    const { drivers } = setupDom(markup);
    let closed = 0;
    window.addEventListener("teacher-submissions-close", () => {
        closed += 1;
    });

    const { StartTourTeacherSubmissions } = await import(
        "../../resources/js/tours/teacher/submissions.js"
    );

    const tour = StartTourTeacherSubmissions();
    await waitFor(() => drivers.length === 1);

    document
        .querySelector('[data-submissions-tab="submitted"]')
        .click();

    await waitFor(() => drivers.length === 2);

    assertOnlyCancel(drivers[1]);

    const cancel = drivers[1].footer.querySelector(CANCEL_SELECTOR);
    assert.ok(cancel);

    cancel.click();
    await tour;

    assert.equal(closed, 1);
    assert.equal(drivers.length, 2);
    assert.equal(drivers.every((driver) => driver.destroyed), true);
});

test("voltar para enviadas depois de explorar não enviadas retoma o fluxo", async () => {
    const { drivers, store } = setupDom(markup);
    let closed = 0;
    window.addEventListener("teacher-submissions-close", () => {
        closed += 1;
    });

    const { StartTourTeacherSubmissions } = await import(
        "../../resources/js/tours/teacher/submissions.js"
    );

    const tour = StartTourTeacherSubmissions();
    await waitFor(() => drivers.length === 1);

    document
        .querySelector('[data-submissions-tab="missing"]')
        .click();

    await waitFor(() => drivers.length === 2);

    assertMissingReviewActions(drivers[1]);

    drivers[1].footer.querySelector(BACK_SELECTOR).click();

    await waitFor(() => drivers.length === 3);

    assertOnlyCancel(drivers[2]);

    document
        .querySelector('[data-submissions-tab="submitted"]')
        .click();

    await waitFor(() => drivers.length === 4);

    assertOnlyCancel(drivers[3]);

    drivers[3].footer.querySelector(CANCEL_SELECTOR).click();
    await tour;

    assert.equal(closed, 1);
    assert.equal(store.isTourActive, false);
    assert.equal(drivers.length, 4);
    assert.equal(drivers.every((driver) => driver.destroyed), true);
});

test("cancelar ao revisar não enviadas fecha o modal", async () => {
    const { drivers, store } = setupDom(markup);
    let closed = 0;
    window.addEventListener("teacher-submissions-close", () => {
        closed += 1;
    });

    const { StartTourTeacherSubmissions } = await import(
        "../../resources/js/tours/teacher/submissions.js"
    );

    const tour = StartTourTeacherSubmissions();
    await waitFor(() => drivers.length === 1);

    document
        .querySelector('[data-submissions-tab="missing"]')
        .click();

    await waitFor(() => drivers.length === 2);
    assertMissingReviewActions(drivers[1]);

    drivers[1].footer.querySelector(CANCEL_SELECTOR).click();
    await tour;

    assert.equal(closed, 1);
    assert.equal(store.isTourActive, false);
    assert.equal(drivers.length, 2);
    assert.equal(drivers.every((driver) => driver.destroyed), true);
});
