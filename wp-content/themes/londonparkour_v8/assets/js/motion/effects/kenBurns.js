/**
 * `data-motion-ken-burns` — continuous zoom on stacked `<img>` children with
 * optional crossfade between slides. Compositor-only (`transform` + `opacity`).
 *
 * Stack defaults (overridable per slide):
 *   data-kb-duration  hold+zoom seconds (default 8)
 *   data-kb-fade      crossfade seconds (default 1.2)
 *   data-kb-zoom      in | out (default in)
 *   data-kb-scale     end/start scale for zoom (default 1.12)
 *   data-kb-origin    transform-origin (default 50% 50%)
 *
 * Per-slide location stamp (synced to `[data-kb-live-coords]` in the hero):
 *   data-kb-coordinates  GPS string — re-runs decode on each slide change
 *   data-kb-href         http(s) URL — updates the coords link (new tab)
 */
import { animate } from 'motion';
import { num } from '../utils.js';
import { decodeEffect } from './decode.js';

function readCfg(el, fallbacks = {}) {
  const d = el.dataset;
  return {
    duration: num(d.kbDuration, fallbacks.duration ?? 8),
    fade: num(d.kbFade, fallbacks.fade ?? 1.2),
    zoom: d.kbZoom === 'out' ? 'out' : 'in',
    scale: num(d.kbScale, fallbacks.scale ?? 1.12),
    origin: d.kbOrigin || fallbacks.origin || '50% 50%',
  };
}

function zoomRange(cfg) {
  return cfg.zoom === 'out' ? { from: cfg.scale, to: 1 } : { from: 1, to: cfg.scale };
}

function preload(img) {
  if (!img?.src || img.complete) return Promise.resolve();
  return new Promise((resolve) => {
    const done = () => resolve();
    img.addEventListener('load', done, { once: true });
    img.addEventListener('error', done, { once: true });
  });
}

/** Only http(s) — never javascript: / data: from slide attrs. */
export function safeKbHref(url) {
  if (!url || typeof url !== 'string') return '';
  try {
    const parsed = new URL(url, typeof window !== 'undefined' ? window.location.origin : 'https://example.com');
    if (parsed.protocol === 'http:' || parsed.protocol === 'https:') return parsed.href;
  } catch {
    /* ignore */
  }
  return '';
}

export const kenBurnsEffect = {
  selector: '[data-motion-ken-burns]',
  init(stack, { reduced }) {
    const slides = Array.from(stack.querySelectorAll(':scope > img'));
    if (!slides.length) return;

    const defaults = readCfg(stack);
    const root = stack.closest('[data-component="hero"]') || stack.parentElement;
    let stopDecode = null;

    const syncCoords = (img) => {
      const coordsEl = root?.querySelector('[data-kb-live-coords]');
      if (!coordsEl) return;

      const coordinates = img.dataset.kbCoordinates || '';
      const href = safeKbHref(img.dataset.kbHref || '');

      if (coordinates) {
        coordsEl.dataset.motionDecode = coordinates;
        coordsEl.dataset.motionDecodeCharset = 'gps';
      }

      if (href) {
        coordsEl.setAttribute('href', href);
        coordsEl.setAttribute('target', '_blank');
        coordsEl.setAttribute('rel', 'noopener noreferrer');
        coordsEl.removeAttribute('aria-disabled');
        coordsEl.style.pointerEvents = '';
        coordsEl.style.cursor = 'pointer';
      } else {
        coordsEl.removeAttribute('href');
        coordsEl.removeAttribute('target');
        coordsEl.removeAttribute('rel');
        coordsEl.setAttribute('aria-disabled', 'true');
        coordsEl.style.pointerEvents = 'none';
        coordsEl.style.cursor = 'default';
      }

      if (typeof stopDecode === 'function') stopDecode();
      stopDecode = null;

      if (!coordinates) {
        coordsEl.textContent = '';
        return;
      }

      // decodeEffect skips [data-kb-live-coords] during initAll — we own it here.
      const result = decodeEffect.init(coordsEl, { reduced, force: true });
      if (typeof result === 'function') stopDecode = result;
    };

    slides.forEach((img, i) => {
      img.style.position = 'absolute';
      img.style.inset = '0';
      img.style.width = '100%';
      img.style.height = '100%';
      img.style.objectFit = 'cover';
      img.style.willChange = 'transform, opacity';
      img.style.opacity = i === 0 ? '1' : '0';
      img.style.zIndex = i === 0 ? '1' : '0';
      const cfg = readCfg(img, defaults);
      img.style.transformOrigin = cfg.origin;
      img.style.transform = `scale(${zoomRange(cfg).from})`;
    });

    if (reduced) {
      slides[0].style.transform = 'scale(1)';
      slides[0].style.opacity = '1';
      slides.slice(1).forEach((img) => {
        img.style.opacity = '0';
      });
      syncCoords(slides[0]);
      return () => {
        if (typeof stopDecode === 'function') stopDecode();
      };
    }

    let stopped = false;
    const running = [];
    const timers = [];

    const stopAll = () => {
      stopped = true;
      timers.splice(0).forEach((id) => clearTimeout(id));
      running.splice(0).forEach((c) => c.stop?.());
      if (typeof stopDecode === 'function') stopDecode();
      stopDecode = null;
    };

    const track = (controls) => {
      running.push(controls);
      return controls;
    };

    const wait = (ms) =>
      new Promise((resolve) => {
        const id = setTimeout(resolve, ms);
        timers.push(id);
      });

    const startZoom = (img) => {
      const cfg = readCfg(img, defaults);
      const { from, to } = zoomRange(cfg);
      img.style.transformOrigin = cfg.origin;
      return track(
        animate(img, { scale: [from, to] }, { duration: cfg.duration, ease: 'linear' })
      );
    };

    const crossfade = async (fromImg, toImg) => {
      const fromCfg = readCfg(fromImg, defaults);
      const toCfg = readCfg(toImg, defaults);
      const fade = Math.min(fromCfg.fade, fromCfg.duration);
      const { from } = zoomRange(toCfg);

      await preload(toImg);
      if (stopped) return 0;

      toImg.style.transformOrigin = toCfg.origin;
      toImg.style.zIndex = '2';
      toImg.style.transform = `scale(${from})`;
      toImg.style.opacity = '0';

      startZoom(toImg);
      // Decode + href update as the new slide starts coming in.
      syncCoords(toImg);

      await Promise.all([
        track(animate(fromImg, { opacity: 0 }, { duration: fade, ease: 'ease-in-out' })).finished,
        track(animate(toImg, { opacity: 1 }, { duration: fade, ease: 'ease-in-out' })).finished,
      ]);

      if (stopped) return 0;
      fromImg.style.zIndex = '0';
      toImg.style.zIndex = '1';
      return fade * 1000;
    };

    const run = async () => {
      let i = 0;
      let elapsedOnCurrent = 0;

      slides[0].style.opacity = '1';
      slides[0].style.zIndex = '1';
      syncCoords(slides[0]);
      startZoom(slides[0]);

      while (!stopped) {
        const img = slides[i];
        const cfg = readCfg(img, defaults);
        const fade = Math.min(cfg.fade, cfg.duration);
        const holdMs = Math.max(0, (cfg.duration - fade) * 1000 - elapsedOnCurrent);
        await wait(holdMs);
        if (stopped) break;

        if (slides.length === 1) {
          const { from, to } = zoomRange(cfg);
          track(
            animate(img, { scale: [to, from] }, { duration: cfg.duration, ease: 'linear' })
          );
          await wait(cfg.duration * 1000);
          if (stopped) break;
          startZoom(img);
          elapsedOnCurrent = 0;
          continue;
        }

        const nextI = (i + 1) % slides.length;
        elapsedOnCurrent = await crossfade(img, slides[nextI]);
        i = nextI;
      }
    };

    run();
    return stopAll;
  },
};
