document.addEventListener('DOMContentLoaded', () => {
    const isDesktop = () => window.matchMedia('(min-width: 768px)').matches;

    const panel         = document.getElementById('filters-panel');
    const countBadge    = document.getElementById('filters-selected-count');
    const compDataEl = document.getElementById('component-data');
    const componentType = compDataEl?.dataset?.type ||
        (window.location.pathname.match(/\/product\/([^\/\?]+)/i)?.[1] ?? '');
    //const clearAllBtn   = document.getElementById('clearFilters');

    // Mobile drawer controls
    const openBtn  = document.getElementById('filters-open');
    const closeBtn = document.getElementById('filters-close');
    const overlay  = document.getElementById('filters-overlay');
    const drawer   = document.getElementById('filters-drawer');
    const backdrop = document.getElementById('filters-backdrop');

    // call once at start
    syncOverlayAria();

    // keep in sync on viewport changes
    const mq = window.matchMedia('(min-width: 768px)');
    try {
        mq.addEventListener('change', syncOverlayAria);
    } catch { // Safari
        mq.addListener(syncOverlayAria);
    }

    const openMobileSection = () => {
        overlay.classList.add('active');
        // ✅ allow overlay subtree to receive taps
        overlay.classList.remove('pointer-events-none');   // <-- add this
        // (optional: overlay.classList.add('pointer-events-auto'); Tailwind has this too)

        backdrop.classList.remove('pointer-events-none');
        backdrop.classList.add('opacity-100');
        drawer.classList.remove('-translate-x-full');
        document.body.style.overflow = 'hidden';
        syncOverlayAria();
    };

    const closeMobileSection = () => {
        overlay.classList.remove('active');
        // ✅ block overlay subtree again so page behind is tappable
        overlay.classList.add('pointer-events-none');      // <-- add this
        // (optional: overlay.classList.remove('pointer-events-auto');)

        backdrop.classList.add('pointer-events-none');
        backdrop.classList.remove('opacity-100');
        drawer.classList.add('-translate-x-full');
        document.body.style.overflow = '';
        syncOverlayAria();
    };

    // Bind drawer controls even if the filter panel is missing

    if (openBtn && overlay && drawer && backdrop) {
        if (!openBtn.dataset.bound) {
            openBtn.addEventListener('click', openMobileSection);
            closeBtn?.addEventListener('click', closeMobileSection);
            backdrop.addEventListener('click', closeMobileSection);
            document.addEventListener('keydown', (e) => {
                if (window.matchMedia('(max-width: 767px)').matches && e.key === 'Escape') {
                    closeMobileSection();
                }
            });
            // Prevent duplicate bindings on PJAX / partial reloads
            openBtn.dataset.bound = '1';
        }
    } else {
        // Helpful debug if something is missing
        console.warn('[filters] Drawer markup missing', { openBtn, overlay, drawer, backdrop });
    }

    // ---------- FILTER PANEL WIRING (only if present) ----------
    if (!panel) return; // nothing else to wire on this page


    // Accordion toggles
    panel.querySelectorAll('.filter-toggle').forEach(btn => {
        btn.addEventListener('click', () => {
            const content = btn.parentElement.querySelector('.filter-content');
            const chev    = btn.querySelector('.chevron');
            content.classList.toggle('hidden');
            chev.classList.toggle('rotate-180');
        });
    });

    // Wire checkbox/radio
    panel.querySelectorAll('.filter-input').forEach(el => {
        el.addEventListener('change', () => {
            updateSelectedCount();
            handleFilterChange(1);
        });
    });

    // Initialize ranges
    initRanges();

    function initRanges() {
        const fills = panel.querySelectorAll('.range-fill');
        fills.forEach(fill => {
            const key = fill.dataset.key;
            const rMin = panel.querySelector(`.range-min[data-key="${CSS.escape(key)}"]`);
            const rMax = panel.querySelector(`.range-max[data-key="${CSS.escape(key)}"]`);
            const nMin = panel.querySelector(`.range-number-min[data-key="${CSS.escape(key)}"]`);
            const nMax = panel.querySelector(`.range-number-max[data-key="${CSS.escape(key)}"]`);

            const dMin = panel.querySelector(`.range-display-min[data-key="${CSS.escape(key)}"]`);
            const dMax = panel.querySelector(`.range-display-max[data-key="${CSS.escape(key)}"]`);

            const minLimit = Number(rMin.min);
            const maxLimit = Number(rMin.max);

            function clamp() {
                let vMin = Math.min(Number(rMin.value), Number(rMax.value));
                let vMax = Math.max(Number(rMin.value), Number(rMax.value));
                // snap inputs
                rMin.value = vMin;
                rMax.value = vMax;
                nMin.value = vMin;
                nMax.value = vMax;

                // display
                if (dMin) dMin.textContent = vMin;
                if (dMax) dMax.textContent = vMax;

                // filled bar
                const left = ((vMin - minLimit) / (maxLimit - minLimit)) * 100;
                const width = ((vMax - vMin) / (maxLimit - minLimit)) * 100;
                fill.style.left = left + '%';
                fill.style.width = width + '%';
            }

            // Bind events
            [rMin, rMax].forEach(inp => {
                inp.addEventListener('input', () => { clamp(); });
                inp.addEventListener('change', () => { updateSelectedCount(); handleFilterChange(1); });
            });
            [nMin, nMax].forEach(inp => {
                inp.addEventListener('input', () => {
                    // clamp into [min,max] and keep order
                    let v = Number(inp.value);
                    if (Number.isNaN(v)) v = (inp.classList.contains('range-number-min') ? minLimit : maxLimit);
                    v = Math.max(minLimit, Math.min(maxLimit, v));
                    if (inp.classList.contains('range-number-min')) rMin.value = v;
                    else rMax.value = v;
                    clamp();
                });
                inp.addEventListener('change', () => { updateSelectedCount(); handleFilterChange(1); });
            });

            // initial paint
            clamp();
        });
    }

/*    // Clear all
    clearAllBtn.addEventListener('click', () => {
        // Uncheck checkboxes
        panel.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
        // Clear radios
        panel.querySelectorAll('input[type="radio"]').forEach(r => r.checked = false);
        // Reset ranges to full span
        panel.querySelectorAll('.range-min').forEach(r => r.value = r.min);
        panel.querySelectorAll('.range-max').forEach(r => r.value = r.max);
        panel.querySelectorAll('.range-number-min').forEach(n => n.value = n.min);
        panel.querySelectorAll('.range-number-max').forEach(n => n.value = n.max);
        // Repaint fills & labels
        initRanges();
        updateSelectedCount();
        handleFilterChange(1);
    });*/

    function updateSelectedCount() {
        const checkedCheckboxes = panel.querySelectorAll('input[type="checkbox"]:checked').length;

        // radios by group
        const radioNames = new Map();
        panel.querySelectorAll('input[type="radio"]').forEach(r => {
            if (!radioNames.has(r.name)) radioNames.set(r.name, false);
            if (r.checked && r.value !== '') radioNames.set(r.name, true);
        });
        const checkedRadios = Array.from(radioNames.values()).filter(Boolean).length;

        // ranges: count if deviating from full span
        let rangeActive = 0;
        panel.querySelectorAll('.range-fill').forEach(fill => {
            const key = fill.dataset.key;
            const rMin = panel.querySelector(`.range-min[data-key="${CSS.escape(key)}"]`);
            const rMax = panel.querySelector(`.range-max[data-key="${CSS.escape(key)}"]`);
            if (!rMin || !rMax) return;

            const minLimit = Number(rMin.min);
            const maxLimit = Number(rMax.max);
            const vMin = Number(rMin.value);
            const vMax = Number(rMax.value);
            if (vMin > minLimit || vMax < maxLimit) rangeActive++;
        });

        const total = checkedCheckboxes + checkedRadios + rangeActive;
        if (total > 0) {
            countBadge.textContent = total;
            countBadge.classList.remove('hidden');
        } else {
            countBadge.classList.add('hidden');
        }
    }

    function handleFilterChange(page = 1) {
        const urlParams = new URLSearchParams();

        // Checkboxes
        panel.querySelectorAll('input[type="checkbox"]:checked').forEach(cb => {
            urlParams.append(cb.name, cb.value); // keeps key[] format
        });

        // Radios
        const radioNames = new Set(Array.from(panel.querySelectorAll('input[type="radio"]')).map(r => r.name));
        radioNames.forEach(name => {
            const checked = panel.querySelector(`input[type="radio"][name="${CSS.escape(name)}"]:checked`);
            if (checked && checked.value !== '') {
                urlParams.append(name, checked.value);
            }
        });

        // Ranges (send key_min / key_max)
        panel.querySelectorAll('.range-fill').forEach(fill => {
            const key = fill.dataset.key;
            const rMin = panel.querySelector(`.range-min[data-key="${CSS.escape(key)}"]`);
            const rMax = panel.querySelector(`.range-max[data-key="${CSS.escape(key)}"]`);
            if (!rMin || !rMax) return;

            const minLimit = Number(rMin.min);
            const maxLimit = Number(rMax.max);
            const vMin = Number(rMin.value);
            const vMax = Number(rMax.value);

            // Only include if narrowed (optional; remove this if you always want to send)
            if (vMin > minLimit) urlParams.append(`${key}_min`, String(vMin));
            if (vMax < maxLimit) urlParams.append(`${key}_max`, String(vMax));
        });

        // page
        urlParams.set('page', page);

        if (typeof showSpinner === 'function') showSpinner();

        const url = `/product/${componentType}?` + urlParams.toString();
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(res => res.json())
            .then(data => {
                document.getElementById('component-container').innerHTML = data.components;
                document.getElementById('pagination-container').innerHTML = data.pagination;
                // Rebind after DOM swap
                document.querySelectorAll('a[href^="/component/"]').forEach(link => handleViewDetailsButton(link));
                if (typeof addComponentToConfig === 'function') addComponentToConfig();
            })
            .catch(err => console.error('Error loading components:', err))
            .finally(() => {
                if (typeof hideSpinner === 'function') hideSpinner();
            });
    }

    function syncOverlayAria() {
        if (!overlay) return;
        if (isDesktop()) {
            overlay.removeAttribute('aria-hidden'); // desktop: never hide the container
        } else {
            // mobile: hide when drawer is closed, show when open
            const closed = drawer?.classList.contains('-translate-x-full');
            overlay.setAttribute('aria-hidden', closed ? 'true' : 'false');
        }
    }

    // with:
    document.querySelectorAll('.js-clear-filters').forEach(btn => {
        btn.addEventListener('click', () => {
            // Uncheck checkboxes, reset radios, ranges, etc…
            // (call your existing clearAll() if you have one)
            // Uncheck checkboxes
            panel.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
            // Clear radios
            panel.querySelectorAll('input[type="radio"]').forEach(r => r.checked = false);
            // Reset ranges to full span
            panel.querySelectorAll('.range-min').forEach(r => r.value = r.min);
            panel.querySelectorAll('.range-max').forEach(r => r.value = r.max);
            panel.querySelectorAll('.range-number-min').forEach(n => n.value = n.min);
            panel.querySelectorAll('.range-number-max').forEach(n => n.value = n.max);
            // Repaint fills & labels
            initRanges();
            updateSelectedCount();
            //handleFilterChange(1);
        });
    });

    // Initial selected count
    updateSelectedCount();
});