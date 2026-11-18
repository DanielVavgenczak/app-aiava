import test from "node:test";
import assert from "node:assert/strict";
import { JSDOM } from "jsdom";

async function waitFor(condition, timeout = 1000) {
    const startedAt = Date.now();

    while (!condition()) {
        if (Date.now() - startedAt > timeout) {
            throw new Error("Timeout aguardando condição do tour");
        }

        await new Promise((resolve) => setTimeout(resolve, 5));
    }
}

test("tour do perfil do professor reabre as opções ao finalizar", async () => {
    const dom = new JSDOM(
        `<!DOCTYPE html><html><body>
            <div id="teacher-profile-avatar"></div>
            <button id="btn-edit-profile"></button>
            <button id="btn-open-password-change"></button>
        </body></html>`,
        { pretendToBeVisual: true, url: "http://localhost/professor/perfil" },
    );

    globalThis.window = dom.window;
    globalThis.document = dom.window.document;
    globalThis.CustomEvent = dom.window.CustomEvent;

    const store = { openMai: false, isTourActive: false };
    let driver;

    globalThis.Alpine = { store: () => store };
    globalThis.drivertour = (config) => {
        const footer = document.createElement("div");
        footer.append(document.createElement("button"));
        footer.append(document.createElement("button"));
        document.body.append(footer);

        driver = {
            config,
            currentIndex: 0,
            destroyed: false,
            stepReady: false,

            async drive() {
                await goToStep(0);
                return new Promise((resolve) => {
                    driver.resolveDrive = resolve;
                });
            },

            async moveNext() {
                await goToStep(driver.currentIndex + 1);
            },

            async destroy() {
                driver.destroyed = true;
                config.onDestroyed?.();
                driver.resolveDrive?.();
            },
        };

        async function goToStep(index) {
            driver.currentIndex = index;
            const step = config.steps[index];
            if (!step) {
                await driver.destroy();
                return;
            }

            step.popover?.onPopoverRender?.(
                {
                    footerButtons: footer,
                    nextButton: footer.firstChild,
                    previousButton: footer.lastChild,
                },
                { config, state: {} },
            );
            driver.stepReady = true;
        }

        return driver;
    };

    const { StartTourTeacherProfile } = await import(
        "../../resources/js/tours/teacher/profile.js?teacher-profile-tour"
    );

    const tour = StartTourTeacherProfile();
    await waitFor(() => driver?.config.steps.length === 3);

    await driver.moveNext();
    await driver.moveNext();

    const finishButton = Array.from(document.querySelectorAll("button")).find(
        (button) =>
            button.textContent === "Finalizar Tour" ||
            button.innerText === "Finalizar Tour",
    );
    assert.ok(finishButton);

    finishButton.click();
    await tour;

    assert.equal(driver.destroyed, true);
    assert.equal(store.openMai, true);
});
