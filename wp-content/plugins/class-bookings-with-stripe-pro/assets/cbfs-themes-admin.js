(function () {
	'use strict';

	var cfg = window.clasbproThemes || {};
	var filesModal = null;
	var previewModal = null;
	var previewLoadId = 0;
	var previewLoadTimer = null;
	var previewActiveSlug = '';

	function qs(sel, ctx) {
		return (ctx || document).querySelector(sel);
	}

	function qsa(sel, ctx) {
		return Array.prototype.slice.call((ctx || document).querySelectorAll(sel));
	}

	function languageForFile(file) {
		var lower = (file || '').toLowerCase();
		if (lower.endsWith('.json')) {
			return 'json';
		}
		if (lower.endsWith('.css')) {
			return 'css';
		}
		if (lower.endsWith('.svg')) {
			return 'xml';
		}
		if (lower.endsWith('.php')) {
			return 'php';
		}
		if (lower.endsWith('.js')) {
			return 'javascript';
		}
		return 'plaintext';
	}

	function setCodeContent(codeEl, content, file) {
		if (!codeEl) {
			return;
		}

		codeEl.dataset.rawContent = content || '';
		codeEl.className = 'clasbpro-theme-code-content';

		if (!content) {
			codeEl.textContent = '';
			return;
		}

		var lang = languageForFile(file);
		if (!window.hljs || lang === 'plaintext') {
			codeEl.textContent = content;
			return;
		}

		try {
			var result = window.hljs.highlight(content, { language: lang, ignoreIllegals: true });
			codeEl.innerHTML = result.value;
			codeEl.className = 'clasbpro-theme-code-content hljs language-' + lang;
		} catch (err) {
			codeEl.textContent = content;
		}
	}

	function getFilesModal() {
		if (!filesModal) {
			filesModal = document.getElementById('clasbpro-theme-files-modal');
		}
		return filesModal;
	}

	function getPreviewModal() {
		if (!previewModal) {
			previewModal = document.getElementById('clasbpro-theme-preview-modal');
		}
		return previewModal;
	}

	function clearPreviewTimer() {
		if (previewLoadTimer) {
			window.clearTimeout(previewLoadTimer);
			previewLoadTimer = null;
		}
	}

	function setPreviewState(m, state, message) {
		var status = qs('.clasbpro-theme-preview-modal__status', m);
		var loading = qs('.clasbpro-theme-preview-modal__loading', m);
		var errorWrap = qs('.clasbpro-theme-preview-modal__error', m);
		var errorText = qs('.clasbpro-theme-preview-modal__error-text', m);
		var iframe = qs('.clasbpro-theme-preview-modal__iframe', m);

		if (status) {
			status.hidden = state === 'ready';
		}
		if (loading) {
			loading.hidden = state !== 'loading';
		}
		if (errorWrap) {
			errorWrap.hidden = state !== 'error';
		}
		if (errorText && message) {
			errorText.textContent = message;
		}
		if (iframe) {
			iframe.classList.toggle('is-loading', state === 'loading');
			iframe.classList.toggle('is-visible', state === 'ready');
		}
	}

	function populatePreviewClassSelect(m) {
		var select = qs('#clasbpro-theme-preview-class', m);
		if (!select) {
			return;
		}

		var current = select.value || 'default';
		select.innerHTML = '';
		(cfg.previewClasses || []).forEach(function (item) {
			var opt = document.createElement('option');
			opt.value = item.id;
			opt.textContent = item.name;
			select.appendChild(opt);
		});

		var hasCurrent = (cfg.previewClasses || []).some(function (item) {
			return item.id === current;
		});
		select.value = hasCurrent ? current : 'default';
	}

	function getPreviewClassSource(m) {
		var select = qs('#clasbpro-theme-preview-class', m || getPreviewModal());
		return select ? select.value : 'default';
	}

	function fetchPreviewUrl(slug, classSource) {
		var body = new FormData();
		body.append('action', 'clasbpro_theme_preview_url');
		body.append('nonce', cfg.previewNonce || '');
		body.append('theme', slug);
		body.append('class', classSource || 'default');

		return fetch(cfg.ajaxUrl || '/wp-admin/admin-ajax.php', {
			method: 'POST',
			credentials: 'same-origin',
			body: body,
		})
			.then(function (r) {
				return r.json();
			})
			.then(function (json) {
				if (!json.success || !json.data || !json.data.url) {
					throw new Error((json.data && json.data.message) || (cfg.i18n && cfg.i18n.previewError) || 'Preview error');
				}
				return json.data.url;
			});
	}

	function loadPreviewIframe(slug, loadId, classSource) {
		var m = getPreviewModal();
		var iframe = m ? qs('.clasbpro-theme-preview-modal__iframe', m) : null;
		if (!m || !iframe || loadId !== previewLoadId) {
			return;
		}

		var source = classSource || getPreviewClassSource(m);
		setPreviewState(m, 'loading');

		fetchPreviewUrl(slug, source)
			.then(function (url) {
				if (loadId !== previewLoadId) {
					return;
				}

				return new Promise(function (resolve, reject) {
					function onLoad() {
						iframe.removeEventListener('load', onLoad);
						iframe.removeEventListener('error', onError);
						clearPreviewTimer();
						if (loadId !== previewLoadId) {
							return;
						}
						resolve();
					}

					function onError() {
						iframe.removeEventListener('load', onLoad);
						iframe.removeEventListener('error', onError);
						clearPreviewTimer();
						reject(new Error('iframe error'));
					}

					iframe.addEventListener('load', onLoad);
					iframe.addEventListener('error', onError);

					clearPreviewTimer();
					previewLoadTimer = window.setTimeout(function () {
						iframe.removeEventListener('load', onLoad);
						iframe.removeEventListener('error', onError);
						reject(new Error('timeout'));
					}, 20000);

					window.requestAnimationFrame(function () {
						if (loadId !== previewLoadId) {
							return;
						}
						iframe.removeAttribute('srcdoc');
						iframe.src = url;
					});
				});
			})
			.then(function () {
				if (loadId !== previewLoadId) {
					return;
				}
				setPreviewState(m, 'ready');
			})
			.catch(function (err) {
				if (loadId !== previewLoadId) {
					return;
				}
				setPreviewState(
					m,
					'error',
					err && err.message ? err.message : (cfg.i18n && cfg.i18n.previewError) || 'Preview could not be loaded.'
				);
			});
	}

	function openFilesModal(slug) {
		var m = getFilesModal();
		var theme = (cfg.themes && cfg.themes[slug]) || null;
		if (!m || !theme) {
			return;
		}

		var title = qs('#clasbpro-theme-files-modal-title', m);
		var list = qs('.clasbpro-theme-files-list', m);
		var filename = qs('.clasbpro-theme-code-filename', m);
		var code = qs('.clasbpro-theme-code-content', m);
		var copyBtn = qs('.clasbpro-theme-copy-btn', m);

		if (title) {
			title.textContent = (cfg.i18n && cfg.i18n.filesTitle ? cfg.i18n.filesTitle + ': ' : '') + theme.name;
		}

		if (list) {
			list.innerHTML = '';
			theme.files.forEach(function (file) {
				var li = document.createElement('li');
				var btn = document.createElement('button');
				btn.type = 'button';
				btn.className = 'clasbpro-theme-file-btn';
				btn.setAttribute('data-theme', slug);
				btn.setAttribute('data-file', file);
				btn.textContent = file;
				li.appendChild(btn);
				list.appendChild(li);
			});
		}

		if (filename) {
			filename.textContent = '';
		}
		setCodeContent(code, (cfg.i18n && cfg.i18n.selectFile) || 'Select a file to view its source.', '');
		if (copyBtn) {
			copyBtn.disabled = true;
		}

		m.removeAttribute('hidden');
		m.classList.add('is-open');
		document.body.classList.add('clasbpro-theme-files-modal-open');

		if (theme.files.length > 0) {
			var firstBtn = qs('.clasbpro-theme-file-btn', m);
			if (firstBtn) {
				loadFile(firstBtn);
			}
		}
	}

	function closeFilesModal() {
		var m = getFilesModal();
		if (!m) {
			return;
		}
		m.setAttribute('hidden', '');
		m.classList.remove('is-open');
		document.body.classList.remove('clasbpro-theme-files-modal-open');
	}

	function openPreviewModal(slug) {
		var m = getPreviewModal();
		var theme = (cfg.themes && cfg.themes[slug]) || null;
		if (!m || !theme) {
			return;
		}

		previewLoadId += 1;
		var loadId = previewLoadId;
		previewActiveSlug = slug;

		var title = qs('#clasbpro-theme-preview-modal-title', m);
		var iframe = qs('.clasbpro-theme-preview-modal__iframe', m);

		if (title) {
			title.textContent = (cfg.i18n && cfg.i18n.previewTitle ? cfg.i18n.previewTitle + ': ' : '') + theme.name;
		}

		populatePreviewClassSelect(m);

		if (iframe) {
			iframe.classList.remove('is-visible');
			iframe.removeAttribute('src');
			iframe.removeAttribute('srcdoc');
		}

		m.removeAttribute('hidden');
		m.classList.add('is-open');
		document.body.classList.add('clasbpro-theme-preview-modal-open');

		window.requestAnimationFrame(function () {
			loadPreviewIframe(slug, loadId);
		});
	}

	function closePreviewModal() {
		previewLoadId += 1;
		previewActiveSlug = '';
		clearPreviewTimer();

		var m = getPreviewModal();
		if (!m) {
			return;
		}

		var iframe = qs('.clasbpro-theme-preview-modal__iframe', m);
		if (iframe) {
			iframe.removeAttribute('src');
			iframe.removeAttribute('srcdoc');
			iframe.classList.remove('is-visible', 'is-loading');
		}

		setPreviewState(m, 'loading');
		m.setAttribute('hidden', '');
		m.classList.remove('is-open');
		document.body.classList.remove('clasbpro-theme-preview-modal-open');
	}

	function loadFile(btn) {
		var m = getFilesModal();
		if (!m) {
			return;
		}

		var slug = btn.getAttribute('data-theme');
		var file = btn.getAttribute('data-file');
		var codeEl = qs('.clasbpro-theme-code-content', m);
		var nameEl = qs('.clasbpro-theme-code-filename', m);
		var copyBtn = qs('.clasbpro-theme-copy-btn', m);

		qsa('.clasbpro-theme-file-btn', m).forEach(function (b) {
			b.classList.toggle('is-selected', b === btn);
		});

		if (nameEl) {
			nameEl.textContent = file;
		}
		setCodeContent(codeEl, (cfg.i18n && cfg.i18n.loading) || 'Loading…', '');
		if (copyBtn) {
			copyBtn.disabled = true;
		}

		var body = new FormData();
		body.append('action', 'clasbpro_theme_file');
		body.append('nonce', cfg.nonce || '');
		body.append('theme', slug);
		body.append('file', file);

		fetch(cfg.ajaxUrl || '/wp-admin/admin-ajax.php', {
			method: 'POST',
			credentials: 'same-origin',
			body: body,
		})
			.then(function (r) {
				return r.json();
			})
			.then(function (json) {
				if (!json.success) {
					throw new Error((json.data && json.data.message) || 'error');
				}
				var content = json.data.content || '';
				setCodeContent(codeEl, content, file);
				if (copyBtn) {
					copyBtn.disabled = !content;
				}
			})
			.catch(function () {
				setCodeContent(codeEl, (cfg.i18n && cfg.i18n.error) || 'Could not load file.', '');
			});
	}

	document.addEventListener('click', function (e) {
		var openFilesBtn = e.target.closest('.clasbpro-theme-open-files');
		if (openFilesBtn) {
			openFilesModal(openFilesBtn.getAttribute('data-theme'));
			return;
		}

		var openPreviewBtn = e.target.closest('.clasbpro-theme-open-preview');
		if (openPreviewBtn) {
			openPreviewModal(openPreviewBtn.getAttribute('data-theme'));
			return;
		}

		var retryBtn = e.target.closest('.clasbpro-theme-preview-retry');
		if (retryBtn && previewActiveSlug) {
			previewLoadId += 1;
			loadPreviewIframe(previewActiveSlug, previewLoadId);
			return;
		}

		if (e.target.closest('[data-close-files-modal]')) {
			closeFilesModal();
			return;
		}

		if (e.target.closest('[data-close-preview-modal]')) {
			closePreviewModal();
			return;
		}

		var fileBtn = e.target.closest('.clasbpro-theme-file-btn');
		if (fileBtn && getFilesModal() && getFilesModal().classList.contains('is-open')) {
			loadFile(fileBtn);
			return;
		}

		var copyBtn = e.target.closest('.clasbpro-theme-copy-btn');
		if (copyBtn && !copyBtn.disabled) {
			var m = getFilesModal();
			var code = m ? qs('.clasbpro-theme-code-content', m) : null;
			var raw = code && code.dataset.rawContent ? code.dataset.rawContent : (code ? code.textContent : '');
			if (!raw) {
				return;
			}
			navigator.clipboard.writeText(raw).then(function () {
				var label = copyBtn.textContent;
				copyBtn.textContent = (cfg.i18n && cfg.i18n.copied) || 'Copied!';
				setTimeout(function () {
					copyBtn.textContent = label;
				}, 1500);
			});
		}
	});

	document.addEventListener('change', function (e) {
		if (e.target && e.target.id === 'clasbpro-theme-preview-class' && previewActiveSlug) {
			previewLoadId += 1;
			loadPreviewIframe(previewActiveSlug, previewLoadId, e.target.value);
		}
	});

	document.addEventListener('keydown', function (e) {
		if (e.key !== 'Escape') {
			return;
		}
		if (getPreviewModal() && getPreviewModal().classList.contains('is-open')) {
			closePreviewModal();
			return;
		}
		if (getFilesModal() && getFilesModal().classList.contains('is-open')) {
			closeFilesModal();
		}
	});

	function initGalleryFilters() {
		var grid = document.getElementById('clasbpro-themes-grid');
		if (!grid) {
			return;
		}

		var cards = qsa('.clasbpro-theme-card', grid);
		var searchInput = document.getElementById('clasbpro-themes-search');
		var sortSelect = document.getElementById('clasbpro-themes-sort');
		var countEl = document.getElementById('clasbpro-themes-count');
		var emptyEl = document.getElementById('clasbpro-themes-empty');
		var toolbar = document.getElementById('clasbpro-themes-toolbar');
		var selectedTags = [];

		function getToolbarTagButtons() {
			return toolbar ? qsa('.clasbpro-themes-tag', toolbar) : [];
		}

		function updateTagButtons() {
			getToolbarTagButtons().forEach(function (btn) {
				var tag = btn.getAttribute('data-tag') || '';
				var active = tag === '' ? selectedTags.length === 0 : selectedTags.indexOf(tag) !== -1;
				btn.classList.toggle('is-active', active);
				btn.setAttribute('aria-pressed', active ? 'true' : 'false');
			});
		}

		function setTagFilter(tags) {
			selectedTags = tags.slice();
			updateTagButtons();
			applyFilters();
		}

		function toggleTag(tag) {
			if (!tag) {
				setTagFilter([]);
				return;
			}

			var idx = selectedTags.indexOf(tag);
			if (idx === -1) {
				selectedTags.push(tag);
			} else {
				selectedTags.splice(idx, 1);
			}
			updateTagButtons();
			applyFilters();
		}

		function compareCards(a, b, sort) {
			if (sort === 'slug-asc') {
				return (a.getAttribute('data-slug') || '').localeCompare(b.getAttribute('data-slug') || '');
			}
			if (sort === 'name-desc') {
				return (b.getAttribute('data-name') || '').localeCompare(a.getAttribute('data-name') || '');
			}
			return (a.getAttribute('data-name') || '').localeCompare(b.getAttribute('data-name') || '');
		}

		function cardMatches(card, query) {
			if (!query) {
				return true;
			}
			var haystack = card.getAttribute('data-search') || '';
			return haystack.indexOf(query) !== -1;
		}

		function cardHasTag(card) {
			if (!selectedTags.length) {
				return true;
			}
			var cardTags = (card.getAttribute('data-tags') || '').split(',').filter(Boolean);
			return selectedTags.some(function (tag) {
				return cardTags.indexOf(tag) !== -1;
			});
		}

		function updateCount(visible) {
			if (!countEl) {
				return;
			}
			var n = visible.length;
			var tpl = n === 1
				? ((cfg.i18n && cfg.i18n.themeSingular) || '%d theme')
				: ((cfg.i18n && cfg.i18n.themePlural) || '%d themes');
			countEl.textContent = tpl.replace('%d', String(n));
		}

		function applyFilters() {
			var query = searchInput ? searchInput.value.trim().toLowerCase() : '';
			var sort = sortSelect ? sortSelect.value : 'name-asc';
			var visible = cards.filter(function (card) {
				return cardMatches(card, query) && cardHasTag(card);
			});

			visible.sort(function (a, b) {
				return compareCards(a, b, sort);
			});

			visible.forEach(function (card) {
				grid.appendChild(card);
			});

			cards.forEach(function (card) {
				card.hidden = visible.indexOf(card) === -1;
			});

			if (emptyEl) {
				emptyEl.hidden = visible.length > 0;
			}

			updateCount(visible);
		}

		if (searchInput) {
			searchInput.addEventListener('input', applyFilters);
		}

		if (sortSelect) {
			sortSelect.addEventListener('change', applyFilters);
		}

		if (toolbar) {
			toolbar.addEventListener('click', function (e) {
				var tagBtn = e.target.closest('.clasbpro-themes-tag');
				if (!tagBtn || !toolbar.contains(tagBtn)) {
					return;
				}
				var tag = tagBtn.getAttribute('data-tag') || '';
				if (tag === '') {
					setTagFilter([]);
					return;
				}
				toggleTag(tag);
			});
		}

		document.addEventListener('click', function (e) {
			var cardTagBtn = e.target.closest('.clasbpro-theme-card__tag');
			if (!cardTagBtn) {
				return;
			}
			var tag = cardTagBtn.getAttribute('data-filter-tag') || '';
			if (!tag) {
				return;
			}
			setTagFilter([tag]);
		});

		applyFilters();
	}

	function initSourceButtonGroup() {
		var form = qs('.clasbpro-themes-source-form');
		if (!form) {
			return;
		}

		function syncSelectedState() {
			qsa('.clasbpro-themes-source-button-group__option', form).forEach(function (option) {
				var input = qs('input[type="radio"]', option);
				option.classList.toggle('is-selected', !!(input && input.checked));
			});
		}

		qsa('input[name="theme_source"]', form).forEach(function (input) {
			input.addEventListener('change', function () {
				syncSelectedState();
				if (input.checked) {
					form.submit();
				}
			});
		});
	}

	initSourceButtonGroup();
	initGalleryFilters();
})();
