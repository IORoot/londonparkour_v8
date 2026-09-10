/**
 * Fire dataLayer events from PHP markers and select_content clicks.
 */

import { lpBootAnalytics } from '../utils/analytics.js';

/**
 * @returns {{ cleanup: () => void }}
 */
export function initCommercePurchase() {
  return lpBootAnalytics(document);
}
