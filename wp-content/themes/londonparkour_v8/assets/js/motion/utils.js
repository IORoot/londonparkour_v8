/**
 * Tiny shared helpers for reading `data-motion-*` attributes. Native
 * `dataset` already turns `data-motion-enter-duration` into
 * `dataset.motionEnterDuration` — this just adds a numeric-with-fallback
 * parse on top, used by every effect.
 */

/** Parse a dataset value as a float, falling back when absent/invalid. */
export function num(value, fallback) {
  const n = parseFloat(value);
  return Number.isFinite(n) ? n : fallback;
}
