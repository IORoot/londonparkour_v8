/**
 * Pay form — POST clasbpro /custom-checkout, then redirect to Stripe.
 *
 * The markup already posts to admin-post.php, so with JS off (or before this
 * module boots) the same payment still starts. This intercept is only for
 * inline errors without leaving /pay/.
 *
 * @param {ParentNode} [root]
 * @returns {{ destroy: () => void } | null}
 */
export function initPayForm(root = document) {
  const form = root.querySelector('form[data-form="pay"]');
  if (!form) return null;

  const cfg = () => (typeof window.lpPay === 'object' && window.lpPay ? window.lpPay : {});
  const button = form.querySelector('button[type="submit"]');
  let errorEl = form.closest('[data-component="pay-form"]')?.querySelector('[data-pay-error]') || null;

  const showError = (message) => {
    if (!errorEl) {
      errorEl = document.createElement('p');
      errorEl.className = 'font-body text-[13px] leading-[1.65] tracking-[0.1px] text-error m-0';
      errorEl.setAttribute('role', 'alert');
      errorEl.setAttribute('data-pay-error', '');
      form.parentNode?.insertBefore(errorEl, form);
    }
    errorEl.textContent = message || 'Something went wrong starting payment. Please try again.';
    errorEl.hidden = false;
  };

  const setLoading = (loading) => {
    if (!button) return;
    button.disabled = loading;
    button.setAttribute('aria-busy', loading ? 'true' : 'false');
  };

  const restUrl = () => {
    const raw = String(cfg().restUrl || '');
    if (!raw) return '';
    try {
      const url = new URL(raw, window.location.origin);
      if (url.hostname !== window.location.hostname || url.port !== window.location.port) {
        url.protocol = window.location.protocol;
        url.hostname = window.location.hostname;
        url.port = window.location.port;
      }
      return url.toString();
    } catch (e) {
      return raw;
    }
  };

  const onSubmit = async (event) => {
    const url = restUrl();
    if (!url) {
      return;
    }
    event.preventDefault();

    if (form.querySelector('[name="lp_company"]')?.value) {
      return;
    }

    const name = String(form.querySelector('[name="name"]')?.value || '').trim();
    const email = String(form.querySelector('[name="email"]')?.value || '').trim();
    const amount = String(form.querySelector('[name="amount"]')?.value || '').trim();

    setLoading(true);
    if (errorEl) {
      errorEl.hidden = true;
    }

    try {
      const res = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify({
          customer_name: name,
          customer_email: email,
          amount,
          origin_url: window.location.href,
        }),
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || data.error) {
        showError(data.message || 'Something went wrong starting payment. Please try again.');
        setLoading(false);
        return;
      }
      if (data.url) {
        window.location.href = data.url;
        return;
      }
      showError('No payment URL returned. Please try again.');
      setLoading(false);
    } catch (e) {
      showError('Network error. Please check your connection and try again.');
      setLoading(false);
    }
  };

  form.addEventListener('submit', onSubmit);

  return {
    destroy: () => form.removeEventListener('submit', onSubmit),
  };
}

export default initPayForm;
