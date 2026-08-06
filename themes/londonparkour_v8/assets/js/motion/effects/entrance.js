/**
 * `data-motion-enter` — fade/slide/scale entrances, triggered on load or on
 * scroll-into-view via Motion's `inView`. `-trigger="scroll"` (the default)
 * IS the "scroll-reveal" effect from the build brief — there is no separate
 * attribute for it, it's just this effect's default trigger mode.
 *
 * `data-motion-stagger` — a container whose direct children animate as a
 * group using the container's own `data-motion-enter*` attributes, spaced
 * out with Motion's `stagger()` helper instead of each child declaring its
 * own `-delay`.
 */
import { animate, inView, stagger } from 'motion';
import { num } from '../utils.js';

const PRESETS = {
  fade: { opacity: [0, 1] },
  'fade-up': { opacity: [0, 1], y: [24, 0] },
  scale: { opacity: [0, 1], scale: [0.92, 1] },
  'slide-left': { opacity: [0, 1], x: [40, 0] },
  'slide-right': { opacity: [0, 1], x: [-40, 0] },
};

/** Read the shared `-enter*` config off any element (entrance target or stagger container). */
export function readEnterConfig(el) {
  const d = el.dataset;
  return {
    preset: d.motionEnter in PRESETS ? d.motionEnter : 'fade',
    trigger: d.motionEnterTrigger || 'scroll',
    duration: num(d.motionEnterDuration, 0.6),
    delay: num(d.motionEnterDelay, 0),
    ease: d.motionEnterEase || 'ease-out',
    once: d.motionEnterOnce !== 'false',
    // IntersectionObserver-style rootMargin passed straight to Motion's inView().
    start: d.motionEnterStart || '0px 0px -10% 0px',
  };
}

function play(elOrElements, keyframes, { duration, delay, ease, reduced }) {
  return animate(elOrElements, keyframes, {
    duration: reduced ? 0 : duration,
    delay: reduced ? 0 : delay,
    ease,
  });
}

export const entranceEffect = {
  selector: '[data-motion-enter]',
  init(el, { reduced }) {
    // closest() checks the element itself first, so a stagger container that
    // also carries data-motion-enter (defining its group's preset) is
    // correctly skipped here and handled by staggerEffect below.
    if (el.closest('[data-motion-stagger]')) return;

    const cfg = readEnterConfig(el);
    if (cfg.trigger === 'none') return;
    const keyframes = PRESETS[cfg.preset];

    if (reduced || cfg.trigger === 'load') {
      play(el, keyframes, { ...cfg, reduced });
      return;
    }

    const stop = inView(
      el,
      () => {
        play(el, keyframes, { ...cfg, reduced });
        if (cfg.once) stop();
      },
      { margin: cfg.start }
    );
    return stop;
  },
};

export const staggerEffect = {
  selector: '[data-motion-stagger]',
  init(container, { reduced }) {
    const cfg = readEnterConfig(container);
    if (cfg.trigger === 'none') return;
    const keyframes = PRESETS[cfg.preset];

    const each = num(container.dataset.motionStaggerEach, 0.08);
    const fromRaw = container.dataset.motionStaggerFrom;
    const from = fromRaw && !Number.isNaN(Number(fromRaw)) ? Number(fromRaw) : fromRaw || 'first';
    const delayFor = stagger(each, { from });

    const children = Array.from(container.children);
    if (!children.length) return;

    const run = () => {
      children.forEach((child, i) => {
        play(child, keyframes, {
          duration: cfg.duration,
          ease: cfg.ease,
          delay: cfg.delay + delayFor(i, children.length),
          reduced,
        });
      });
    };

    if (reduced || cfg.trigger === 'load') {
      run();
      return;
    }

    const stop = inView(
      container,
      () => {
        run();
        if (cfg.once) stop();
      },
      { margin: cfg.start }
    );
    return stop;
  },
};
