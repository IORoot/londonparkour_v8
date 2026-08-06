/**
 * `data-motion-marquee` — infinite horizontal loop. The container's content
 * is cloned once so the two copies can hand off seamlessly, then driven by a
 * single linear, infinitely-repeating `animate()` call.
 */
import { animate } from 'motion';
import { num } from '../utils.js';

export const marqueeEffect = {
  selector: '[data-motion-marquee]',
  init(el, { reduced }) {
    // An infinite loop is precisely the kind of motion prefers-reduced-motion
    // opts out of — leave the (already-static) content as authored.
    if (reduced) return;

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
    const duration = distance / speed;

    const controls = animate(
      el,
      { x: direction === -1 ? [0, -distance] : [-distance, 0] },
      { duration, ease: 'linear', repeat: Infinity }
    );

    return () => controls.stop();
  },
};
