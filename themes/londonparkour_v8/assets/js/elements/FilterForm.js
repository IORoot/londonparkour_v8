/**
 * FilterForm — submit a filter form when one of its selects changes.
 *
 * The Concourse filter grid (parts/components/filter-grid.php) draws no submit
 * control, so a changed <select> has to submit the form itself. This is the
 * whole behaviour: no fetch, no state, no rendering. The markup is already
 * complete and correct without it — the same form carries an sr-only submit,
 * so with JS off, or before this module boots, the filter still works by
 * keyboard and by pressing Enter in the search field.
 *
 * Text inputs are deliberately NOT auto-submitted: submitting per keystroke
 * would reload the page mid-word.
 *
 * @param {ParentNode} root Scope to search. Defaults to the document.
 * @returns {Array<{destroy: () => void}>} One handle per form found.
 */
export const initAllFilterForms = (root = document) =>
  [...root.querySelectorAll('form[data-filter-form]')].map((form) => {
    const onChange = (event) => {
      if (event.target instanceof HTMLSelectElement) form.requestSubmit();
    };

    form.addEventListener('change', onChange);

    return { destroy: () => form.removeEventListener('change', onChange) };
  });

export default initAllFilterForms;
