/**
 * Declarative motion layer (Motion / motion.dev) — replaces the deleted GSAP
 * `animation/` layer. Components author intent via `data-motion-*`
 * attributes; this file scans the DOM for them and wires the matching
 * effect. New behaviour = `registerEffect()`, never editing this file.
 *
 * prefers-reduced-motion is handled ONCE here, at the layer (see
 * reducedMotion.js), not per effect: every `initAll()` mount also subscribes
 * to preference changes, and on change simply tears down and re-runs every
 * effect's normal init path — which already resolves instantly to the final
 * state when reduced motion is active (see each effect for how).
 */
import { prefersReducedMotion, watchReducedMotion } from './reducedMotion.js';
import { entranceEffect, staggerEffect } from './effects/entrance.js';
import { parallaxEffect } from './effects/scroll.js';
import { hoverEffect } from './effects/hover.js';
import { marqueeEffect } from './effects/marquee.js';
import { counterEffect } from './effects/counter.js';
import { glyphAssemblyEffect } from './effects/glyphAssembly.js';

// Order matters: 'stagger' must run before 'entrance' so a stagger
// container's own [data-motion-enter] is claimed by staggerEffect first
// (entranceEffect skips anything inside/on a [data-motion-stagger] element).
const registry = new Map([
  ['stagger', staggerEffect],
  ['entrance', entranceEffect], // also covers "scroll-reveal" via -trigger="scroll" (the default)
  ['parallax', parallaxEffect],
  ['hover', hoverEffect],
  ['marquee', marqueeEffect],
  ['counter', counterEffect],
  ['glyphAssembly', glyphAssemblyEffect],
]);

/**
 * Extension point for new `data-motion-*` behaviour.
 * @param {string} name
 * @param {{ selector: string, init: (el: Element, ctx: { reduced: boolean }) => (Function|void) }} effect
 */
export function registerEffect(name, effect) {
  registry.set(name, effect);
}

function runEffects(root) {
  const reduced = prefersReducedMotion();
  const cleanups = [];

  for (const effect of registry.values()) {
    root.querySelectorAll(effect.selector).forEach((el) => {
      const result = effect.init(el, { reduced });
      if (typeof result === 'function') cleanups.push(result);
    });
  }

  return () => cleanups.forEach((fn) => fn());
}

const activeMounts = new Set();

/**
 * Scan `root` for every data-motion-* attribute this layer understands and
 * wire the matching effect. Returns a cleanup() that fully reverses it.
 * @param {Element} [root]
 * @returns {() => void}
 */
export function initAll(root = document.body) {
  const mount = { teardown: null };
  mount.rerun = () => {
    mount.teardown?.();
    mount.teardown = runEffects(root);
  };
  mount.rerun();

  const unwatch = watchReducedMotion(mount.rerun);
  activeMounts.add(mount);

  return function cleanup() {
    mount.teardown?.();
    unwatch();
    activeMounts.delete(mount);
  };
}

/**
 * Re-measure scroll-linked effects (and re-evaluate everything else) for
 * every currently-mounted root — e.g. after Storybook remounts a story and
 * layout has settled.
 */
export function refresh() {
  activeMounts.forEach((mount) => mount.rerun());
}

/**
 * Per-component lifecycle wrapper, mirrors the old GSAP AnimationManager
 * shape so ports don't have to relearn an API:
 *   const m = new MotionManager(container); m.mount(); m.unmount();
 */
export class MotionManager {
  constructor(container) {
    this.container = container;
    this._cleanup = null;
  }
  mount() {
    this._cleanup = initAll(this.container);
    return this;
  }
  update() {
    this.unmount();
    return this.mount();
  }
  unmount() {
    this._cleanup?.();
    this._cleanup = null;
  }
}
