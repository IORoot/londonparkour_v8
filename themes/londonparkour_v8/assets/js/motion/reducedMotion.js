/**
 * Single source of truth for `prefers-reduced-motion`.
 *
 * Every effect in this layer must resolve instantly to its end state when
 * this preference is active (see index.js) — the guard lives here, once, so
 * no individual effect file re-implements its own media-query check.
 */

const query =
  typeof window !== 'undefined' && typeof window.matchMedia === 'function'
    ? window.matchMedia('(prefers-reduced-motion: reduce)')
    : null;

/** Read the current preference synchronously. */
export function prefersReducedMotion() {
  return query ? query.matches : false;
}

/**
 * Calls `cb(matches)` whenever the OS-level preference flips at runtime
 * (not just at page load). Returns an unsubscribe function.
 */
export function watchReducedMotion(cb) {
  if (!query) return () => {};
  const handler = () => cb(query.matches);
  query.addEventListener('change', handler);
  return () => query.removeEventListener('change', handler);
}
