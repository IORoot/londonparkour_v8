/**
 * `data-motion-decode` — departure-board / GPS scramble that locks left-to-right
 * into the final string. On mount (not scroll). Charset flavours:
 *   - gps: digits + N/S/E/W + degree / punctuation (coordinates)
 *   - board: A–Z, digits, light punctuation (headlines)
 *
 * Final string comes from `data-motion-decode` when set, otherwise the element's
 * current textContent. Newlines become `<br>` and are never scrambled.
 */
import { animate } from 'motion';
import { num } from '../utils.js';

const CHARSETS = {
  gps: '0123456789NSEW°./ ',
  board: "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789 .,'—-",
};

const DEFAULT_DURATION = { gps: 0.9, board: 1.6 };

function charsetFor(name, finalText) {
  const base = CHARSETS[name] || CHARSETS.board;
  const extras = [...finalText].filter((ch) => ch !== '\n' && !base.includes(ch));
  return base + extras.join('');
}

function pick(pool) {
  return pool[Math.floor(Math.random() * pool.length)] || ' ';
}

/**
 * Build per-character spans. Hard newlines become separate nowrap block lines so
 * scramble never changes the line count (wide glyphs used to soft-wrap and
 * bounce the hero height). Each glyph also reserves its final width.
 */
export function buildDecodeNodes(el, finalText) {
  el.textContent = '';
  const spans = [];
  const lines = finalText.split('\n');

  lines.forEach((line) => {
    const lineEl = document.createElement('span');
    lineEl.style.display = 'block';
    lineEl.style.whiteSpace = 'nowrap';
    // Avoid a totally empty block collapsing oddly when a line is blank.
    if (line.length === 0) {
      lineEl.innerHTML = '&nbsp;';
      el.appendChild(lineEl);
      return;
    }

    for (const ch of line) {
      const span = document.createElement('span');
      span.dataset.decodeChar = ch;
      span.textContent = ch === ' ' ? '\u00a0' : ch;
      span.style.display = 'inline-block';
      if (ch === ' ') span.style.whiteSpace = 'pre';
      lineEl.appendChild(span);
      spans.push(span);
    }
    el.appendChild(lineEl);
  });

  // Lock slot widths to the final glyphs before scramble starts.
  spans.forEach((span) => {
    const w = span.getBoundingClientRect().width;
    if (w > 0) span.style.minWidth = `${w}px`;
  });

  return spans;
}

export function readDecodeConfig(el) {
  const charsetName = el.dataset.motionDecodeCharset === 'gps' ? 'gps' : 'board';
  return {
    charsetName,
    duration: num(el.dataset.motionDecodeDuration, DEFAULT_DURATION[charsetName]),
    tick: num(el.dataset.motionDecodeTick, 0.04),
  };
}

export const decodeEffect = {
  selector: '[data-motion-decode]',
  init(el, { reduced, force = false } = {}) {
    // Ken Burns owns slide-synced stamps (`data-kb-live-coords`) and re-inits
    // decode on every slide change — skip them during the global initAll pass.
    if (el.hasAttribute('data-kb-live-coords') && !force) return;

    const finalText =
      el.dataset.motionDecode !== undefined && el.dataset.motionDecode !== ''
        ? el.dataset.motionDecode
        : el.textContent;
    // dataset turns literal `\n` in HTML attributes into the two chars `\`+`n`
    // when authors write data-motion-decode="line\nline". Prefer real newlines
    // from textContent when the attribute still contains the escape sequence.
    const resolved =
      finalText.includes('\\n') && !finalText.includes('\n')
        ? finalText.replace(/\\n/g, '\n')
        : finalText;

    const cfg = readDecodeConfig(el);
    const spans = buildDecodeNodes(el, resolved);
    const scrambleChars = spans.map((s) => s.dataset.decodeChar);
    const pool = charsetFor(cfg.charsetName, resolved);

    const paintFinal = () => {
      spans.forEach((span, i) => {
        const ch = scrambleChars[i];
        span.textContent = ch === ' ' ? '\u00a0' : ch;
      });
    };

    if (reduced || !spans.length) {
      paintFinal();
      return;
    }

    let lastTick = -1;
    const controls = animate(0, 1, {
      duration: cfg.duration,
      ease: 'linear',
      onUpdate: (t) => {
        const tickIndex = Math.floor(t / cfg.tick);
        if (tickIndex === lastTick && t < 1) return;
        lastTick = tickIndex;

        const locked = Math.floor(t * spans.length);
        spans.forEach((span, i) => {
          const ch = scrambleChars[i];
          if (i < locked || t >= 1) {
            span.textContent = ch === ' ' ? '\u00a0' : ch;
          } else {
            span.textContent = pick(pool);
          }
        });
      },
      onComplete: paintFinal,
    });

    return () => controls.stop();
  },
};
