/**
 * Shared clasbpro panel drawer — loads booking or coupon shortcode HTML.
 */

import { lpBeginCheckout, lpSelectItem } from '../utils/analytics.js';

const DRAWER_ID = 'lp-booking-drawer';

function mountEl() {
  return document.querySelector('[data-lp-booking-mount]');
}

function drawerEl() {
  return document.getElementById(DRAWER_ID);
}

/**
 * Open the shared panel. Invoker Commands open the native <dialog> when
 * commandfor points at it; this is a fallback for programmatic/open races.
 */
function openDrawer() {
  const target = drawerEl();
  if (!target) return;

  if (target instanceof HTMLDialogElement) {
    if (!target.open) {
      target.showModal();
    }
    return;
  }

  if (typeof target.show === 'function') {
    target.show();
  }
}

function titleEl() {
  return document.querySelector('[data-lp-drawer-title]');
}

function cfg() {
  return window.lpBooking || {};
}

function setTitle(type) {
  const title = titleEl();
  if (!title) return;
  const labels = cfg().labels || {};
  title.textContent =
    type === 'coupon' ? labels.coupon || 'Buy a coupon' : labels.booking || 'Book a session';
}

/**
 * @param {'booking'|'coupon'} type
 * @param {string|number} id
 * @param {{ presetDate?: string, presetSlot?: string, name?: string }} [extra]
 */
async function loadPanel(type, id, extra = {}) {
  const mount = mountEl();
  const restUrl = cfg().panelFormUrl || cfg().restUrl;
  if (!mount || !restUrl) {
    if (mount) {
      mount.innerHTML =
        '<p class="font-label text-[11px] uppercase tracking-[0.8px] text-neutral-content/50">Booking unavailable</p>';
    }
    return;
  }

  setTitle(type);
  mount.innerHTML =
    '<p class="font-label text-[11px] uppercase tracking-[0.8px] text-neutral-content/50">Loading…</p>';

  const url = new URL(restUrl, window.location.origin);
  if (cfg().panelFormUrl) {
    url.searchParams.set('type', type);
    url.searchParams.set('id', String(id));
  } else {
    // Legacy schedule-booking-form
    url.searchParams.set('class_id', String(id));
  }
  if (extra.presetDate) {
    url.searchParams.set('preset_date', extra.presetDate);
  }
  if (extra.presetSlot) {
    url.searchParams.set('preset_slot_rule_id', extra.presetSlot);
  }

  try {
    const res = await fetch(url.toString(), {
      headers: {
        Accept: 'application/json',
        'X-WP-Nonce': cfg().nonce || '',
      },
      credentials: 'same-origin',
    });
    const data = await res.json();
    if (!res.ok || !data?.html) {
      mount.innerHTML =
        '<p class="font-label text-[11px] uppercase tracking-[0.8px] text-neutral-content/50">Could not load form</p>';
      return;
    }
    mount.innerHTML = data.html;

    if (typeof window.CLASBOWPRO_initBookingForms === 'function') {
      window.CLASBOWPRO_initBookingForms(mount);
    }
    if (typeof window.CLASBOWPRO_initAppointmentCalendars === 'function') {
      window.CLASBOWPRO_initAppointmentCalendars(mount);
    }
    if (typeof window.CLASBOWPRO_initClassDateCalendars === 'function') {
      window.CLASBOWPRO_initClassDateCalendars(mount);
    }

    const itemType = type === 'coupon' ? 'pack' : 'class';
    lpBeginCheckout({
      itemType,
      id,
      name: extra.name || '',
    });
  } catch {
    mount.innerHTML =
      '<p class="font-label text-[11px] uppercase tracking-[0.8px] text-neutral-content/50">Could not load form</p>';
  }
}

/**
 * @param {Element} trigger
 */
function panelFromTrigger(trigger) {
  const panel = trigger.getAttribute('data-lp-panel');
  if (panel === 'coupon' || panel === 'pack') {
    const packId = trigger.getAttribute('data-pack-id') || trigger.getAttribute('data-lp-id');
    return packId ? { type: 'coupon', id: packId } : null;
  }
  if (panel === 'booking' || trigger.hasAttribute('data-lp-book')) {
    const classId =
      trigger.getAttribute('data-class-id') || trigger.getAttribute('data-lp-id');
    return classId ? { type: 'booking', id: classId } : null;
  }
  return null;
}

function onPanelClick(event) {
  const trigger = event.target.closest('[data-lp-panel], [data-lp-book]');
  if (!trigger) return;

  const panel = panelFromTrigger(trigger);
  if (!panel) return;

  const presetDate = trigger.getAttribute('data-preset-date') || '';
  const presetSlot = trigger.getAttribute('data-preset-slot-rule-id') || '';
  const name = trigger.getAttribute('data-lp-item-name') || '';
  const itemType = panel.type === 'coupon' ? 'pack' : 'class';

  lpSelectItem({
    itemType,
    id: panel.id,
    name,
    listName: trigger.getAttribute('data-lp-list') || 'site',
  });

  openDrawer();
  loadPanel(panel.type, panel.id, { presetDate, presetSlot, name });
}

/**
 * Bind click delegation for panel triggers.
 * @returns {{ cleanup: () => void }}
 */
export function initBookingDrawer() {
  document.addEventListener('click', onPanelClick, true);

  return {
    cleanup: () => document.removeEventListener('click', onPanelClick, true),
  };
}
