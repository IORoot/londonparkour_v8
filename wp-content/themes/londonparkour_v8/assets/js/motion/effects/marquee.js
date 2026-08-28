/**
 * `data-motion-marquee` — infinite horizontal loop. The container's content
 * is cloned once so the two copies can hand off seamlessly, then driven by a
 * single linear, infinitely-repeating `animate()` call.
 *
 * Optional `data-motion-marquee-mq` (a CSS media query) starts the loop only
 * while that query matches, so a mobile-only ticker can sit next to a static
 * desktop grid without cloning or translating a `display:none` track.
 */
import { animate } from 'motion';
import { num } from '../utils.js';

export const marqueeEffect = {
  selector: '[data-motion-marquee]',
  init(el, { reduced }) {
    // An infinite loop is precisely the kind of motion prefers-reduced-motion
    // opts out of — leave the (already-static) content as authored.
    if (reduced) return;

    const start = () => {
      if (!el.dataset.motionMarqueeCloned) {
        // Clone existing child nodes (not innerHTML/insertAdjacentHTML — no
        // HTML re-parsing, so this can't become an injection sink even though
        // the content here is already-trusted, same-page DOM).
        Array.from(el.childNodes).forEach((node) => el.appendChild(node.cloneNode(true)));
        el.dataset.motionMarqueeCloned = 'true';
      }

      const direction = el.dataset.motionMarqueeDirection === 'right' ? 1 : -1;
      const speed = num(el.dataset.motionMarqueeSpeed, 60); // px/sec
      const distance = el.scrollWidth / 2;
      if (!distance) return () => {};

      const duration = distance / speed;

      const controls = animate(
        el,
        { x: direction === -1 ? [0, -distance] : [-distance, 0] },
        { duration, ease: 'linear', repeat: Infinity }
      );

      return () => {
        controls.stop();
        el.style.transform = 'none';
      };
    };

    const mqQuery = el.dataset.motionMarqueeMq;
    if (!mqQuery) {
      return start();
    }

    const mq = window.matchMedia(mqQuery);
    let stop = mq.matches ? start() : null;
    const onChange = () => {
      stop?.();
      stop = mq.matches ? start() : null;
    };
    mq.addEventListener('change', onChange);
    return () => {
      mq.removeEventListener('change', onChange);
      stop?.();
    };
  },
};
