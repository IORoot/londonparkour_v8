/**
 * ClassDetailOsmMap — single meeting-point Leaflet map with a yellow MapPin.
 * Mounts on `[data-component="class-detail-osm"]` with data-lat / data-lon.
 * Pin HTML comes from `[data-meeting-pin] [data-component="map-pin"]`.
 */

import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const TILE_URL = 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png';
const TILE_ATTR =
  '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/attributions">CARTO</a>';

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

const initOne = (root) => {
  const lat = Number(root.dataset.lat);
  const lon = Number(root.dataset.lon);
  const name = root.dataset.name || 'Meeting point';
  const mapEl = root.querySelector('[data-mount="leaflet"]');
  const markerHtml = (
    root.querySelector('[data-meeting-pin] [data-component="map-pin"]')?.outerHTML || ''
  ).trim();

  if (!mapEl || !markerHtml || !Number.isFinite(lat) || !Number.isFinite(lon)) {
    return null;
  }

  let map = null;
  let removeWheel = null;

  try {
    map = L.map(mapEl, {
      scrollWheelZoom: false,
      touchZoom: true,
      doubleClickZoom: true,
      boxZoom: true,
      dragging: true,
      attributionControl: false,
      zoomControl: true,
    });
    removeWheel = enableModifierWheelZoom(map);

    L.tileLayer(TILE_URL, {
      attribution: TILE_ATTR,
      maxZoom: 20,
      subdomains: 'abcd',
    }).addTo(map);

    const icon = L.divIcon({
      className: 'lp-map-pin-icon !bg-transparent !border-0',
      html: markerHtml,
      iconSize: [30, 30],
      iconAnchor: [15, 15],
    });

    L.marker([lat, lon], { icon, keyboard: true, title: name }).addTo(map);
    map.setView([lat, lon], 16);

    requestAnimationFrame(() => map?.invalidateSize());
    window.setTimeout(() => map?.invalidateSize(), 200);
  } catch {
    return null;
  }

  return {
    destroy: () => {
      removeWheel?.();
      if (map) {
        map.remove();
        map = null;
      }
    },
  };
};

export function initClassDetailOsmMaps(root = document) {
  const mounts = [...root.querySelectorAll('[data-component="class-detail-osm"]')];
  const instances = mounts.map(initOne).filter(Boolean);
  return {
    destroy: () => instances.forEach((instance) => instance?.destroy?.()),
  };
}

export default initClassDetailOsmMaps;
