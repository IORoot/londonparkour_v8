/**
 * WCAG 1.4.13 — Escape dismisses an open site-nav flyout and returns focus to
 * its trigger link. The flyouts are pure CSS (hover / focus-within); this only
 * sets `data-dismissed` on the group, which nav.php's
 * `group-data-[dismissed]/<key>:hidden!` classes consume. The dismissal lasts
 * until pointer or focus leaves the group. Port of SiteNav.js's handler; listens
 * on document so a hover-opened flyout closes while focus is elsewhere.
 */
export function initNavDismiss(root = document) {
  const groups = [...root.querySelectorAll('[data-component="site-nav"] [data-nav-panel]')]
    .map((panel) => panel.parentElement);
  if (!groups.length) return null;

  const onKeydown = (e) => {
    if (e.key !== 'Escape') return;
    const group = groups.find((g) => g.matches(':hover') || g.contains(document.activeElement));
    if (!group) return;
    group.setAttribute('data-dismissed', '');
    group.querySelector('a')?.focus();
  };
  const clear = (e) => {
    if (e.type === 'focusout' && e.currentTarget.contains(e.relatedTarget)) return;
    e.currentTarget.removeAttribute('data-dismissed');
  };

  document.addEventListener('keydown', onKeydown);
  groups.forEach((g) => {
    g.addEventListener('mouseleave', clear);
    g.addEventListener('focusout', clear);
  });

  return {
    cleanup: () => {
      document.removeEventListener('keydown', onKeydown);
      groups.forEach((g) => {
        g.removeEventListener('mouseleave', clear);
        g.removeEventListener('focusout', clear);
      });
    }
  };
}
