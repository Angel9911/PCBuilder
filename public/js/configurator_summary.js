const buildSummaryState = {
    selectedCount: 0,
    totalCount: 6, // TODO: maybe should be 8 or 9(pc case and monitor)
    lowestPrice: 0,
    highestPrice: 0,
    powerWattage: 0
};

// Track use case and performance answers
const initialAnswers = {
    useCases: new Set(),
    performance: null
};

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