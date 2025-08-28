document.addEventListener('DOMContentLoaded', () => {
    const input     = document.getElementById('ai-periphery-input');
    const button    = document.getElementById('ai-periphery-button');
    const container = document.getElementById('ai-reco-container');
    const ctx       = document.getElementById('ai-context'); // optional

    // Must have these three
    if (!input || !button || !container) return;

    // Try to resolve the component type from several places
    const urlType = (() => {
        // Expecting /product/{type}… ; adjust if your routing differs
        const m = window.location.pathname.match(/\/product\/([^\/\?]+)/i);
        return m ? decodeURIComponent(m[1]) : '';
    })();

    const componentType =
        (ctx && (ctx.dataset.component || '').trim()) ||
        (button.dataset.component || '').trim() ||
        urlType;

    if (!componentType) {
        console.warn('AI Finder: componentType not found. Set #ai-context[data-component] or button[data-component].');
    }

    // Build endpoints
    const postEndpoint =
        (ctx && ctx.dataset.endpoint) ||
        `/product/ai/${encodeURIComponent(componentType)}`;

    const getEndpoint = (() => {
        const url = new URL(window.location.href);
        url.searchParams.set('ajax_ai', '1');
        return url.toString();
    })();

    const setLoading = (loading) => {
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

    async function fetchAi() {
        const query = input.value.trim();
        if (!query) {
            input.focus();
            input.classList.add('ring-2','ring-red-400');
            setTimeout(() => input.classList.remove('ring-2','ring-red-400'), 900);
            return;
        }
        if (!componentType) {
            console.error('AI Finder: componentType is empty; cannot build endpoint.');
            return;
        }

        setLoading(true);
        try {
            // 1) POST -> store AI selection in session
            const postRes = await fetch(postEndpoint, {
                method: 'POST',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ user_requirement: query })
            });
            const postData = await postRes.json().catch(() => ({}));

            if (!postRes.ok || postData.error) {
                container.innerHTML = `
          <div class="max-w-3xl mx-auto">
            <div class="p-4 rounded-xl border border-red-200 bg-red-50 text-red-700">
              ${postData?.error || 'Something went wrong.'}
            </div>
          </div>`;
                return;
            }

            // 2) GET -> ask the current page for the AI block html
            const getRes = await fetch(getEndpoint, { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
            const data = await getRes.json().catch(() => ({}));

            //console.log(getRes);
            console.log(data);

            if (!getRes.ok || !('ai_recommended' in data)) {
                container.innerHTML = `
          <div class="max-w-3xl mx-auto">
            <div class="p-4 rounded-xl border border-red-200 bg-red-50 text-red-700">
              Could not load AI recommendations.
            </div>
          </div>`;
                return;
            }

            // 3) Inject the server-rendered Twig HTML
            container.innerHTML = data.ai_recommended || '';
            if (data.ai_recommended) {
                container.scrollIntoView({ behavior:'smooth', block:'start' });
            }
        } catch (e) {
            console.error(e);
            container.innerHTML = `
        <div class="max-w-3xl mx-auto">
          <div class="p-4 rounded-xl border border-red-200 bg-red-50 text-red-700">
            Network error. Please try again.
          </div>
        </div>`;
        } finally {
            setLoading(false);
        }
    }

    button.addEventListener('click', fetchAi);
    input.addEventListener('keydown', (e) => { if (e.key === 'Enter') fetchAi(); });
});