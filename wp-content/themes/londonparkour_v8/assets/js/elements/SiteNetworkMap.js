/**
 * SiteNetworkMap — Leaflet + Carto Voyager for Classes / Map.
 * Reads pin templates from `[data-site-pin]` children inside a <template>.
 *
 * Wheel zoom only with ⌘/Ctrl (and trackpad pinch, which browsers send as
 * Ctrl+wheel) so ordinary page scroll is not stolen.
 */

import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const TILE_URL = 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png';
const TILE_ATTR =
  '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/attributions">CARTO</a>';

const highlightSite = (siteId) => {
  if (!siteId) return;
  const panel = document.getElementById(`site-${siteId}`);
  if (!panel) return;
  panel.scrollIntoView({ behavior: 'smooth', block: 'center' });
  panel.classList.add('ring-2', 'ring-primary', 'ring-offset-2', 'ring-offset-base-200');
  window.setTimeout(() => {
    panel.classList.remove('ring-2', 'ring-primary', 'ring-offset-2', 'ring-offset-base-200');
  }, 1600);
};

const enableModifierWheelZoom = (map) => {
  const el = map.getContainer();
  // ⌘/Ctrl+scroll, and trackpad pinch (browsers send pinch as Ctrl+wheel).
  // Plain scroll keeps paging the document.
  const onWheel = (event) => {
    if (!(event.metaKey || event.ctrlKey)) return;
    event.preventDefault();
    const delta = L.DomEvent.getWheelDelta(event);
    const next = map._limitZoom(map.getZoom() + delta);
    if (next === map.getZoom()) return;
    const point = map.mouseEventToContainerPoint(event);
    map.setZoomAround(map.containerPointToLatLng(point), next);
  };
  el.addEventListener('wheel', onWheel, { passive: false });
  return () => el.removeEventListener('wheel', onWheel);
};

const bindSiteListFlyTo = (map, mount) => {
  const network = mount.closest('[data-component="map-network"]') || mount.parentElement;
  if (!network) return () => {};

  const onClick = (event) => {
    const item = event.target.closest('[data-site-flyto]');
    if (!item || !network.contains(item)) return;

    const lat = Number(item.dataset.lat);
    const lon = Number(item.dataset.lon);
    if (!Number.isFinite(lat) || !Number.isFinite(lon)) return;

    event.preventDefault();
    map.flyTo([lat, lon], Math.max(map.getZoom(), 15), { duration: 0.75 });
    highlightSite(item.dataset.siteId || '');
  };

  network.addEventListener('click', onClick);
  return () => network.removeEventListener('click', onClick);
};

export function initSiteNetworkMap(root = document) {
  const mounts = root.querySelectorAll('[data-component="site-network-map"]');
  const cleanups = [];

  mounts.forEach((mount) => {
    if (mount.dataset.lpMapReady === '1') return;
    mount.dataset.lpMapReady = '1';

    const mapEl = mount.querySelector('[data-mount="leaflet"]');
    const tpl = mount.querySelector('template[data-site-pins]');
    if (!mapEl || !tpl) return;

    const pins = [...tpl.content.querySelectorAll('[data-site-pin]')];
    let map = null;
    let removeWheel = null;
    let removeList = null;

    try {
      map = L.map(mapEl, {
        scrollWheelZoom: false,
        touchZoom: true,
        doubleClickZoom: true,
        boxZoom: true,
        dragging: true,
      });
      removeWheel = enableModifierWheelZoom(map);
      removeList = bindSiteListFlyTo(map, mount);

      L.tileLayer(TILE_URL, {
        attribution: TILE_ATTR,
        maxZoom: 20,
        subdomains: 'abcd',
      }).addTo(map);

      const bounds = [];

      pins.forEach((pin) => {
        const lat = Number(pin.dataset.lat);
        const lon = Number(pin.dataset.lon);
        const siteId = pin.dataset.siteId || '';
        if (!Number.isFinite(lat) || !Number.isFinite(lon)) return;

        bounds.push([lat, lon]);
        const html = pin.innerHTML.trim();
        if (!html) return;

        const icon = L.divIcon({
          className: 'lp-map-pin-icon !bg-transparent !border-0',
          html,
          iconSize: [168, 44],
          iconAnchor: [14, 22],
        });

        const marker = L.marker([lat, lon], {
          icon,
          keyboard: true,
          title: pin.dataset.name || siteId,
        }).addTo(map);

        marker.on('click', () => highlightSite(siteId));
      });

      if (bounds.length) {
        map.fitBounds(bounds, { padding: [48, 48], maxZoom: 13 });
      } else {
        map.setView([51.5074, -0.1278], 11);
      }

      requestAnimationFrame(() => map?.invalidateSize());
      window.setTimeout(() => map?.invalidateSize(), 200);
    } catch {
      return;
    }

    cleanups.push(() => {
      removeList?.();
      removeWheel?.();
      map?.remove();
      delete mount.dataset.lpMapReady;
    });
  });

  return {
    destroy: () => cleanups.forEach((fn) => fn()),
  };
}

export default initSiteNetworkMap;
