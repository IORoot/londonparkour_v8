/**
 * `data-motion-hover` — pointer-driven micro-interactions. Wraps Motion's
 * `hover()` gesture helper (filters touch/synthetic pointer noise already)
 * instead of binding raw mouseenter/mouseleave.
 */
import { animate, hover } from 'motion';
import { num } from '../utils.js';

const PRESETS = {
  scale: (amount) => ({ scale: amount }),
  lift: (amount) => ({ y: -amount }),
};
const RESET = { scale: 1, y: 0 };

export const hoverEffect = {
  selector: '[data-motion-hover]',
  init(el, { reduced }) {
    const preset = el.dataset.motionHover in PRESETS ? el.dataset.motionHover : 'scale';
    const defaultAmount = preset === 'lift' ? 6 : 1.04;
    const amount = num(el.dataset.motionHoverAmount, defaultAmount);
    const duration = reduced ? 0 : num(el.dataset.motionHoverDuration, 0.25);
    const target = PRESETS[preset](amount);
    const rest = Object.fromEntries(Object.keys(target).map((key) => [key, RESET[key]]));

    return hover(el, () => {
      animate(el, target, { duration, ease: 'ease-out' });
      return () => animate(el, rest, { duration, ease: 'ease-out' });
    });
  },
};
