(function () {
    function setupOne(root) {
        const input     = root.querySelector('.js-ai-input');
        const button    = root.querySelector('.js-ai-btn');
        const ctx       = root.querySelector('.js-ai-context'); // optional
        if (!input || !button) return;

        // resolve component type
        const urlType = (window.location.pathname.match(/\/product\/([^\/\?]+)/i) || [])[1] || '';
        const componentType =
            (root.dataset.component || '').trim() ||
            (ctx && (ctx.dataset.component || '').trim()) ||
            urlType;

        // endpoints
        const postEndpoint = root.dataset.endpoint || (componentType ? `/product/ai/${encodeURIComponent(componentType)}` : '');
        const getEndpoint  = (() => {
            const url = new URL(window.location.href);
            url.searchParams.set('ajax_ai', '1');
            return url.toString()
        })();

        // results target
        const targetId  = root.dataset.target || 'component-container';
        const container = document.getElementById(targetId);
        if (!container) {
            console.warn(`AI Finder: results container #${targetId} not found.`);
        }

        const setLoading = (loading) => {
            if (!button) return;
            if (loading) {
                button.disabled = true;
                if (!button.dataset.prevText) button.dataset.prevText = button.innerHTML;
                button.innerHTML = `
          <svg class="animate-spin h-4 w-4 mr-2 inline-block" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" fill="none" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
          </svg>
          Analyzing...
        `;
            } else {
                button.disabled = false;
                if (button.dataset.prevText) button.innerHTML = button.dataset.prevText;
            }
        };

        async function submit() {
            const query = input.value.trim();
            if (!query) {
                input.focus();
                input.classList.add('ring-2','ring-red-400');
                setTimeout(() => input.classList.remove('ring-2','ring-red-400'), 900);
                return;
            }
            if (!componentType || !postEndpoint) {
                console.error('AI Finder: missing componentType or endpoint.');
                return;
            }

            setLoading(true);
            try {
                // POST: store session / compute
                const postRes  = await fetch(postEndpoint, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
                    body: JSON.stringify({ user_requirement: query })
                });
                const postData = await postRes.json().catch(() => ({}));
                if (!postRes.ok || postData.error) {
                    if (container) {
                        container.innerHTML = `
              <div class="max-w-3xl mx-auto">
                <div class="p-4 rounded-xl border border-red-200 bg-red-50 text-red-700">
                  ${postData?.error || 'Something went wrong.'}
                </div>
              </div>`;
                    }
                    return;
                }

                // GET: fetch rendered Twig block (expects { ai_recommended: '<html>' })
                const getRes = await fetch(getEndpoint, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const data   = await getRes.json().catch(() => ({}));
                if (!getRes.ok || !('ai_recommended' in data)) {
                    if (container) {
                        container.innerHTML = `
              <div class="max-w-3xl mx-auto">
                <div class="p-4 rounded-xl border border-red-200 bg-red-50 text-red-700">
                  Could not load AI recommendations.
                </div>
              </div>`;
                    }
                    return;
                }

                if (container) {
                    container.innerHTML = data.ai_recommended || '';
                    // optional: scroll into view
                    if (data.ai_recommended) container.scrollIntoView({ behavior: 'smooth', block: 'start' });

                    // re-init score gradients if your cards use them
                    if (typeof window.initComponentScores === 'function') {

                        window.initComponentScores(container);
                    }

                    // Initialize carousel for the new unified recommendation section
                    if (typeof window.initRecommendationSection === 'function') {

                        window.initRecommendationSection(container);
                    }
                }
            } catch (e) {
                console.error(e);
                if (container) {
                    container.innerHTML = `
            <div class="max-w-3xl mx-auto">
              <div class="p-4 rounded-xl border border-red-200 bg-red-50 text-red-700">
                Network error. Please try again.
              </div>
            </div>`;
                }
            } finally {
                setLoading(false);
            }
        }

        button.addEventListener('click', submit);
        input.addEventListener('keydown', (e) => { if (e.key === 'Enter') submit(); });
    }

    function initAll(ctx = document) {
        ctx.querySelectorAll('[data-role="ai-finder"]').forEach(setupOne);
    }

    window.initAiFinder = initAll; // if you inject this block via AJAX later

    // ✅ safe-ready: works if the module loads after DOMContentLoaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => initAll(), { once: true });
    } else {
        initAll();
    }
})();