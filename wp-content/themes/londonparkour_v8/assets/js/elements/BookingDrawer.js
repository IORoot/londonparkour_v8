/**
 * Shared clasbpro booking drawer — loads form HTML into #lp-booking-drawer.
 */

const DRAWER_ID = 'lp-booking-drawer';

function mountEl() {
  return document.querySelector('[data-lp-booking-mount]');
}

function drawerEl() {
  return document.getElementById(DRAWER_ID);
}

async function loadForm(classId, presetDate = '') {
  const mount = mountEl();
  const cfg = window.lpBooking;
  if (!mount || !cfg?.restUrl) {
    if (mount) {
      mount.innerHTML = '<p class="font-label text-[11px] uppercase tracking-[0.8px] text-neutral-content/50">Booking unavailable</p>';
    }
    return;
  }

  mount.innerHTML = '<p class="font-label text-[11px] uppercase tracking-[0.8px] text-neutral-content/50">Loading…</p>';

  const url = new URL(cfg.restUrl, window.location.origin);
  url.searchParams.set('class_id', String(classId));
  if (presetDate) {
    url.searchParams.set('preset_date', presetDate);
  }

  try {
    const res = await fetch(url.toString(), {
      headers: {
        Accept: 'application/json',
        'X-WP-Nonce': cfg.nonce || '',
      },
      credentials: 'same-origin',
    });
    const data = await res.json();
    if (!res.ok || !data?.html) {
      mount.innerHTML = '<p class="font-label text-[11px] uppercase tracking-[0.8px] text-neutral-content/50">Could not load booking form</p>';
      return;
    }
    mount.innerHTML = data.html;
  } catch {
    mount.innerHTML = '<p class="font-label text-[11px] uppercase tracking-[0.8px] text-neutral-content/50">Could not load booking form</p>';
  }
}

function onBookClick(event) {
  const trigger = event.target.closest('[data-lp-book]');
  if (!trigger) return;

  const classId = trigger.getAttribute('data-class-id');
  if (!classId) return;

  const presetDate = trigger.getAttribute('data-preset-date') || '';
  loadForm(classId, presetDate);
}

/**
 * Bind click delegation for booking triggers.
 * @returns {{ cleanup: () => void }}
 */
export function initBookingDrawer() {
  document.addEventListener('click', onBookClick, true);

  // Prefetch when the dialog opens via command API too.
  const drawer = drawerEl();
  if (drawer) {
    drawer.addEventListener('command', (e) => {
      // no-op placeholder — click handler already loads before show-modal
      void e;
    });
  }

  return {
    cleanup: () => document.removeEventListener('click', onBookClick, true),
  };
}
