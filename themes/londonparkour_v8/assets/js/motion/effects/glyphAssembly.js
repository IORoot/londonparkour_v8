/**
 * `data-motion-scope="glyph-assembly"` — animates the segment paths emitted
 * by src/stories/Brand/Glyph/glyphs.js (`glyphSvg()`): each `<path
 * data-motion-element="segment">` inside the scope fades/scales in,
 * staggered via Motion's `stagger()`.
 *
 * `data-motion-verb` is read off the scope element but not yet used to vary
 * the choreography — every glyph currently gets the same assemble-in. If a
 * verb needs bespoke motion later, branch on `el.dataset.motionVerb` here;
 * nothing else in this layer needs to change.
 */
import { animate, stagger } from 'motion';

export const glyphAssemblyEffect = {
  selector: '[data-motion-scope="glyph-assembly"]',
  init(el, { reduced }) {
    const segments = el.querySelectorAll('[data-motion-element="segment"]');
    if (!segments.length) return;

    const controls = animate(
      segments,
      { opacity: [0, 1], scale: [0.85, 1] },
      {
        duration: reduced ? 0 : 0.5,
        delay: reduced ? 0 : stagger(0.04),
        ease: 'ease-out',
      }
    );

    return () => controls.stop();
  },
};
