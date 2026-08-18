/**
 * `data-motion-quote-board` — cycles a 3-row testimonial stack.
 *
 * Visible slots stay 01 / 02 / 03. Existing rows move up; the incoming quote
 * runs `data-motion-decode` (charset board, wrap). Dwell starts after decode
 * finishes. Pauses while off-screen or while a control inside the section is
 * focused. Hover does not pause — sitting on the quotes to watch them would
 * otherwise freeze the board. Reduced motion leaves the three static rows.
 */
import { animate } from 'motion';
import { num } from '../utils.js';
import { decodeEffect } from './decode.js';

const VISIBLE = 3;
const SHIFT_MS = 550;

function padIndex(n) {
  return String(n).padStart(2, '0');
}

function parseQuotes(el) {
  try {
    const raw = el.getAttribute('data-quotes') || '[]';
    const parsed = JSON.parse(raw);
    return Array.isArray(parsed) ? parsed.filter((q) => q && q.quote) : [];
  } catch {
    return [];
  }
}

function splitAttribution(raw) {
  const text = raw || '';
  const i = text.indexOf(' / ');
  if (i === -1) return { name: text, note: '' };
  return { name: text.slice(0, i), note: text.slice(i + 3) };
}

function fillAttribution(row, item, { blank = false } = {}) {
  const nameEl = row.querySelector('[data-quote-name]');
  const noteEl = row.querySelector('[data-quote-note]');
  const { name, note } = blank ? { name: '', note: '' } : splitAttribution(item.attribution);
  if (nameEl) nameEl.textContent = name;
  if (noteEl) {
    noteEl.textContent = note ? `/ ${note}` : '';
    noteEl.hidden = !note;
  }
}

function fillRow(row, item, slotIndex, { blank = false } = {}) {
  const idx = row.querySelector('[data-quote-index]');
  const text = row.querySelector('[data-quote-text]');
  if (idx) idx.textContent = padIndex(slotIndex);
  fillAttribution(row, item, { blank });
  if (text) {
    const quote = item.quote || '';
    text.dataset.motionDecode = quote;
    text.dataset.motionDecodeCharset = 'board';
    text.dataset.motionDecodeWrap = 'true';
    text.textContent = blank ? '' : quote;
  }
}

function relabel(list) {
  list.querySelectorAll(':scope > [data-quote-row]').forEach((row, i) => {
    const idx = row.querySelector('[data-quote-index]');
    if (idx) idx.textContent = padIndex(i + 1);
  });
}

function isElementInView(node) {
  const rect = node.getBoundingClientRect();
  return rect.bottom > 0 && rect.top < (window.innerHeight || 0) && rect.height > 0;
}

function gapPx(el) {
  const styles = getComputedStyle(el);
  return parseFloat(styles.rowGap || styles.gap) || 0;
}

function waitMs(ms) {
  return new Promise((resolve) => {
    window.setTimeout(resolve, ms);
  });
}

export const quoteBoardEffect = {
  selector: '[data-motion-quote-board]',
  init(el, { reduced }) {
    const quotes = parseQuotes(el);
    if (quotes.length <= VISIBLE || reduced) return;

    const section = el.closest('[data-component="testimonials"]') || el;
    const rowTpl = section.querySelector('[data-quote-row-template]');
    const ruleTpl = section.querySelector('[data-quote-rule-template]');
    if (!rowTpl?.content?.firstElementChild || !ruleTpl?.content?.firstElementChild) return;

    const dwellMs = Math.max(0, num(el.dataset.motionQuoteBoardDwell, 10)) * 1000;

    let nextIndex = VISIBLE;
    let remaining = dwellMs;
    let dwellTimer = 0;
    let dwellStartedAt = 0;
    let inView = isElementInView(el);
    let focusing = false;
    let cycling = false;
    let stopped = false;
    let shiftControls = null;
    let stopDecode = null;

    const isPaused = () => stopped || cycling || !inView || focusing;

    const clearDwell = () => {
      window.clearTimeout(dwellTimer);
      dwellTimer = 0;
      if (dwellStartedAt && !isPaused()) {
        remaining -= performance.now() - dwellStartedAt;
      }
      dwellStartedAt = 0;
    };

    const armDwell = () => {
      clearDwell();
      if (isPaused()) return;
      dwellStartedAt = performance.now();
      dwellTimer = window.setTimeout(() => {
        dwellTimer = 0;
        dwellStartedAt = 0;
        remaining = dwellMs;
        shift();
      }, Math.max(0, remaining));
    };

    const shift = async () => {
      if (stopped || cycling) return;
      cycling = true;

      const outgoing = el.querySelector(':scope > [data-quote-row]');
      const outgoingRule = el.querySelector(':scope > [data-quote-rule]');
      if (!outgoing) {
        cycling = false;
        remaining = dwellMs;
        armDwell();
        return;
      }

      const item = quotes[nextIndex % quotes.length];
      nextIndex += 1;

      const newRow = rowTpl.content.firstElementChild.cloneNode(true);
      const newRule = ruleTpl.content.firstElementChild.cloneNode(true);
      fillRow(newRow, item, VISIBLE, { blank: true });

      const gap = gapPx(el);
      const delta =
        outgoing.getBoundingClientRect().height +
        gap +
        (outgoingRule ? outgoingRule.getBoundingClientRect().height + gap : 0);

      const startH = el.getBoundingClientRect().height;
      el.style.overflow = 'hidden';
      el.style.height = `${startH}px`;

      el.appendChild(newRule);
      el.appendChild(newRow);

      const text = newRow.querySelector('[data-quote-text]');
      const session = text
        ? decodeEffect.init(text, { reduced: false, force: true, deferPlay: true })
        : null;
      if (session && typeof session.stop === 'function') stopDecode = session.stop;

      const movers = Array.from(el.children);
      try {
        shiftControls = animate(movers, { y: [0, -delta] }, { duration: SHIFT_MS / 1000, ease: [0.22, 1, 0.36, 1] });
        const finished = shiftControls?.finished;
        await Promise.race([
          finished && typeof finished.then === 'function' ? finished : waitMs(SHIFT_MS),
          waitMs(SHIFT_MS + 200),
        ]);
      } catch {
        /* torn down mid-shift */
      }
      shiftControls = null;

      if (stopped) return;

      outgoing.remove();
      outgoingRule?.remove();
      Array.from(el.children).forEach((node) => {
        node.style.transform = '';
      });
      relabel(el);

      el.style.height = '';
      el.style.overflow = '';

      fillAttribution(newRow, item);

      if (session?.play) await session.play();
      stopDecode = null;

      if (stopped) return;
      cycling = false;
      remaining = dwellMs;
      armDwell();
    };

    const onFocusIn = () => {
      focusing = true;
      clearDwell();
    };
    const onFocusOut = (e) => {
      if (!section.contains(e.relatedTarget)) {
        focusing = false;
        armDwell();
      }
    };

    section.addEventListener('focusin', onFocusIn);
    section.addEventListener('focusout', onFocusOut);

    const io = new IntersectionObserver(
      (entries) => {
        const next = entries.some((entry) => entry.isIntersecting);
        if (next === inView) return;
        inView = next;
        if (inView) armDwell();
        else clearDwell();
      },
      { threshold: 0 }
    );
    io.observe(el);

    armDwell();

    return () => {
      stopped = true;
      clearDwell();
      io.disconnect();
      section.removeEventListener('focusin', onFocusIn);
      section.removeEventListener('focusout', onFocusOut);
      shiftControls?.stop?.();
      if (typeof stopDecode === 'function') stopDecode();
      el.style.height = '';
      el.style.overflow = '';
    };
  },
};
