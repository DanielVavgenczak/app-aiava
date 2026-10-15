import test from "node:test";
import assert from "node:assert/strict";
import { JSDOM } from "jsdom";

import {
  configDriver,
  defaultDriverConfig,
  isElementHidden,
  waitForVideoPreview,
  waitForUploadComplete,
  whenElementHidden,
} from "../../resources/js/tours/utils.js";

function setupDom(bodyHtml = "") {
  const dom = new JSDOM(
    `<!DOCTYPE html><html><body>${bodyHtml}</body></html>`,
    { pretendToBeVisual: true },
  );

  globalThis.window = dom.window;
  globalThis.document = dom.window.document;
  globalThis.MutationObserver = dom.window.MutationObserver;

  return dom;
}

const videoUploadMarkup = (moduleId, { warningDisplay = "none" } = {}) => `
  <video id="temp-video-preview-${moduleId}"></video>
  <div id="message-warning-duration-${moduleId}" style="display:${warningDisplay}"></div>
`;

test("configDriver usa apenas chaves válidas do driver.js", () => {
  for (const config of [configDriver, defaultDriverConfig]) {
    assert.equal(config.overlayOpacity, 0.75);
    assert.equal(config.stagePadding, 10);
    assert.equal("opacity" in config, false);
    assert.equal("padding" in config, false);
    assert.equal("closeBtnText" in config, false);
  }
});

test("isElementHidden trata ausência, insline display:none e visibilidade", () => {
  setupDom(`
    <div id="none-el" style="display:none"></div>
    <div id="visible-el"></div>
  `);

  assert.equal(isElementHidden(null), true);
  assert.equal(isElementHidden(document.getElementById("none-el")), true);
  assert.equal(isElementHidden(document.getElementById("visible-el")), false);
});

test("waitForVideoPreview resolve quando o src do preview é definido", async () => {
  setupDom(videoUploadMarkup("m1"));

  const promise = waitForVideoPreview("m1", { timeout: 2000 });
  setTimeout(() => {
    document
      .getElementById("temp-video-preview-m1")
      .setAttribute("src", "blob:video-1");
  }, 10);

  const preview = await promise;
  assert.equal(preview.getAttribute("src"), "blob:video-1");
});

test("waitForVideoPreview resolve imediatamente se o src já existe", async () => {
  setupDom(
    `<video id="temp-video-preview-m2" src="blob:ja-existente"></video>`,
  );

  const preview = await waitForVideoPreview("m2", { timeout: 2000 });
  assert.equal(preview.getAttribute("src"), "blob:ja-existente");
});

test("waitForVideoPreview rejeita quando o aviso de duração fica visível", async () => {
  setupDom(videoUploadMarkup("m3"));

  const promise = waitForVideoPreview("m3", { timeout: 2000 });
  setTimeout(() => {
    document.getElementById("message-warning-duration-m3").style.display =
      "block";
  }, 10);

  await assert.rejects(promise, /limite de duração/i);
});

test("waitForVideoPreview rejeita no timeout quando nada acontece", async () => {
  setupDom(videoUploadMarkup("m4"));

  await assert.rejects(
    waitForVideoPreview("m4", { timeout: 50 }),
    /não apareceu após 50ms/i,
  );
});

test("waitForUploadComplete resolve no evento do módulo correto e remove o listener", async () => {
  setupDom();

  let handler = null;
  let offCalled = false;

  globalThis.Livewire = {
    on(name, callback) {
      assert.equal(name, "after-video-upload");
      handler = callback;
      return () => {
        offCalled = true;
      };
    },
  };

  const promise = waitForUploadComplete("mod-1", { timeout: 1000 });
  handler({ module_id: "mod-outro", lesson_id: 99 });
  handler({ module_id: "mod-1", lesson_id: 42 });

  const event = await promise;
  assert.equal(event.lesson_id, 42);
  assert.equal(offCalled, true);
});

test("waitForUploadComplete ignora outros módulos e rejeita no timeout", async () => {
  setupDom();

  let handler = null;
  globalThis.Livewire = {
    on(_name, callback) {
      handler = callback;
      return () => {};
    },
  };

  const promise = waitForUploadComplete("mod-x", { timeout: 50 });
  handler({ module_id: "mod-y", lesson_id: 1 });

  await assert.rejects(promise, /não confirmado após 50ms/i);
});

test("waitForUploadComplete rejeita quando o Livewire não está disponível", async () => {
  setupDom();
  globalThis.Livewire = undefined;

  await assert.rejects(
    waitForUploadComplete("mod-1", { timeout: 1000 }),
    /Livewire não está disponível/i,
  );
});

test("whenElementHidden dispara callback quando o elemento fica oculto", async () => {
  setupDom(`<div id="progress" style="display:block"></div>`);

  const element = document.getElementById("progress");
  let fired = false;

  whenElementHidden(element, () => {
    fired = true;
  });

  element.style.display = "none";

  await new Promise((resolve) => setTimeout(resolve, 20));
  assert.equal(fired, true);
});

test("whenElementHidden não dispara callback no timeout", async () => {
  setupDom(`<div id="progress"></div>`);

  const element = document.getElementById("progress");
  let fired = false;

  whenElementHidden(
    element,
    () => {
      fired = true;
    },
    { timeout: 20 },
  );

  await new Promise((resolve) => setTimeout(resolve, 40));
  assert.equal(fired, false);
});
