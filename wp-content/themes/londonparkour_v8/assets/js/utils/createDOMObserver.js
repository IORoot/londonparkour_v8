/**
 * Create a MutationObserver that watches for elements matching a selector
 * @param {string} selector - CSS selector to watch for
 * @param {Function} callback - Function to call when matching elements are added
 * @param {Object} options - Optional configuration
 * @param {number} options.debounce - Debounce delay in ms (default: 100)
 * @param {Element} [options.root=document.body] - Root element to observe
 * @returns {MutationObserver & { cleanup: () => void }} Observer instance with cleanup
 */
export function createDOMObserver(selector, callback, options = {}) {
  const { debounce = 100, root = document.body } = options;
  let timeoutId = null;

  const observer = new MutationObserver((mutations) => {
    let shouldCallback = false;

    for (const mutation of mutations) {
      for (const node of mutation.addedNodes) {
        if (node.nodeType !== 1) continue; // Skip non-element nodes

        // Check if the added node matches the selector
        if (node.matches?.(selector)) {
          shouldCallback = true;
          break;
        }

        // Check if any child matches the selector
        if (node.querySelectorAll?.(selector).length > 0) {
          shouldCallback = true;
          break;
        }
      }
      if (shouldCallback) break;
    }

    if (shouldCallback) {
      // Debounce to ensure DOM is fully rendered
      if (timeoutId) clearTimeout(timeoutId);
      timeoutId = setTimeout(() => {
        callback();
        timeoutId = null;
      }, debounce);
    }
  });

  observer.observe(root, {
    childList: true,
    subtree: true
  });

  // Provide a cleanup helper that also clears pending debounce timers
  observer.cleanup = () => {
    if (timeoutId) {
      clearTimeout(timeoutId);
      timeoutId = null;
    }
    observer.disconnect();
  };

  return observer;
}
