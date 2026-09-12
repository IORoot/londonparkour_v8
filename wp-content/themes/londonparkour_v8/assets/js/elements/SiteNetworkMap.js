/**
 * SiteNetworkMap — Leaflet + OpenStreetMap for Classes / Map.
 * Reads pin templates from `[data-site-pin]` children inside a <template>.
 *
 * Wheel zoom only with ⌘/Ctrl (and trackpad pinch, which browsers send as
 * Ctrl+wheel) so ordinary page scroll is not stolen.
 *
 * Class-site pins scroll to that site's meeting panel.
 */

import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const TILE_URL = 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';
const TILE_ATTR =
  '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>';

const nameMapContainer = (map, label) => {
  const el = map.getContainer();
  el.setAttribute('role', 'region');
  el.setAttribute('aria-label', label);
};

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

const fitLayerBounds = (map, layer) => {
  const layers = layer.getLayers();
  if (!layers.length) {
    map.setView([51.5074, -0.1278], 11);
    return;
  }
  const group = L.featureGroup(layers);
  map.fitBounds(group.getBounds(), { padding: [48, 48], maxZoom: 13 });
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

/** Match sidebar height to the map column (head + stage). */
const syncSidebarHeight = (network) => {
  const sidebar = network?.querySelector('[data-map-sidebar]');
  const panel = network?.querySelector('[data-map-panel]');
  if (!sidebar || !panel) return;

  if (window.matchMedia('(min-width: 1024px)').matches) {
    sidebar.style.height = `${panel.offsetHeight}px`;
  } else {
    sidebar.style.height = '';
  }
};

const bindSidebarHeight = (network) => {
  if (!network) return () => {};
  const panel = network.querySelector('[data-map-panel]');
  const sync = () => syncSidebarHeight(network);
  sync();
  requestAnimationFrame(sync);

  const ro = panel && typeof ResizeObserver !== 'undefined' ? new ResizeObserver(sync) : null;
  ro?.observe(panel);
  window.addEventListener('resize', sync);
  return () => {
    ro?.disconnect();
    window.removeEventListener('resize', sync);
    const sidebar = network.querySelector('[data-map-sidebar]');
    if (sidebar) sidebar.style.height = '';
  };
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
    const network = mount.closest('[data-component="map-network"]');
    let map = null;
    let removeWheel = null;
    let removeList = null;
    let removeHeight = null;

    try {
      map = L.map(mapEl, {
        scrollWheelZoom: false,
        touchZoom: true,
        doubleClickZoom: true,
        boxZoom: true,
        dragging: true,
      });
      nameMapContainer(map, 'Map of class locations');
      removeWheel = enableModifierWheelZoom(map);
      removeList = bindSiteListFlyTo(map, mount);

      L.tileLayer(TILE_URL, {
        attribution: TILE_ATTR,
        maxZoom: 19,
      }).addTo(map);

      const classes = L.layerGroup().addTo(map);

      pins.forEach((pin) => {
        const lat = Number(pin.dataset.lat);
        const lon = Number(pin.dataset.lon);
        const siteId = pin.dataset.siteId || '';
        const name = pin.dataset.name || siteId;
        if (!Number.isFinite(lat) || !Number.isFinite(lon)) return;

        const markerHtml = pin.innerHTML.trim();
        if (!markerHtml) return;

        const icon = L.divIcon({
          className: 'lp-map-pin-icon !bg-transparent !border-0',
          html: markerHtml,
          iconSize: [168, 44],
          iconAnchor: [14, 22],
        });

        // Sidebar site links are the keyboard path; pins stay pointer-only.
        const marker = L.marker([lat, lon], {
          icon,
          keyboard: false,
          title: name,
        });
        marker.on('click', () => {
          highlightSite(siteId);
        });
        classes.addLayer(marker);
      });

      if (network) {
        removeHeight = bindSidebarHeight(network);
      }

      fitLayerBounds(map, classes);

      requestAnimationFrame(() => {
        map?.invalidateSize();
        if (network) syncSidebarHeight(network);
      });
      window.setTimeout(() => {
        map?.invalidateSize();
        if (network) syncSidebarHeight(network);
      }, 200);
    } catch {
      return;
    }

    cleanups.push(() => {
      removeHeight?.();
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
