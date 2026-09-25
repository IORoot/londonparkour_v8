/**
 * Focal point for a cover crop and a Ken Burns zoom.
 *
 * One "x% y%" value. 50% 50% is the middle of the photograph. The same point
 * is the object-position (what stays in frame as the hero changes shape) and
 * the transform-origin (where the zoom travels).
 *
 * @param {string} origin
 * @returns {string}
 */
export function focalPoint(origin) {
  const match = String(origin || '').trim().match(/^(\d{1,3}(?:\.\d+)?)%\s+(\d{1,3}(?:\.\d+)?)%$/);
  if (!match) return '50% 50%';
  const clamp = (value) => Math.min(100, Math.max(0, Number(value)));
  const format = (value) => {
    const n = clamp(value);
    return String(Math.round(n * 10) / 10).replace(/\.0$/, '');
  };
  return `${format(match[1])}% ${format(match[2])}%`;
}
