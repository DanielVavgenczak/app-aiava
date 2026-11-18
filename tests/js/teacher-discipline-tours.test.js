import test from "node:test";
import assert from "node:assert/strict";
import { JSDOM } from "jsdom";

const realSetTimeout = globalThis.setTimeout;

function setupDom(markup = "") {
    const dom = new JSDOM(
        `<!DOCTYPE html><html><body>${markup}</body></html>`,
        {
            pretendToBeVisual: true,
            url: "http://localhost/professor/disciplina/d1",
        },
    );

    globalThis.window = dom.window;
    globalThis.document = dom.window.document;
    globalThis.CustomEvent = dom.window.CustomEvent;
    globalThis.MutationObserver = dom.window.MutationObserver;

    dom.window.HTMLElement.prototype.checkVisibility = function () {
        return this.style.display !== "none";
    };
    dom.window.HTMLElement.prototype.scrollIntoView = function () {};

    const store = {
        openMai: false,
        isTourActive: false,
        initTour: async () => {},
    };
    const drivers = [];
    const events = [];
    const alerts = [];
    const listeners = new Map();

    globalThis.Alpine = {
        store: () => store,
    };

    globalThis.Livewire = {
        on(name, handler) {
            const handlers = listeners.get(name) ?? [];
            handlers.push(handler);
            listeners.set(name, handlers);

            return () => {
                listeners.set(
                    name,
                    handlers.filter((item) => item !== handler),
                );
            };
        },
        dispatch(name, payload) {
            events.push({ name, payload });
            for (const handler of listeners.get(name) ?? []) {
                handler(payload);
            }
            return Promise.resolve();
        },
    };

    globalThis.alert = (message) => alerts.push(message);

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

        const popover = {
            footer,
            footerButtons: footer,
            nextButton,
            previousButton,
            progress,
        };

        const driver = {
            config,
            currentIndex: 0,
            destroyed: false,
            moveNextCalls: 0,
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
                driver.moveNextCalls += 1;
                await goToStep(driver.currentIndex + 1);
            },

            async movePrevious() {
                await goToStep(Math.max(driver.currentIndex - 1, 0));
            },

            async destroy() {
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

            const element = step.element
                ? document.querySelector(step.element)
                : null;

            await step.onHighlighted?.(element);
            driver.stepReady = true;
        }

        return driver;
    };

    return { dom, store, drivers, events, alerts };
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

const editDisciplineMarkup = `
    <div id="disciplines-all">
        <div class="discipline-card" id="discipline-1" data-tour="1">
            <button id="tour-operations-1"></button>
            <button class="tour-discipline-edit-1"></button>
        </div>
    </div>
    <dialog class="modal" open>
        <div id="discipline-edit-form-actions-1">
            <button id="discipline-edit-cancel-1"></button>
            <button id="discipline-edit-form-send-1"></button>
        </div>
    </dialog>
`;

const uploadMarkup = `
    <div id="accordion-module-m1"></div>
    <div id="open-accordion-block-m1"></div>
    <div id="block-open-new-block-m1"></div>
    <div class="options-m1">
        <a><div class="whitespace-nowrap">Video</div></a>
        <a><div class="whitespace-nowrap">Texto</div></a>
        <a><div class="whitespace-nowrap">Link</div></a>
    </div>
`;

const lessonMarkup = `
    <button id="content-lesson-create" data-tour="d1"></button>
    <div id="new-lesson-and-block">
        <input class="lesson-input-title" value="Aula 1" />
        <button type="button" id="module-input-cancel"></button>
        <button type="submit" id="module-input-create"></button>
        <div id="title_module_error"></div>
    </div>
`;

const managerMarkup = `
    <div id="disciplines-all">
        <div class="discipline-card" id="discipline-1" data-tour="1">
            <a id="discipline-manager-content-1"></a>
            <span id="card-discipline-count-module-1">Aulas: 0</span>
        </div>
    </div>
    <button id="content-lesson-create"></button>
`;

test("editar disciplina avança o tour secundário e reabre opções ao cancelar", async () => {
    const context = setupDom(editDisciplineMarkup);
    const restoreTimers = capTimers();

    try {
        const { StartTourEditDiscipline } = await import(
            "../../resources/js/tours/teacher/discipline/discipline.js"
        );

        const tour = StartTourEditDiscipline();
        await waitFor(
            () =>
                context.drivers.length >= 2 &&
                context.drivers[1].currentIndex >= 3 &&
                context.drivers[1].stepReady,
        );

        const secondDriver = context.drivers[1];
        assert.ok(secondDriver.moveNextCalls >= 3);
        assert.equal(context.drivers[0].destroyed, true);

        document.querySelector("#discipline-edit-cancel-1").click();
        await tour;

        assert.equal(secondDriver.destroyed, true);
        assert.equal(context.store.openMai, true);
        assert.ok(context.events.some((event) => event.name === "to-close"));
    } finally {
        restoreTimers();
    }
});

test("gerenciar conteúdo aguarda a página da disciplina antes de continuar", async () => {
    const context = setupDom(managerMarkup);
    const restoreTimers = capTimers();

    try {
        const { StartTourManagerDisciplineContent } = await import(
            "../../resources/js/tours/teacher/discipline/discipline.js"
        );

        const tour = StartTourManagerDisciplineContent();
        await waitFor(
            () =>
                context.drivers.length >= 2 &&
                context.events.some(
                    (event) => event.name === "continue-created-content",
                ),
        );

        const secondDriver = context.drivers[1];
        assert.ok(secondDriver.moveNextCalls >= 1);
        assert.equal(secondDriver.destroyed, true);

        await tour;
    } finally {
        restoreTimers();
    }
});

test("selecionar aba no bloco encontra os descendentes corretos", async () => {
    const context = setupDom(uploadMarkup);
    const restoreTimers = capTimers();

    try {
        const { ContinueUploadBlock } = await import(
            "../../resources/js/tours/teacher/discipline/lesson.js"
        );

        const tour = ContinueUploadBlock([], "m1");
        await waitFor(
            () =>
                context.drivers.length >= 1 &&
                context.drivers[0].currentIndex >= 3 &&
                context.drivers[0].stepReady,
        );

        document
            .querySelector(".options-m1 a div.whitespace-nowrap")
            .click();

        await waitFor(() => context.drivers.length >= 2);

        assert.match(
            context.drivers[1].config.steps[0].popover.description,
            /arquivo de vídeo/i,
        );
        await tour;
    } finally {
        restoreTimers();
    }
});

test("cancelar criação de aula fecha o modal do tour e reabre opções", async () => {
    const context = setupDom(lessonMarkup);
    const restoreTimers = capTimers();

    try {
        const { StartTourDisciplineCreateContent } = await import(
            "../../resources/js/tours/teacher/discipline/content.js"
        );

        const tour = StartTourDisciplineCreateContent(true);
        await waitFor(
            () =>
                context.drivers.length === 1 &&
                context.drivers[0].currentIndex === 1 &&
                context.drivers[0].stepReady,
        );

        document.querySelector("#module-input-cancel").click();
        await tour;

        assert.equal(context.drivers[0].destroyed, true);
        assert.equal(context.store.openMai, true);
        assert.ok(
            context.events.some(
                (event) =>
                    event.name === "tour-teacher::close-modal-new-lesson",
            ),
        );
    } finally {
        restoreTimers();
    }
});

test("erro de título duplicado mantém o tour e usa a mensagem do sistema", async () => {
    const context = setupDom(lessonMarkup);
    const restoreTimers = capTimers();

    try {
        document.querySelector("#title_module_error").textContent =
            "Já existe uma aula com este nome nesta disciplina.";

        const { StartTourDisciplineCreateContent } = await import(
            "../../resources/js/tours/teacher/discipline/content.js"
        );

        const tour = StartTourDisciplineCreateContent(true);
        await waitFor(
            () =>
                context.drivers.length === 1 &&
                context.drivers[0].currentIndex === 1 &&
                context.drivers[0].stepReady,
        );

        document.querySelector("#module-input-create").click();
        await waitFor(() => context.alerts.length === 1);

        assert.equal(context.alerts[0], "Já existe uma aula com este nome nesta disciplina.");
        assert.equal(context.drivers[0].destroyed, false);
        assert.equal(
            context.events.some((event) => event.name === "to-teste-register"),
            false,
        );

        document.querySelector("#module-input-cancel").click();
        await tour;
    } finally {
        restoreTimers();
    }
});
