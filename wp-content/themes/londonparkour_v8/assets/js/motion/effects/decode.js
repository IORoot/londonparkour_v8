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

function appendGlyph(parent, ch, spans, { nbspSpaces }) {
  const span = document.createElement('span');
  span.dataset.decodeChar = ch;
  const wrapSpace = ch === ' ' && !nbspSpaces;
  span.textContent = wrapSpace ? ' ' : ch === ' ' ? '\u00a0' : ch;
  if (wrapSpace) {
    span.style.whiteSpace = 'normal';
  } else {
    span.style.display = 'inline-block';
    if (ch === ' ') span.style.whiteSpace = 'pre';
  }
  parent.appendChild(span);
  spans.push(span);
}

/**
 * Build per-character spans. Hard newlines become separate nowrap block lines so
 * scramble never changes the line count (wide glyphs used to soft-wrap and
 * bounce the hero height). Each glyph also reserves its final width.
 *
 * `wrap: true` groups words into nowrap spans so wrapping happens at spaces —
 * quotes at 32px must wrap; hero titles must not.
 */
export function buildDecodeNodes(el, finalText, { wrap = false } = {}) {
  el.textContent = '';
  const spans = [];
  const lines = finalText.split('\n');

  lines.forEach((line) => {
    const lineEl = document.createElement('span');
    lineEl.style.display = 'block';
    if (!wrap) lineEl.style.whiteSpace = 'nowrap';
    // Avoid a totally empty block collapsing oddly when a line is blank.
    if (line.length === 0) {
      lineEl.innerHTML = '&nbsp;';
      el.appendChild(lineEl);
      return;
    }

    if (!wrap) {
      for (const ch of line) appendGlyph(lineEl, ch, spans, { nbspSpaces: true });
    } else {
      const tokens = line.split(/(\s+)/);
      tokens.forEach((token) => {
        if (!token) return;
        if (/^\s+$/.test(token)) {
          for (const ch of token) appendGlyph(lineEl, ch, spans, { nbspSpaces: false });
          return;
        }
        const wordEl = document.createElement('span');
        wordEl.style.whiteSpace = 'nowrap';
        wordEl.style.display = 'inline-block';
        for (const ch of token) appendGlyph(wordEl, ch, spans, { nbspSpaces: true });
        lineEl.appendChild(wordEl);
      });
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
  init(el, { reduced, force = false, onComplete, deferPlay = false } = {}) {
    // Ken Burns owns slide-synced stamps (`data-kb-live-coords`) and re-inits
    // decode on every slide change — skip them during the global initAll pass.
    if (el.hasAttribute('data-kb-live-coords') && !force) return;
    // Quote board owns incoming-row decode; skip the visible slots on first paint
    // and on reduced-motion remounts so the initial three never scramble.
    if (el.closest('[data-motion-quote-board]') && !force) return;

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
    const wrap = el.dataset.motionDecodeWrap === 'true';
    const spans = buildDecodeNodes(el, resolved, { wrap });
    const scrambleChars = spans.map((s) => s.dataset.decodeChar);
    const pool = charsetFor(cfg.charsetName, resolved);
    const spaceFor = (ch) => (ch === ' ' && wrap ? ' ' : ch === ' ' ? '\u00a0' : ch);

    const paintBlank = () => {
      spans.forEach((span) => {
        span.textContent = '\u00a0';
      });
    };
    const paintScramble = () => {
      spans.forEach((span) => {
        span.textContent = pick(pool);
      });
    };
    const paintFinal = () => {
      spans.forEach((span, i) => {
        span.textContent = spaceFor(scrambleChars[i]);
      });
      onComplete?.();
    };

    let controls = null;
    const stop = () => controls?.stop();

    const play = () => {
      if (reduced || !spans.length) {
        paintFinal();
        return Promise.resolve();
      }
      paintScramble();
      return new Promise((resolve) => {
        let settled = false;
        const done = () => {
          if (settled) return;
          settled = true;
          window.clearTimeout(failsafe);
          paintFinal();
          resolve();
        };
        const failsafe = window.setTimeout(done, (cfg.duration + 0.25) * 1000);
        let lastTick = -1;
        controls = animate(0, 1, {
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
                span.textContent = spaceFor(ch);
              } else {
                span.textContent = pick(pool);
              }
            });
          },
          onComplete: done,
        });
      });
    };

    // Measure laid out final glyphs, then immediately blank them so a later
    // paint never shows the finished quote before scramble starts.
    if (deferPlay) {
      paintBlank();
      return { play, stop };
    }

    if (reduced || !spans.length) {
      paintFinal();
      return;
    }

    play();
    return stop;
  },
};
