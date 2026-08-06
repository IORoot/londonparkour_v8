/**
 * AppInitializer - Module initialization system with lazy loading
 *
 * Features:
 * - Auto-initialization based on data attributes
 * - Lazy loading with IntersectionObserver
 * - Retry logic with exponential backoff
 * - Performance tracking
 */

export class AppInitialiser {
  constructor(options = {}) {
    this.options = {
      debug: options.debug || false,
      container: options.container || document.body,
      ...options
    };

    this.modules = new Map();
    this.initialized = new Map();
    this.failed = new Set();
    this.observers = new Map();

    this._log('AppInitializer created');
  }

  /**
   * Register a module with its initialization function
   * @param {string} name - Module name
   * @param {Object} config - Module configuration
   */
  register(name, config) {
    this.modules.set(name, {
      name,
      initFunction: config.init,
      selector: config.selector,
      critical: config.critical || false,
      lazy: config.lazy !== false, // Default to lazy
      retries: config.retries || 1,
      timeout: config.timeout || 5000,
      ...config
    });

    this._log(`Registered module: ${name}`);
    return this;
  }

  /**
   * Initialize all registered modules
   */
  async initialize() {
    this._log('Starting initialization');
    const startTime = performance.now();

    // Separate modules by priority
    const critical = [];
    const lazy = [];
    const immediate = [];

    this.modules.forEach(module => {
      if (module.critical) {
        critical.push(module);
      } else if (module.lazy) {
        lazy.push(module);
      } else {
        immediate.push(module);
      }
    });

    // Initialize critical modules first (blocking)
    for (const module of critical) {
      await this._initializeModule(module);
    }

    // Initialize immediate non-critical modules (parallel)
    await Promise.allSettled(
      immediate.map(module => this._initializeModule(module))
    );

    // Setup lazy loading for remaining modules
    lazy.forEach(module => this._setupLazyLoading(module));

    const duration = performance.now() - startTime;
    this._log(`Initialization complete in ${duration.toFixed(2)}ms`, {
      initialized: this.initialized.size,
      failed: this.failed.size,
      lazy: lazy.length
    });

    return this.getStatus();
  }

  /**
   * Initialize a single module
   */
  async _initializeModule(module, attempt = 1) {
    if (this.initialized.has(module.name)) {
      return;
    }

    // Check if elements exist
    if (module.selector) {
      const elements = this.options.container.querySelectorAll(module.selector);
      if (elements.length === 0) {
        this._log(`No elements found for ${module.name}`);
        return;
      }
    }

    const startTime = performance.now();

    try {
      // Create timeout promise
      const timeoutPromise = new Promise((_, reject) =>
        setTimeout(() => reject(new Error(`Timeout after ${module.timeout}ms`)), module.timeout)
      );

      // Initialize with timeout
      const result = await Promise.race([
        module.initFunction(),
        timeoutPromise
      ]);

      const duration = performance.now() - startTime;
      this.initialized.set(module.name, {
        duration,
        timestamp: Date.now(),
        instances: Array.isArray(result) ? result.length : 1,
        result
      });

      this._log(`✅ ${module.name} initialized (${duration.toFixed(2)}ms)`);

    } catch (error) {
      const shouldRetry = attempt < module.retries;

      if (shouldRetry) {
        const delay = Math.min(1000 * Math.pow(2, attempt - 1), 5000); // Exponential backoff
        this._log(`⚠️ ${module.name} failed, retrying in ${delay}ms (attempt ${attempt}/${module.retries})`);

        await new Promise(resolve => setTimeout(resolve, delay));
        return this._initializeModule(module, attempt + 1);
      } else {
        this.failed.add(module.name);
        this._log(`❌ ${module.name} failed: ${error.message}`);
      }
    }
  }

  /**
   * Setup lazy loading for a module
   */
  _setupLazyLoading(module) {
    if (!module.selector) {
      this._log(`${module.name} has lazy loading but no selector, initializing immediately`);
      this._initializeModule(module);
      return;
    }

    if (!('IntersectionObserver' in window)) {
      this._log('IntersectionObserver not supported, initializing immediately');
      this._initializeModule(module);
      return;
    }

    const elements = this.options.container.querySelectorAll(module.selector);
    if (elements.length === 0) {
      return;
    }

    const observer = new IntersectionObserver((entries) => {
      for (const entry of entries) {
        if (entry.isIntersecting) {
          this._log(`${module.name} visible, initializing...`);
          this._initializeModule(module);
          observer.disconnect();
          this.observers.delete(module.name);
          break;
        }
      }
    }, {
      rootMargin: '100px 0px', // Start loading 100px before visible
      threshold: 0.01
    });

    elements.forEach(el => observer.observe(el));
    this.observers.set(module.name, observer);

    this._log(`${module.name} setup for lazy loading (${elements.length} elements)`);
  }

  /**
   * Get initialization status
   */
  getStatus() {
    return {
      total: this.modules.size,
      initialized: this.initialized.size,
      failed: this.failed.size,
      pending: this.modules.size - this.initialized.size - this.failed.size,
      modules: Object.fromEntries(this.initialized)
    };
  }

  /**
   * Destroy all observers and cleanup
   */
  destroy() {
    // Call module cleanup/destroy functions
    this.initialized.forEach((info) => {
      const r = info.result;
      if (typeof r === 'function') r();
      else if (r?.cleanup) r.cleanup();
      else if (r?.destroy) r.destroy();
    });

    this.observers.forEach(observer => observer.disconnect());
    this.observers.clear();
    this.modules.clear();
    this.initialized.clear();
    this.failed.clear();
  }

  /**
   * Logging utility
   */
  _log(message, data = null) {
    if (this.options.debug) {
      if (data) {
        console.log(`[App] ${message}`, data);
      } else {
        console.log(`[App] ${message}`);
      }
    }
  }
}
