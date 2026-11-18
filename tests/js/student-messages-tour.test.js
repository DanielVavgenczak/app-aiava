import test from "node:test";
import assert from "node:assert/strict";
import { JSDOM } from "jsdom";

const realSetTimeout = globalThis.setTimeout;

function setupDom(markup = "") {
    const dom = new JSDOM(
        `<!DOCTYPE html><html><body>${markup}</body></html>`,
        { pretendToBeVisual: true, url: "http://localhost/aluno/mensagens" },
    );

    globalThis.window = dom.window;
    globalThis.document = dom.window.document;
    globalThis.CustomEvent = dom.window.CustomEvent;

    const store = { openMai: false, isTourActive: false };
    const drivers = [];

    globalThis.Alpine = { store: () => store };

    globalThis.drivertour = (config) => {
        const footer = document.createElement("div");
        footer.style.display = "none";
        document.body.appendChild(footer);

        const popover = {
            footer,
            footerButtons: footer,
            nextButton: document.createElement("button"),
            previousButton: document.createElement("button"),
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

    return { store, drivers };
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

const messagesMarkup = `
    <div id="student-messages-header-7"></div>
    <div id="chat-sidebar-7">
        <button id="group-chat-7"></button>
        <button id="teacher-chat-7"></button>
        <div id="classmates-list-7"></div>
        <button id="classmate-8"></button>
    </div>
    <div id="chat-area-7"></div>
    <div id="message-input-7"><input /></div>
`;

test("iniciar conversa no chat do aluno seleciona contato e leva ao campo de mensagem", async () => {
    const context = setupDom(messagesMarkup);
    const restoreTimers = capTimers();

    try {
        const clicked = [];
        document
            .querySelector("#group-chat-7")
            .addEventListener("click", () => clicked.push("group"));

        const { StartTourInitChatInClasse } = await import(
            "../../resources/js/tours/student/messages.js?student-chat-start"
        );

        const tour = StartTourInitChatInClasse();
        await waitFor(
            () => context.drivers.length === 1 && context.drivers[0].stepReady,
        );

        assert.match(
            context.drivers[0].config.steps[0].popover.description,
            /iniciar uma conversa/i,
        );

        document.querySelector("#group-chat-7").click();

        await waitFor(
            () =>
                context.drivers.length === 2 &&
                context.drivers[1].currentIndex >= 1 &&
                context.drivers[1].stepReady,
        );

        assert.deepEqual(clicked, ["group"]);
        assert.equal(context.drivers[0].destroyed, true);
        assert.equal(
            context.drivers[1].config.steps[1].element,
            "#message-input-7",
        );

        await waitFor(() => context.store.openMai === true);
        await tour;
    } finally {
        restoreTimers();
    }
});
