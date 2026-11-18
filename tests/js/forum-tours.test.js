import test from "node:test";
import assert from "node:assert/strict";
import { readFile } from "node:fs/promises";
import { JSDOM } from "jsdom";

const realSetTimeout = globalThis.setTimeout;

function setupDom(markup = "") {
    const dom = new JSDOM(
        `<!DOCTYPE html><html><body>${markup}</body></html>`,
        { pretendToBeVisual: true, url: "http://localhost/professor" },
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
    const navigations = [];

    globalThis.Alpine = { store: () => store };
    globalThis.Livewire = {
        dispatch(name, payload) {
            events.push({ name, payload });
        },
        navigate(url) {
            navigations.push(url);
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

            async movePrevious() {
                await goToStep(Math.max(0, driver.currentIndex - 1));
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

    return { store, drivers, events, navigations };
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

function findButton(label) {
    return Array.from(document.querySelectorAll("button")).find(
        (button) => button.textContent === label || button.innerText === label,
    );
}

async function advanceTo(driver, index) {
    while (driver.currentIndex < index) {
        await driver.moveNext();
    }
}

test("registro de tours não referencia o tour de respostas inexistente", async () => {
    const registry = await readFile(
        new URL("../../resources/js/tours/tour.js", import.meta.url),
        "utf8",
    );
    const teacherForum = await readFile(
        new URL("../../resources/js/tours/teacher/forum/forum.js", import.meta.url),
        "utf8",
    );

    assert.doesNotMatch(registry, /StartTourTeacherForumReplies\b/);
    assert.doesNotMatch(teacherForum, /stageCursor/);
});

test("tour inicial do fórum reabre opções quando não há disciplinas", async () => {
    const context = setupDom(`<div id="forum-discipline-grid"></div>`);

    try {
        const { StartTourTeacherForumInteract } = await import(
            "../../resources/js/tours/teacher/forum/forum.js?forum-interact-empty"
        );

        await StartTourTeacherForumInteract();

        assert.equal(context.drivers.length, 0);
        assert.equal(context.store.openMai, true);
    } finally {
        delete globalThis.Livewire.navigate;
    }
});

test("tour de novo tópico do professor reabre opções quando o botão não existe", async () => {
    const context = setupDom("");

    const { StartTourTeacherForumShowNewTopic } = await import(
        "../../resources/js/tours/teacher/forum/forum.js?forum-new-topic-missing"
    );

    await StartTourTeacherForumShowNewTopic();

    assert.equal(context.drivers.length, 0);
    assert.equal(context.store.openMai, true);
});

test("botão de cancelar do tour de novo tópico fecha o modal e encerra o driver", async () => {
    const context = setupDom(`
        <button id="forum-show-new-topic"></button>
        <div id="input-register-topic"></div>
    `);
    const restoreTimers = capTimers();

    try {
        const { StartTourTeacherForumShowNewTopic } = await import(
            "../../resources/js/tours/teacher/forum/forum.js?forum-new-topic-cancel"
        );

        const tour = StartTourTeacherForumShowNewTopic();
        await waitFor(() => context.drivers.length === 1 && context.drivers[0].currentIndex >= 1);

        const driver = context.drivers[0];
        const cancelButton = findButton("Cancelar operação");
        assert.ok(cancelButton);
        cancelButton.click();

        await waitFor(() => driver.destroyed && context.store.openMai === true);
        assert.ok(
            context.events.some(
                (event) => event.name === "teacher::close-create-forum-topic",
            ),
        );
        await tour;
    } finally {
        restoreTimers();
    }
});

test("seleção de tópico do professor ignora botões de ação e navega uma vez", async () => {
    const context = setupDom(`
        <div id="forum-show-all-topics">
            <div class="topic-item">
                <a href="/topico/1"></a>
                <button type="button">Excluir</button>
            </div>
        </div>
    `);

    try {
        const { StartTourTeacherForumShowSelectTopic } = await import(
            "../../resources/js/tours/teacher/forum/forum.js?forum-select-topic-actions"
        );

        const tour = StartTourTeacherForumShowSelectTopic();
        await waitFor(() => context.drivers.length === 1 && context.drivers[0].stepReady);

        const topic = document.querySelector(".topic-item");
        topic.querySelector("button").click();
        await new Promise((resolve) => realSetTimeout(resolve, 10));
        assert.deepEqual(context.navigations, []);
        assert.equal(context.store.openMai, false);

        topic.click();
        await waitFor(() => context.drivers[0].destroyed);
        assert.equal(context.navigations.length, 1);
        assert.match(context.navigations[0], /\/topico\/1$/);
        assert.equal(context.store.openMai, false);
        await tour;
    } finally {
        delete globalThis.Livewire.navigate;
    }
});

test("tour de tópicos do aluno aponta para o seletor real e o primeiro tópico visível", async () => {
    const context = setupDom(`
        <div id="forum-show-breadcrumb"></div>
        <div id="forum-show-header"></div>
        <select id="forum-show-per-page"></select>
        <button id="forum-show-new-topic"></button>
        <div id="forum-show-tabs"></div>
        <div id="forum-show-all-topics">
            <div class="topic-item"></div>
        </div>
    `);

    try {
        const { StartTourStudentForumShow } = await import(
            "../../resources/js/tours/student/forum.js?forum-show-targets"
        );

        const tour = StartTourStudentForumShow();
        await waitFor(() => context.drivers.length === 1 && context.drivers[0].stepReady);

        const driver = context.drivers[0];
        assert.equal(driver.config.steps[2].element, "#forum-show-per-page");
        assert.equal(driver.config.steps[5].element(), document.querySelector(".topic-item"));

        await advanceTo(driver, driver.config.steps.length - 1);
        const finishButton = findButton("Finalizar Tour");
        assert.ok(finishButton);
        finishButton.click();
        await waitFor(() => driver.destroyed && context.store.openMai === true);
        await tour;
    } finally {
        delete globalThis.Livewire.navigate;
    }
});

test("tour de respostas do aluno aponta para a lista sempre renderizada", async () => {
    const context = setupDom(`
        <div id="forum-replies-breadcrumb"></div>
        <div id="forum-replies-header"></div>
        <button id="forum-replies-new-btn"></button>
        <div id="forum-replies-list"></div>
    `);

    try {
        const { StartTourStudentForumReplies } = await import(
            "../../resources/js/tours/student/forum.js?forum-replies-target"
        );

        const tour = StartTourStudentForumReplies();
        await waitFor(() => context.drivers.length === 1 && context.drivers[0].stepReady);

        const driver = context.drivers[0];
        assert.equal(driver.config.steps[3].element, "#forum-replies-list");

        await advanceTo(driver, driver.config.steps.length - 1);
        const finishButton = findButton("Finalizar Tour");
        assert.ok(finishButton);
        finishButton.click();
        await waitFor(() => driver.destroyed && context.store.openMai === true);
        await tour;
    } finally {
        delete globalThis.Livewire.navigate;
    }
});
