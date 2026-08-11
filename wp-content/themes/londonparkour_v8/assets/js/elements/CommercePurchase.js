/**
 * Fire purchase on clasbpro result pages.
 */

import { lpMaybePurchaseFromDom } from '../utils/analytics.js';

/**
 * @returns {{ cleanup: () => void }}
 */
export function initCommercePurchase() {
  lpMaybePurchaseFromDom(document);
  return { cleanup: () => {} };
}
