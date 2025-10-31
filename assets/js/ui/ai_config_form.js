import {createIcons, icons} from "lucide";

function initAIConfigForm() {
    // Budget section logic
    const budgetOptions = document.querySelectorAll('.budget-option');
    budgetOptions.forEach(option => {
        option.addEventListener('click', () => {
            budgetOptions.forEach(o => resetOption(o, 'green'));
            activateOption(option, 'green');
        });
    });

    // Primary Use section logic
    const useOptions = document.querySelectorAll('.use-option');
    useOptions.forEach(option => {
        option.addEventListener('click', () => {
            useOptions.forEach(o => resetOption(o, 'blue'));
            activateOption(option, 'blue');
        });
    });
}

function initAIConfigSubmit() {
    const submitBtn = document.querySelector('#ai-build-submit');

    if (!submitBtn) return;
    const setLoading = (loading) => {
        if (loading) {
            submitBtn.disabled = true;
            if (!submitBtn.dataset.prevText) submitBtn.dataset.prevText = submitBtn.innerHTML;
            submitBtn.innerHTML = `
            <svg class="animate-spin h-4 w-4 mr-2 inline-block text-white" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" fill="none" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
            Analyzing...
        `;
        } else {
            submitBtn.disabled = false;
            if (submitBtn.dataset.prevText) submitBtn.innerHTML = submitBtn.dataset.prevText;
        }
    };

    submitBtn.addEventListener('click', async () => {
        // 1️⃣ Collect selected values
        const selectedBudget = document.querySelector('.budget-option.border-green-500');
        const selectedUses = document.querySelectorAll('.use-option.border-blue-500');
        const textarea = document.querySelector('#specific-requirements');

        const budgetValue = selectedBudget ? selectedBudget.dataset.value : null;
        const primaryUses = Array.from(selectedUses).map(el => el.dataset.value);
        const specificReq = textarea ? textarea.value.trim() : '';

        if (!budgetValue || primaryUses.length === 0) {
            alert('Please select both a budget priority and at least one primary use.');
            return;
        }

        // 2️⃣ Build payload matching Symfony backend
        const payload = {
            user_requirements: {
                budget_focused: formatBudget(budgetValue),
                primary_use: formatUses(primaryUses),
                specific_requirement: specificReq
            }
        };

        console.log('🧠 Sending AI Build Payload:', payload);

        setLoading(true);

        try {
            // 3️⃣ POST request to your Symfony endpoint
            const res = await fetch('/completed/ai/build', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(payload)
            });

            if (!res.ok) throw new Error(`Request failed with ${res.status}`);
            const data = await res.json();

            console.log('✅ AI Recommended Configurations:', data);

            const container = document.getElementById('ai-recommendations-container');
            if (!container) {
                console.warn('AI container not found!');
                return;
            }

            // Insert rendered Twig HTML into the container
            container.innerHTML = data.config_html || '';

            // Optionally scroll into view
            container.scrollIntoView({behavior: 'smooth', block: 'start'});

            // Initialize icons, carousel, etc.
            if (typeof window.initRecommendationSection === 'function') {

                window.initRecommendationSection(container);
            }
            if (typeof window.createIcons === 'function') {

                window.createIcons({icons});
            }

        } catch (err) {

            console.error('❌ Error generating AI build:', err);
            alert('Something went wrong while generating recommendations.');
        } finally {

            setLoading(false);
        }
    });
}

// Utility formatters (to match your PHP-side expected values)
function formatBudget(value) {
    switch (value) {
        case 'balanced': return 'Balanced';
        case 'budget': return 'Budget Focused';
        case 'performance': return 'Performance Focused';
        default: return value;
    }
}

function formatUses(values) {
    return values.map(v => {
        switch (v) {
            case 'gaming': return 'Gaming';
            case 'work': return 'Work Productivity';
            case 'creation': return 'Content Creation';
            case 'general': return 'General Use';
            default: return v;
        }
    }).join(', ');
}

function resetOption(option, color) {
    option.classList.remove(`border-${color}-500`, `bg-${color}-50`);
    option.classList.add('border-gray-200');
    const iconWrapper = option.querySelector('.p-2');
    const checkmark = option.querySelector('.checkmark');

    if (iconWrapper) {
        iconWrapper.classList.remove(`bg-${color}-100`);
        iconWrapper.classList.add('bg-gray-50');
    }
    if (checkmark) {
        checkmark.classList.remove('flex');
        checkmark.classList.add('hidden');
        checkmark.innerHTML = '';
    }
}

function activateOption(option, color) {
    option.classList.remove('border-gray-200');
    option.classList.add(`border-${color}-500`, `bg-${color}-50`);
    const iconWrapper = option.querySelector('.p-2');
    const checkmark = option.querySelector('.checkmark');

    if (iconWrapper) {
        iconWrapper.classList.remove('bg-gray-50');
        iconWrapper.classList.add(`bg-${color}-100`);
    }
    if (checkmark) {
        checkmark.classList.remove('hidden');
        checkmark.classList.add('flex', `bg-${color}-500`);
        checkmark.innerHTML = `<i data-lucide="check" class="w-3 h-3 text-white"></i>`;
        // ✅ run Lucide after DOM update
        queueMicrotask(() => createIcons({ icons }));
    }
}

// ✅ Safe init wrapper (works for static or dynamically injected HTML)
if (document.readyState === 'loading') {

    document.addEventListener('DOMContentLoaded', () => {

        initAIConfigForm();
        initAIConfigSubmit();
    });
} else {

    initAIConfigForm();
    initAIConfigSubmit();
}