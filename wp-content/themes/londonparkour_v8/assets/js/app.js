import '@tailwindplus/elements';

import { initAllVideoDialogs } from './elements/DialogVideo.js';
import { initAllFilterForms } from './elements/FilterForm.js';
import { initBookingDrawer } from './elements/BookingDrawer.js';
import { AppInitialiser } from './AppInitialiser.js';
import { createDOMObserver } from './utils/createDOMObserver.js';
import { initAll as initMotion } from './motion/index.js';

/**
 * Module registry
 */
const MODULES = {
  motion: {
    init: () => {
      const cleanup = initMotion(document.body);
      return { cleanup };
    },
    selector: null,
    critical: false,
    lazy: false,
    timeout: 5000
  },

  videoDialogs: {
    init: async () => {
      if (!customElements.get('el-dialog')) {
        await new Promise((resolve) => {
          window.addEventListener('elements:ready', resolve, { once: true });
        });
      }

      const instances = initAllVideoDialogs();
      createDOMObserver('button[data-video-type]', initAllVideoDialogs, { debounce: 50 });

      return instances;
    },
    selector: null,
    critical: false,
    lazy: false,
    timeout: 5000
  },

  // The filter grid is above the fold on every page that has one, so there is
  // nothing to defer — and a filter that only wakes on scroll is a bug.
  filterForms: {
    init: () => initAllFilterForms(),
    selector: 'form[data-filter-form]',
    critical: false,
    lazy: false,
    timeout: 5000
  },

  bookingDrawer: {
    init: () => initBookingDrawer(),
    selector: '[data-lp-book], #lp-booking-drawer',
    critical: false,
    lazy: false,
    timeout: 5000
  }
};

/**
 * Initialize app modules
 * @param {Object} options
 * @param {HTMLElement} options.container - Root element to scan for modules
 * @param {boolean} options.debug - Enable debug logging
 * @param {string[]} options.modules - Module names to initialize (defaults to all)
 * @returns {Promise<AppInitialiser>} The initialiser instance (call .destroy() to clean up)
 */
export async function initApp(options = {}) {
  const {
    container = document.body,
    debug = false,
    modules = Object.keys(MODULES)
  } = options;

  const app = new AppInitialiser({ debug, container });

  modules.forEach((name) => {
    if (MODULES[name]) {
      app.register(name, MODULES[name]);
    }
  });

  await app.initialize();
  return app;
}

/*
 * Entry point. In the Storybook this job belonged to the `withMotion` decorator,
 * which re-ran after every story mount. On the site the DOM is server-rendered
 * once, so a single boot on DOMContentLoaded replaces it.
 */
const boot = () => initApp({ modules: ['motion', 'videoDialogs', 'filterForms', 'bookingDrawer'] });

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', boot, { once: true });
} else {
  boot();
}
