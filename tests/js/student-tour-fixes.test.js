import test from "node:test";
import assert from "node:assert/strict";
import { JSDOM } from "jsdom";

const realSetTimeout = globalThis.setTimeout;

function setupDom(markup = "") {
    const dom = new JSDOM(
        `<!DOCTYPE html><html><body>${markup}</body></html>`,
        { pretendToBeVisual: true, url: "http://localhost/aluno" },
    );

    globalThis.window = dom.window;
    globalThis.document = dom.window.document;
    globalThis.CustomEvent = dom.window.CustomEvent;
    globalThis.MutationObserver = dom.window.MutationObserver;

    Object.defineProperty(dom.window.HTMLElement.prototype, "offsetParent", {
        configurable: true,
        get() {
            return this.style.display !== "none" && !this.hidden
                ? dom.window.document.body
                : null;
        },
    });
    dom.window.HTMLElement.prototype.checkVisibility = function checkVisibility() {
        return this.style.display !== "none" && !this.hidden;
    };

    const store = { openMai: false, isTourActive: false };
    const drivers = [];
    const events = [];

    globalThis.Alpine = { store: () => store };
    globalThis.Livewire = {
        dispatch(name, payload) {
            events.push({ name, payload });
        },
    };
    dom.window.Livewire = globalThis.Livewire;
    globalThis.alert = () => {};

    globalThis.drivertour = (config) => {
        const footer = document.createElement("div");
        footer.style.display = "none";
        footer.append(document.createElement("button"));
        footer.append(document.createElement("button"));
        document.body.append(footer);

        const popover = {
            footer,
            footerButtons: footer,
            nextButton: footer.firstChild,
            previousButton: footer.lastChild,
        };

        const driver = {
            config,
            currentIndex: 0,
            destroyed: false,
            stepReady: false,

            async drive() {
                drivers.push(driver);
                const completion = new Promise((resolve) => {
                    driver.resolveDrive = resolve;
                });

                await goToStep(0);
                return completion;
            },

            async moveNext() {
                await goToStep(driver.currentIndex + 1);
            },

            async destroy() {
                if (driver.destroyed) return;
                driver.destroyed = true;
                footer.remove();
                config.onDestroyed?.();
                driver.resolveDrive?.();
            },
        };

        async function goToStep(index) {
            driver.currentIndex = index;
            driver.stepReady = false;
            const step = config.steps[index];

            if (!step) {
                await driver.destroy();
                return;
            }

            step.popover?.onPopoverRender?.(popover, { config, state: {} });
            const element = typeof step.element === "function"
                ? step.element()
                : step.element instanceof dom.window.Element
                    ? step.element
                    : step.element
                        ? document.querySelector(step.element)
                        : null;

            await step.onHighlighted?.(element);
            driver.stepReady = true;
        }

        return driver;
    };

    return { store, drivers, events };
}

function capTimers() {
    globalThis.setTimeout = (callback, delay, ...args) =>
        realSetTimeout(callback, Math.min(delay, 2), ...args);

    return () => {
        globalThis.setTimeout = realSetTimeout;
    };
}

async function waitFor(condition, timeout = 2000) {
    const startedAt = Date.now();

    while (!condition()) {
        if (Date.now() - startedAt > timeout) {
            throw new Error("Timeout aguardando condição do tour");
        }

        await new Promise((resolve) => realSetTimeout(resolve, 5));
    }
}

test("tour de novo tópico usa campos reais e fecha pelo evento do componente", async () => {
    const context = setupDom(`
        <button id="forum-show-new-topic"></button>
        <div id="forum-input-title"></div>
        <div id="forum-input-class"></div>
        <div id="forum-input-actions"></div>
    `);
    const restoreTimers = capTimers();

    try {
        const { StartTourStudentForumShowNewTopic } = await import(
            "../../resources/js/tours/student/forum.js?student-forum-new-topic-fix"
        );

        const tour = StartTourStudentForumShowNewTopic();
        await waitFor(
            () => context.drivers.length === 1 && context.drivers[0].currentIndex >= 1,
        );

        const driver = context.drivers[0];
        assert.equal(driver.config.steps[1].element, "#forum-input-title");
        assert.equal(driver.config.steps[2].element, "#forum-input-class");
        assert.equal(driver.config.steps[3].element, "#forum-input-actions");

        const closeButton = Array.from(document.querySelectorAll("button")).find(
            (button) =>
                button.textContent === "Cancelar e encerrar" ||
                button.innerText === "Cancelar e encerrar",
        );
        assert.ok(closeButton);
        closeButton.click();

        await waitFor(() => driver.destroyed && context.store.openMai === true);
        assert.deepEqual(context.events, [
            { name: "student::close-new-topic-forum", payload: undefined },
        ]);
        await tour;
    } finally {
        restoreTimers();
    }
});

test("tour de atividades da disciplina usa o drawer real sem driver obsoleto", async () => {
    const context = setupDom(`
        <div id="discipline-show-modules-sidebar">
            <div class="lesson-card" data-tour="m1" id="accordion-m1">
                <div id="accordion-module-m1"></div>
                <button id="open-accordion-block-m1"></button>
                <div id="content-activitie-lesson-m1">
                    <button class="tasks-open" id="open-activity-a1"></button>
                </div>
            </div>
        </div>
        <div id="student-activity-drawer"></div>
    `);
    const restoreTimers = capTimers();
    const clicked = [];
    document
        .querySelector("#open-activity-a1")
        .addEventListener("click", () => clicked.push("activity"));

    try {
        const { StartTourStudentDisciplineShowActivitie } = await import(
            "../../resources/js/tours/student/discipline.js?student-discipline-activity-fix"
        );

        const tour = StartTourStudentDisciplineShowActivitie();
        await waitFor(
            () => context.drivers.length === 2 && context.drivers[1].destroyed,
        );

        const firstDriver = context.drivers[0];
        const secondDriver = context.drivers[1];

        assert.equal(firstDriver.destroyed, true);
        assert.equal(secondDriver.config.steps[3].element, "#student-activity-drawer");
        assert.deepEqual(clicked, ["activity"]);
        assert.equal(context.store.openMai, true);
        await tour;
    } finally {
        restoreTimers();
    }
});

test("tour de início de conversa reabre opções quando a sidebar não existe", async () => {
    const context = setupDom(`<div id="student-messages-header-7"></div>`);
    const restoreTimers = capTimers();

    try {
        const { StartTourInitChatInClasse } = await import(
            "../../resources/js/tours/student/messages.js?student-chat-missing-sidebar"
        );

        await StartTourInitChatInClasse();

        assert.equal(context.drivers.length, 1);
        assert.equal(context.drivers[0].destroyed, true);
        assert.equal(context.store.openMai, true);
    } finally {
        restoreTimers();
    }
});

test("tour de resposta mantém opções fechadas ao passar para o drawer", async () => {
    const context = setupDom(`
        <button class="tasks-open"></button>
        <div id="student-activity-drawer">
            <div data-tour="student-activity-question"></div>
            <button id="student-activity-submit"></button>
        </div>
    `);
    const restoreTimers = capTimers();

    try {
        const { StartTourStudentAnswerActivity } = await import(
            "../../resources/js/tours/student/discipline.js?student-answer-handoff"
        );

        const tour = StartTourStudentAnswerActivity();
        await waitFor(() => context.drivers.length === 2 && context.drivers[1].stepReady);

        assert.equal(context.drivers[0].destroyed, true);
        assert.equal(context.store.openMai, false);

        const finishButton = Array.from(document.querySelectorAll("button")).find(
            (button) =>
                button.textContent === "Encerrar tour" ||
                button.innerText === "Encerrar tour",
        );
        assert.ok(finishButton);
        finishButton.click();

        await waitFor(() => context.drivers[1].destroyed && context.store.openMai === true);
        await tour;
    } finally {
        restoreTimers();
    }
});

test("tour de seleção de tópico navega sem reabrir as opções", async () => {
    const context = setupDom(`
        <div id="forum-show-all-topics">
            <div class="topic-item"><a href="/topico/1"></a></div>
        </div>
    `);
    let navigated = null;
    globalThis.Livewire.navigate = (url) => {
        navigated = url;
    };

    try {
        const { StartTourStudentForumShowSelectTopic } = await import(
            "../../resources/js/tours/student/forum.js?student-forum-navigation"
        );

        const tour = StartTourStudentForumShowSelectTopic();
        await waitFor(() => context.drivers.length === 1 && context.drivers[0].stepReady);

        document.querySelector(".topic-item").click();

        await waitFor(
            () => context.drivers[0].destroyed && navigated === "http://localhost/topico/1",
        );
        assert.equal(context.store.openMai, false);
        await tour;
    } finally {
        delete globalThis.Livewire.navigate;
    }
});
