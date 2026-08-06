/**
 * Shared HTML-safety helpers for template-literal components.
 *
 * Every component in this design system builds markup with template literals
 * and assigns it via `innerHTML`. Components are ported into consumer sites
 * where the values come from a CMS or API, so every interpolated value is
 * untrusted input — not just the obviously user-facing ones.
 *
 * These used to be copy-pasted into each component file. One shared pair means
 * a fix lands everywhere at once, which matters for a security boundary.
 */

/**
 * Escape a value for interpolation into HTML text or a quoted attribute.
 * Covers both contexts: `&`, `<`, `>` for text nodes; `"` and `'` for attrs.
 *
 * @param {unknown} value
 * @returns {string}
 */
export const esc = (value = '') =>
  String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');

/**
 * Allow-list URL schemes before a value reaches an `href` or `src`.
 *
 * An allow-list, not a block-list: blocking `javascript:` alone is defeated by
 * whitespace, casing and entity tricks, whereas anything that fails to match a
 * known-good prefix is simply replaced. Relative paths and fragments pass.
 *
 * @param {string} url
 * @param {{ allowData?: boolean }} [options] allowData permits `data:image/…`,
 *   needed for inline image sources. Off by default — `data:` can carry HTML
 *   and SVG script payloads.
 * @returns {string} the URL, or '#' if its scheme is not allowed
 */
export const safeUrl = (url = '#', { allowData = false } = {}) => {
  const value = String(url).trim();
  const pattern = allowData
    ? /^(https?:|mailto:|tel:|data:image\/(?:png|jpe?g|gif|webp|avif);|\/|#|\.\/|\.\.\/)/i
    : /^(https?:|mailto:|tel:|\/|#|\.\/|\.\.\/)/i;
  return pattern.test(value) ? value : '#';
};
