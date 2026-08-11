/**
 * GA4 ecommerce via dataLayer (GTM). CMP gates tags later — we always push.
 */

/**
 * @param {Record<string, unknown>} payload
 */
export function lpPushDataLayer(payload) {
  window.dataLayer = window.dataLayer || [];
  window.dataLayer.push(payload);
}

/**
 * @param {'class'|'pack'} itemType
 * @param {string|number} id
 * @param {string} [name]
 * @param {number} [price]
 * @returns {Record<string, unknown>}
 */
function commerceItem(itemType, id, name = '', price = 0) {
  const itemId = `${itemType}:${id}`;
  const item = {
    item_id: itemId,
    item_name: name || itemId,
    item_category: itemType === 'pack' ? 'coupon' : 'class',
  };
  if (price > 0) {
    item.price = price;
  }
  return item;
}

/**
 * @param {object} opts
 * @param {'class'|'pack'} opts.itemType
 * @param {string|number} opts.id
 * @param {string} [opts.name]
 * @param {number} [opts.price]
 * @param {string} [opts.listName]
 */
export function lpSelectItem(opts) {
  const item = commerceItem(opts.itemType, opts.id, opts.name, opts.price);
  lpPushDataLayer({ ecommerce: null });
  lpPushDataLayer({
    event: 'select_item',
    ecommerce: {
      item_list_name: opts.listName || 'site',
      items: [item],
    },
  });
}

/**
 * @param {object} opts
 * @param {'class'|'pack'} opts.itemType
 * @param {string|number} opts.id
 * @param {string} [opts.name]
 * @param {number} [opts.price]
 * @param {string} [opts.currency]
 */
export function lpBeginCheckout(opts) {
  const item = commerceItem(opts.itemType, opts.id, opts.name, opts.price);
  lpPushDataLayer({ ecommerce: null });
  lpPushDataLayer({
    event: 'begin_checkout',
    ecommerce: {
      currency: opts.currency || 'GBP',
      items: [item],
    },
  });
}

/**
 * @param {object} opts
 * @param {string} opts.transactionId
 * @param {number} [opts.value]
 * @param {string} [opts.currency]
 * @param {Array<Record<string, unknown>>} [opts.items]
 */
export function lpPurchase(opts) {
  const tid = String(opts.transactionId || '');
  if (!tid) return;

  const key = `lp_purchase_${tid}`;
  try {
    if (sessionStorage.getItem(key)) return;
    sessionStorage.setItem(key, '1');
  } catch {
    // private mode — still fire once per page load via module flag
  }

  lpPushDataLayer({ ecommerce: null });
  lpPushDataLayer({
    event: 'purchase',
    ecommerce: {
      transaction_id: tid,
      value: opts.value || 0,
      currency: opts.currency || 'GBP',
      items: opts.items || [],
    },
  });
}

/**
 * Read purchase payload from a status-page marker and fire once.
 */
export function lpMaybePurchaseFromDom(root = document) {
  const el = root.querySelector('[data-lp-purchase]');
  if (!el) return;

  let items = [];
  try {
    items = JSON.parse(el.getAttribute('data-lp-purchase-items') || '[]');
  } catch {
    items = [];
  }

  lpPurchase({
    transactionId: el.getAttribute('data-lp-purchase') || '',
    value: Number(el.getAttribute('data-lp-purchase-value') || 0),
    currency: el.getAttribute('data-lp-purchase-currency') || 'GBP',
    items,
  });
}
