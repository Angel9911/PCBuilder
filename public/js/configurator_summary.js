const buildSummaryState = {
    selectedCount: 0,
    totalCount: 7, // TODO: maybe should be 8 or 9(pc case and monitor)
    lowestPrice: 0,
    highestPrice: 0,
    powerWattage: 0
};

// Track use case and performance answers
const initialAnswers = {
    useCases: new Set(),
    performance: null,
    specific_requirements: null
};

// Classes for use-case buttons (multi-select)
const useCaseActive = ['bg-gradient-to-r', 'from-indigo-500', 'to-cyan-500', 'text-white'];
const useCaseInactive = ['bg-gray-100', 'text-gray-700', 'hover:bg-gray-200'];

// Classes for performance buttons (radio group)
const perfActive = ['bg-gradient-to-r', 'from-indigo-500', 'to-cyan-500', 'text-white'];
const perfInactive = ['bg-gray-100', 'text-gray-700', 'hover:bg-gray-200'];

// Multi-select logic (checkbox-style)
document.querySelectorAll('.use-case-btn').forEach(button => {
    button.addEventListener('click', () => {
        const isActive = button.classList.contains('bg-gradient-to-r');

        if (isActive) {
            button.classList.remove(...useCaseActive);
            button.classList.add(...useCaseInactive);
        } else {
            button.classList.remove(...useCaseInactive);
            button.classList.add(...useCaseActive);
        }
    });
});

// Radio-style logic (single select for performance)
const perfButtons = document.querySelectorAll('.performance-btn');

perfButtons.forEach(button => {
    button.addEventListener('click', () => {
        perfButtons.forEach(b => {
            b.classList.remove(...perfActive);
            b.classList.add(...perfInactive);
        });

        button.classList.remove(...perfInactive);
        button.classList.add(...perfActive);
    });
});

function setupInitialQuestionListeners() {
    const reviewBtn = document.getElementById("submit-review");

    // Handle use case selection (multi-select)
    document.querySelectorAll('[data-use-case]').forEach(button => {
        button.addEventListener("click", () => {
            const key = button.dataset.useCase;

            if (initialAnswers.useCases.has(key)) {
                initialAnswers.useCases.delete(key);
                button.classList.remove("bg-blue-100", "border-2", "border-blue-500");
            } else {
                initialAnswers.useCases.add(key);
                button.classList.add("bg-blue-100", "border-2", "border-blue-500");
            }

            checkInitialAnswersComplete();
        });
    });

    // Handle performance selection (single-select)
    document.querySelectorAll('[data-performance]').forEach(button => {
        button.addEventListener("click", () => {
            document.querySelectorAll('[data-performance]').forEach(b => {
                b.classList.remove("bg-blue-100", "border-2", "border-blue-500");
            });

            button.classList.add("bg-blue-100", "border-2", "border-blue-500");
            initialAnswers.performance = button.dataset.performance;

            checkInitialAnswersComplete();
        });
    });

    function checkInitialAnswersComplete() {
        // If user selected at least 1 use case AND a performance level
        if (initialAnswers.useCases.size > 0 && initialAnswers.performance) {
            reviewBtn.disabled = false;
        } else {
            reviewBtn.disabled = true;
        }
    }
}

document.getElementById("submit-review").addEventListener("click", async () => {
    const useCases = Array.from(initialAnswers.useCases);
    const performance = initialAnswers.performance;
    const specificRequirements = document.getElementById("specific-requirements").value.trim();

    // You should already have this from your global scope or inject it dynamically
    const selectedComponents = window.selectedComponents || [];

    const payload = {
        answers: {
            useCases: useCases,
            performance: performance,
            specifications: specificRequirements
        },
        selectedComponents: selectedComponents
    };

    try {
        showSpinner();
        //console.log(userAnswers)
        fetch("/configurator/ai/build", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify(payload)
        })
            .then(response => {

                if (response.ok) {
                    // Redirect to PC build configuration page
                    window.location.href = '/configurator/ai/summary';
                } else {
                    console.error('Error processing request');
                }
            })
            .catch(error => console.error("Error:", error))
            .finally(() => {

                hideSpinner()// hide spinner
            });

    } catch (error) {
        console.error("Network or server error:", error);
        alert("Network error. Please try again.");
    }
});

function updateBuildSummaryState({ selectedCount, lowestPrice, highestPrice, powerWattage }) {

    buildSummaryState.selectedCount = selectedCount;

    if (lowestPrice !== undefined) {

        buildSummaryState.lowestPrice = parseFloat(lowestPrice) || 0;
    }

    if (highestPrice !== undefined) {

        buildSummaryState.highestPrice = parseFloat(highestPrice) || 0;
    }

    if (powerWattage !== undefined) {

        buildSummaryState.powerWattage = parseInt(powerWattage) || 0;
    }


    renderBuildSummary();
}

function calculateTotalRangePrices({componentPriceRanges}) {
    let totalLowest = 0;
    let totalHighest = 0;

    componentPriceRanges.forEach(({ lowest, highest }) => {
        totalLowest += parseFloat(lowest);
        totalHighest += parseFloat(highest);
    });

    return {
        totalLowest: totalLowest,
        totalHighest: totalHighest
    };
}

function calculatePowerWattage({selectedComponentsPowerWattage}) {

    let totalPowerWattage = 0;

    selectedComponentsPowerWattage.forEach(({powerWattage}) => {

        totalPowerWattage += powerWattage;
    })

    return {
        totalPowerWattage: totalPowerWattage
    }
}

function renderBuildSummary() {
    const {
        selectedCount,
        totalCount,
        lowestPrice,
        highestPrice,
        powerWattage
    } = buildSummaryState;

    const countEl = document.getElementById("component-count");
    const lowestPriceEl = document.getElementById("lowest-price");
    const highestPriceEl = document.getElementById("highest-price");
    const powerWattageEl = document.getElementById("power-wattage");
    const progressText = document.getElementById("build-progress-text");
    const progressBar = document.getElementById("build-progress-bar");
    const statusEl = document.getElementById("build-status");
    const reviewBtn = document.getElementById("review-build-button");

    if (countEl) {
        countEl.textContent = `${selectedCount} of ${totalCount}`;
    }

    if (lowestPriceEl) {
        lowestPriceEl.textContent = `$${lowestPrice.toFixed(2)}`;
    }
    if (highestPriceEl) {
        highestPriceEl.textContent = `$${highestPrice.toFixed(2)}`;
    }
    if(powerWattageEl){
        powerWattageEl.textContent = `${powerWattage}W`
    }

    const progressPercent = Math.round((selectedCount / totalCount) * 100);

    if (progressText) {

        progressText.textContent = `${progressPercent}%`;
    }

    if (progressBar) {

        progressBar.style.width = `${progressPercent}%`;
    }

    const isComplete = selectedCount === totalCount;

    if (statusEl) {
        statusEl.textContent = isComplete ? "Ready" : "In Progress";
        statusEl.className = isComplete
            ? "px-3 py-1 bg-green-50 text-green-700 rounded-full text-sm font-medium"
            : "px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-sm font-medium";
    }

    if (reviewBtn) {
        reviewBtn.disabled = !isComplete;
    }
}

function initMobileSummaryPanel() {
    // These elements only exist on the mobile template; on desktop this exits immediately.
    const fab     = document.getElementById('mobile-summary-fab');
    const panel   = document.getElementById('mobile-summary-panel');
    const overlay = document.getElementById('mobile-summary-overlay');
    const close   = document.getElementById('mobile-summary-close');

    if (!fab || !panel || !overlay) return; // desktop or template not loaded

    const openPanel = () => {
        panel.classList.remove('translate-x-full');  // slide in
        overlay.classList.remove('hidden');          // show dim/blur
        document.body.style.overflow = 'hidden';     // lock scroll
    };

    const closePanel = () => {
        panel.classList.add('translate-x-full');     // slide out
        overlay.classList.add('hidden');             // hide dim/blur
        document.body.style.overflow = '';           // restore scroll
    };

    // Prevent double-binding if this script executes more than once (e.g., hot reload)
    if (!fab.dataset.bound) {
        fab.addEventListener('click', openPanel);
        overlay.addEventListener('click', closePanel);
        close?.addEventListener('click', closePanel);
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !overlay.classList.contains('hidden')) closePanel();
        });
        fab.dataset.bound = '1';
    }
}

document.getElementById("review-build-button").addEventListener("click", () => {
    document.getElementById("reviewModal").classList.remove("hidden");
});

document.getElementById("closeReviewModal").addEventListener("click", () => {
    document.getElementById("reviewModal").classList.add("hidden");
});

document.getElementById("cancelReviewModal").addEventListener("click", () => {
    document.getElementById("reviewModal").classList.add("hidden");
});

window.updateBuildSummaryState = updateBuildSummaryState;
window.calculateTotalRangePrices = calculateTotalRangePrices;
window.setupInitialQuestionListeners = setupInitialQuestionListeners;