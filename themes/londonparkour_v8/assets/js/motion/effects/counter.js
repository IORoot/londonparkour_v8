/**
 * `data-motion-counter` — animates a number up to a target value, driven by
 * Motion's numeric `animate()` + `onUpdate` rather than a hand-rolled rAF
 * tween. Triggers on scroll-into-view like the entrance effect.
 */
import { animate, inView } from 'motion';
import { num } from '../utils.js';

function format(value, decimals) {
  return decimals > 0 ? value.toFixed(decimals) : Math.round(value).toLocaleString();
}

export const counterEffect = {
  selector: '[data-motion-counter]',
  init(el, { reduced }) {
    const target = num(el.dataset.motionCounter, num(el.textContent, 0));
    const decimals = num(el.dataset.motionCounterDecimals, 0);
    const prefix = el.dataset.motionCounterPrefix || '';
    const suffix = el.dataset.motionCounterSuffix || '';
    const duration = reduced ? 0 : num(el.dataset.motionCounterDuration, 1.2);

    const run = () =>
      animate(0, target, {
        duration,
        ease: 'ease-out',
        onUpdate: (latest) => {
          el.textContent = `${prefix}${format(latest, decimals)}${suffix}`;
        },
      });

    if (reduced) {
      run();
      return;
    }

    const stop = inView(
      el,
      () => {
        run();
        stop();
      },
      { margin: '0px 0px -10% 0px' }
    );
    return stop;
  },
};
