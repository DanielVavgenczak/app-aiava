import test from "node:test";
import assert from "node:assert/strict";

import {
  MAX_VIDEO_DURATION_MESSAGE,
  MIN_VIDEO_DURATION_MESSAGE,
  videoDurationError,
} from "../../resources/js/video-duration.js";

test("rejeita vídeos com menos de 11 segundos", () => {
  assert.equal(videoDurationError(10), MIN_VIDEO_DURATION_MESSAGE);
  assert.equal(videoDurationError(0), MIN_VIDEO_DURATION_MESSAGE);
});

test("aceita vídeos entre 11 segundos e 12 minutos", () => {
  assert.equal(videoDurationError(11), null);
  assert.equal(videoDurationError(60), null);
  assert.equal(videoDurationError(720), null);
});

test("rejeita vídeos com mais de 12 minutos", () => {
  assert.equal(videoDurationError(721), MAX_VIDEO_DURATION_MESSAGE);
});

test("não valida quando a duração não pôde ser determinada", () => {
  assert.equal(videoDurationError(NaN), null);
  assert.equal(videoDurationError(Infinity), null);
});
