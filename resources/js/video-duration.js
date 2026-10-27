export const MIN_VIDEO_DURATION_SECONDS = 11;
export const MAX_VIDEO_DURATION_SECONDS = 12 * 60;

export const MIN_VIDEO_DURATION_MESSAGE =
  "O vídeo deve ter pelo menos 11 segundos.";
export const MAX_VIDEO_DURATION_MESSAGE =
  "O vídeo excede o limite de 12 minutos.";

export function videoDurationError(durationInSeconds) {
  if (!Number.isFinite(durationInSeconds)) {
    return null;
  }

  if (durationInSeconds < MIN_VIDEO_DURATION_SECONDS) {
    return MIN_VIDEO_DURATION_MESSAGE;
  }

  if (durationInSeconds > MAX_VIDEO_DURATION_SECONDS) {
    return MAX_VIDEO_DURATION_MESSAGE;
  }

  return null;
}
