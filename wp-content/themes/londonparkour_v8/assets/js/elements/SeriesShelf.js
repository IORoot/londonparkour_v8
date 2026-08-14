/**
 * SeriesShelf — custom scroll track + prev/next on lmIjE lesson rows.
 *
 * Native overflow scrolling stays; the Pen's 2px track + 28px arrow buttons
 * replace the browser scrollbar. Markup is already in the page — this only
 * wires scrollLeft to the thumb and the buttons.
 *
 * @param {ParentNode} root Scope to search. Defaults to the document.
 * @returns {Array<{destroy: () => void}>}
 */
export const initAllSeriesShelves = (root = document) =>
  [...root.querySelectorAll('[data-component="series-card-shelf"]')].map((shelf) => {
    const scroller = shelf.querySelector('[data-shelf-scroller]');
    const thumb = shelf.querySelector('[data-shelf-thumb]');
    const prev = shelf.querySelector('[data-shelf-prev]');
    const next = shelf.querySelector('[data-shelf-next]');

    if (!scroller) {
      return { destroy: () => {} };
    }

    const step = () => Math.max(248, Math.floor(scroller.clientWidth * 0.75));

    const update = () => {
      const max = Math.max(0, scroller.scrollWidth - scroller.clientWidth);
      if (thumb) {
        const track = thumb.parentElement;
        const trackWidth = track ? track.clientWidth : 0;
        const ratio = scroller.scrollWidth > 0 ? scroller.clientWidth / scroller.scrollWidth : 1;
        const thumbWidth = Math.max(24, Math.min(trackWidth, ratio * trackWidth));
        const x = max > 0 ? (scroller.scrollLeft / max) * (trackWidth - thumbWidth) : 0;
        thumb.style.width = `${thumbWidth}px`;
        thumb.style.transform = `translateX(${x}px)`;
      }
      if (prev) prev.disabled = scroller.scrollLeft <= 1;
      if (next) next.disabled = max <= 1 || scroller.scrollLeft >= max - 1;
    };

    const onPrev = () => scroller.scrollBy({ left: -step(), behavior: 'smooth' });
    const onNext = () => scroller.scrollBy({ left: step(), behavior: 'smooth' });

    prev?.addEventListener('click', onPrev);
    next?.addEventListener('click', onNext);
    scroller.addEventListener('scroll', update, { passive: true });
    window.addEventListener('resize', update);
    update();

    return {
      destroy: () => {
        prev?.removeEventListener('click', onPrev);
        next?.removeEventListener('click', onNext);
        scroller.removeEventListener('scroll', update);
        window.removeEventListener('resize', update);
      },
    };
  });

export default initAllSeriesShelves;
