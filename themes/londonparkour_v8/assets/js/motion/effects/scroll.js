/**
 * `data-motion-scroll` — scroll-linked / parallax motion. Uses Motion's
 * `scroll()` to link an existing `animate()` timeline directly to the
 * element's scroll progress through the viewport, rather than hand-rolling
 * a scroll listener + rAF loop.
 */
import { animate, scroll } from 'motion';
import { num } from '../utils.js';

export const parallaxEffect = {
  selector: '[data-motion-scroll]',
  init(el, { reduced }) {
    // Scroll-linked movement is exactly what reduced-motion opts out of —
    // leave the element in its natural (neutral) position, no listener.
    if (reduced) return;

    const axis = el.dataset.motionScrollAxis === 'x' ? 'x' : 'y';
    const speed = num(el.dataset.motionScrollSpeed, 0.3);
    const travel = 100 * speed; // px of total parallax travel across the tracked range

    const controls = animate(el, { [axis]: [-travel, travel] }, { ease: 'linear' });
    return scroll(controls, { target: el, offset: ['start end', 'end start'] });
  },
};
