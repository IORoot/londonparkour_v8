/**
 * GA4 ecommerce via dataLayer (GTM). CMP gates tags later — we always push.
 *
 * Funnel: select_item → begin_checkout → add_payment_info → purchase.
 * item_category is one of: class | workshop | private | coupon.
 *
 * Names are GA4 recommended events (except newsletter_subscribe) so
 * Monetization, the checkout funnel, and Total revenue work. Split product
 * type with item_category, not a name prefix.
 */

const EVENT = {
  viewItem: 'view_item',
  selectItem: 'select_item',
  beginCheckout: 'begin_checkout',
  addPaymentInfo: 'add_payment_info',
  purchase: 'purchase',
  generateLead: 'generate_lead',
  newsletterSubscribe: 'newsletter_subscribe',
  viewSearchResults: 'view_search_results',
  videoStart: 'video_start',
  videoProgress: 'video_progress',
  selectContent: 'select_content',
};

/**
 * @typedef {'class'|'workshop'|'private'|'coupon'} LpCommerceCategory
 */

/** @type {Record<string, unknown>|null} */
let checkoutContext = null;

/**
 * @param {Record<string, unknown>} payload
 */
export function lpPushDataLayer(payload) {
  window.dataLayer = window.dataLayer || [];
  window.dataLayer.push(payload);
}

/**
 * @param {string} [raw]
 * @param {string} [itemType]
 * @returns {LpCommerceCategory}
 */
export function lpCommerceCategory(raw, itemType) {
  if (raw === 'class' || raw === 'workshop' || raw === 'private' || raw === 'coupon') {
    return raw;
  }
  return itemType === 'pack' || itemType === 'coupon' ? 'coupon' : 'class';
}

/**
 * @param {LpCommerceCategory} category
 * @param {string|number} id
 * @returns {string}
 */
function commerceItemId(category, id) {
  const prefix = category === 'coupon' ? 'pack' : category;
  return `${prefix}:${id}`;
}

/**
 * @param {object} opts
 * @param {LpCommerceCategory} opts.category
 * @param {string|number} opts.id
 * @param {string} [opts.name]
 * @param {number} [opts.price]
 * @param {number} [opts.quantity]
 * @returns {Record<string, unknown>}
 */
function commerceItem(opts) {
  const quantity = opts.quantity && opts.quantity > 0 ? opts.quantity : 1;
  return {
    item_id: commerceItemId(opts.category, opts.id),
    item_name: opts.name || commerceItemId(opts.category, opts.id),
    item_category: opts.category,
    price: Number(opts.price) || 0,
    quantity,
  };
}

/**
 * @param {object} opts
 * @param {string|number} opts.id
 * @param {string} [opts.name]
 * @param {number} [opts.price]
 * @param {number} [opts.quantity]
 * @param {string} [opts.listName]
 * @param {string} [opts.category]
 * @param {'class'|'pack'} [opts.itemType]
 */
export function lpSelectItem(opts) {
  const category = lpCommerceCategory(opts.category, opts.itemType);
  const item = commerceItem({
    category,
    id: opts.id,
    name: opts.name,
    price: opts.price,
    quantity: opts.quantity,
  });
  lpPushDataLayer({ ecommerce: null });
  lpPushDataLayer({
    event: EVENT.selectItem,
    ecommerce: {
      item_list_name: opts.listName || 'site',
      currency: 'GBP',
      value: Number(item.price) * Number(item.quantity) || 0,
      items: [item],
    },
  });
}

/**
 * @param {object} opts
 * @param {string|number} opts.id
 * @param {string} [opts.name]
 * @param {number} [opts.price]
 * @param {number} [opts.quantity]
 * @param {string} [opts.currency]
 * @param {string} [opts.category]
 * @param {'class'|'pack'} [opts.itemType]
 * @param {string} [opts.listName]
 */
export function lpBeginCheckout(opts) {
  const category = lpCommerceCategory(opts.category, opts.itemType);
  const item = commerceItem({
    category,
    id: opts.id,
    name: opts.name,
    price: opts.price,
    quantity: opts.quantity,
  });
  const currency = opts.currency || 'GBP';
  const value = Number(item.price) * Number(item.quantity) || 0;
  const ecommerce = {
    currency,
    value,
    items: [item],
  };

  checkoutContext = {
    category,
    id: opts.id,
    name: opts.name || '',
    price: opts.price || 0,
    quantity: opts.quantity || 1,
    currency,
    listName: opts.listName || '',
  };

  lpPushDataLayer({ ecommerce: null });
  lpPushDataLayer({
    event: EVENT.beginCheckout,
    ecommerce,
  });
}

/**
 * Pay CTA — Stripe checkout or book-with-coupon submit.
 *
 * @param {object} [opts]
 * @param {string} [opts.paymentType]
 * @param {number} [opts.price]
 * @param {number} [opts.quantity]
 * @param {string} [opts.currency]
 */
export function lpAddPaymentInfo(opts = {}) {
  const ctx = checkoutContext;
  if (!ctx) return;

  const quantity = opts.quantity && opts.quantity > 0 ? opts.quantity : ctx.quantity || 1;
  const price = opts.price && opts.price > 0 ? opts.price : ctx.price || 0;
  const currency = opts.currency || ctx.currency || 'GBP';
  const item = commerceItem({
    category: ctx.category,
    id: ctx.id,
    name: ctx.name,
    price,
    quantity,
  });
  const value = (Number(price) || 0) * quantity;
  const ecommerce = {
    currency,
    value,
    payment_type: opts.paymentType || 'stripe',
    items: [item],
  };

  lpPushDataLayer({ ecommerce: null });
  lpPushDataLayer({
    event: EVENT.addPaymentInfo,
    ecommerce,
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

  const items = Array.isArray(opts.items) ? opts.items : [];
  lpPushDataLayer({ ecommerce: null });
  lpPushDataLayer({
    event: EVENT.purchase,
    ecommerce: {
      transaction_id: tid,
      value: Number(opts.value) || 0,
      currency: opts.currency || 'GBP',
      items,
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

/**
 * @param {string} key
 * @returns {boolean} true if this is the first time in the session
 */
function oncePerSession(key) {
  try {
    if (sessionStorage.getItem(key)) return false;
    sessionStorage.setItem(key, '1');
    return true;
  } catch {
    return true;
  }
}

/**
 * @param {Array<Record<string, unknown>>} items
 * @param {string} [currency]
 */
export function lpViewItem(items, currency = 'GBP') {
  if (!Array.isArray(items) || !items.length) return;

  let value = 0;
  items.forEach((item) => {
    value += (Number(item.price) || 0) * (Number(item.quantity) || 1);
  });

  const ecommerce = { currency, value, items };

  lpPushDataLayer({ ecommerce: null });
  lpPushDataLayer({ event: EVENT.viewItem, ecommerce });
}

export function lpGenerateLead() {
  if (!oncePerSession('lp_generate_lead')) return;
  lpPushDataLayer({ event: EVENT.generateLead });
}

/**
 * @param {'dispatch'|'booking_drawer'} method
 * @param {string} [sessionKey]
 */
export function lpNewsletterSubscribe(method, sessionKey = '') {
  const key =
    method === 'booking_drawer'
      ? `lp_newsletter_booking_${sessionKey || '1'}`
      : 'lp_newsletter_dispatch';
  if (!oncePerSession(key)) return;
  lpPushDataLayer({ event: EVENT.newsletterSubscribe, method });
}

/**
 * @param {object} opts
 * @param {string} opts.searchTerm
 * @param {number} opts.resultCount
 * @param {string} opts.searchFilter
 */
export function lpViewSearchResults(opts) {
  const term = String(opts.searchTerm || '');
  const filter = String(opts.searchFilter || 'all');
  if (!oncePerSession(`lp_search_${filter}:${term}`)) return;
  lpPushDataLayer({
    event: EVENT.viewSearchResults,
    search_term: term,
    result_count: Number(opts.resultCount) || 0,
    search_filter: filter,
  });
}

/**
 * @param {object} opts
 * @param {string} opts.videoTitle
 * @param {string} [opts.seriesName]
 */
export function lpVideoStart(opts) {
  lpPushDataLayer({
    event: EVENT.videoStart,
    video_title: opts.videoTitle || '',
    video_provider: 'youtube',
    series_name: opts.seriesName || '',
  });
}

/**
 * @param {object} opts
 * @param {string} opts.videoTitle
 * @param {string} [opts.seriesName]
 * @param {number} opts.videoPercent
 */
export function lpVideoProgress(opts) {
  lpPushDataLayer({
    event: EVENT.videoProgress,
    video_title: opts.videoTitle || '',
    video_provider: 'youtube',
    series_name: opts.seriesName || '',
    video_percent: Number(opts.videoPercent) || 0,
  });
}

/**
 * @param {object} opts
 * @param {string} opts.contentType
 * @param {string|number} opts.contentId
 * @param {string} opts.contentName
 * @param {string} [opts.seriesName]
 */
export function lpSelectContent(opts) {
  lpPushDataLayer({
    event: EVENT.selectContent,
    content_type: opts.contentType || '',
    content_id: String(opts.contentId || ''),
    content_name: opts.contentName || '',
    series_name: opts.seriesName || '',
  });
}

function fireDomEvent(el) {
  const name = el.getAttribute('data-lp-event') || '';
  if (name === 'generate_lead') {
    lpGenerateLead();
    return;
  }
  if (name === 'newsletter_subscribe') {
    lpNewsletterSubscribe(el.getAttribute('data-lp-method') === 'booking_drawer' ? 'booking_drawer' : 'dispatch');
    return;
  }
  if (name === 'view_search_results') {
    lpViewSearchResults({
      searchTerm: el.getAttribute('data-lp-search-term') || '',
      resultCount: Number(el.getAttribute('data-lp-result-count') || 0),
      searchFilter: el.getAttribute('data-lp-search-filter') || 'all',
    });
    return;
  }
  if (name === 'view_item') {
    let items = [];
    try {
      items = JSON.parse(el.getAttribute('data-lp-items') || '[]');
    } catch {
      items = [];
    }
    lpViewItem(items);
  }
}

/**
 * Page-load markers + select_content click delegation.
 *
 * @param {ParentNode} [root]
 * @returns {{ cleanup: () => void }}
 */
export function lpBootAnalytics(root = document) {
  lpMaybePurchaseFromDom(root);

  const purchase = root.querySelector('[data-lp-purchase]');
  if (purchase && purchase.getAttribute('data-lp-newsletter') === '1') {
    lpNewsletterSubscribe('booking_drawer', purchase.getAttribute('data-lp-purchase') || '');
  }

  root.querySelectorAll('[data-lp-event]').forEach((el) => fireDomEvent(el));

  const onClick = (event) => {
    const el = event.target instanceof Element ? event.target.closest('[data-lp-select-content]') : null;
    if (!el) return;
    lpSelectContent({
      contentType: el.getAttribute('data-lp-content-type') || '',
      contentId: el.getAttribute('data-lp-content-id') || '',
      contentName: el.getAttribute('data-lp-content-name') || '',
      seriesName: el.getAttribute('data-lp-series-name') || '',
    });
  };

  document.addEventListener('click', onClick);
  return { cleanup: () => document.removeEventListener('click', onClick) };
}
