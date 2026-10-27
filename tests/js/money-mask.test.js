import test from "node:test";
import assert from "node:assert/strict";

globalThis.window = {};
await import("../../resources/js/libs/money_mask.js");

const moneyMask = globalThis.window.moneyMask;

function makeCtx(overrides = {}) {
  return {
    note: null,
    mensagem: "",
    showMessage: false,
    limit: moneyMask.limit,
    clampToLimit: moneyMask.clampToLimit,
    init: moneyMask.init,
    mask: moneyMask.mask,
    setNote: moneyMask.setNote,
    handleBackspaceInput: moneyMask.handleBackspaceInput,
    showWarningMessage() {
      this.mensagem = `Valor maximo permitido: ${this.limit}`;
      this.showMessage = true;
    },
    ...overrides,
  };
}

function type(value, overrides = {}) {
  const ctx = makeCtx(overrides);
  ctx.note = value;
  ctx.mask({ target: { value } });
  return ctx;
}

test("mask limita a nota a 100 ao digitar/colar valores gigantes", () => {
  for (const raw of ["2000000000000", "99999999999999"]) {
    const ctx = type(raw);
    assert.equal(ctx.note, "100", `entrada ${raw}`);
    assert.equal(ctx.showMessage, true);
  }
});

test("mask clampa 150 para 100 e avisa", () => {
  const ctx = type("150");

  assert.equal(ctx.note, "100");
  assert.equal(ctx.showMessage, true);
});

test("mask mantém 100 sem aviso", () => {
  const ctx = type("100");

  assert.equal(ctx.note, "100");
  assert.equal(ctx.showMessage, false);
});

test("mask remove zeros à esquerda e mantém valores válidos", () => {
  assert.equal(type("085").note, "85");
  assert.equal(type("0").note, "0");
  assert.equal(type("5").note, "5");
  assert.equal(type("99").note, "99");
});

test("mask descarta separador decimal (nota inteira)", () => {
  // "8,5" ao ser digitado vira apenas os dígitos "85".
  assert.equal(type("8,5").note, "85");
});

test("mask deixa campo vazio vazio (não inventa 0)", () => {
  const ctx = type("");

  assert.equal(ctx.note, "");
  assert.equal(ctx.mensagem, "");
});

test("mask escreve o valor mascarado no próprio input", () => {
  const target = { value: "5000" };
  const ctx = makeCtx({ note: target.value });

  ctx.mask({ target });

  assert.equal(target.value, "100");
  assert.equal(ctx.note, "100");
});

test("handleBackspaceInput clampa valores acima do limite", () => {
  const ctx = makeCtx({ note: "2000000000000" });

  ctx.handleBackspaceInput();

  assert.equal(ctx.note, "100");
});

test("handleBackspaceInput apaga até esvaziar", () => {
  const one = makeCtx({ note: "8" });
  one.handleBackspaceInput();
  assert.equal(one.note, "");

  const two = makeCtx({ note: "85" });
  two.handleBackspaceInput();
  assert.equal(two.note, "8");
});

test("clampToLimit só intervém quando a nota excede 100", () => {
  const ctx = makeCtx();

  assert.equal(ctx.clampToLimit("2000000000000"), "100");
  assert.equal(ctx.clampToLimit("150"), "100");
  assert.equal(ctx.clampToLimit("100"), "100");
  assert.equal(ctx.clampToLimit("99"), "99");
  assert.equal(ctx.clampToLimit("8,5"), "8,5");
  assert.equal(ctx.clampToLimit(null), null);
  assert.equal(ctx.clampToLimit(""), "");
  assert.equal(ctx.clampToLimit("abc"), "abc");
});

test("init registra $watch e corrige valores externos", () => {
  const ctx = makeCtx();
  let handler = null;
  let watched = null;

  ctx.$watch = (property, callback) => {
    watched = property;
    handler = callback;
  };

  ctx.init();

  assert.equal(watched, "note");

  ctx.note = "2000000000000";
  handler(ctx.note);

  assert.equal(ctx.note, "100");

  const untouched = makeCtx();
  untouched.$watch = (_property, callback) => {
    handler = callback;
  };
  untouched.init();
  untouched.note = "99";
  handler(untouched.note);

  assert.equal(untouched.note, "99");
});
