const btnBottleneck = document.getElementById("btn-bottleneck");
const btnFps = document.getElementById("btn-fps");
const toggleBtn = document.getElementById("toggle-performance");
const chevronIcon = document.getElementById("chevron-icon");
const section = document.getElementById("performance-analysis-content");

let isVisible = true;

function updateBottleneckSummaryState(cpuSelect, gpuSelect, ramSelect){
    // Used for Performance analysis section
    const cpuSelectedSummary = document.getElementById("cpu-selected-pill");
    const gpuSelectedSummary = document.getElementById("gpu-selected-pill");
    const ramSelectedSummary = document.getElementById("ram-selected-pill");

    const cpuTextSummary = document.getElementById("cpu-selected-text");
    const gpuTextSummary = document.getElementById("gpu-selected-text");
    const ramTextSummary = document.getElementById("ram-selected-text");

    // CPU
    if (cpuSelect) {

        cpuSelectedSummary.className = "flex items-center gap-1.5 px-2 py-1 rounded-full text-xs bg-emerald-100 text-emerald-700 transition-colors duration-300";
        cpuTextSummary.textContent = "CPU ✓";
    } else {

        cpuSelectedSummary.className = "flex items-center gap-1.5 px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-500 transition-colors duration-300";
        cpuTextSummary.textContent = "CPU";
    }

    // GPU
    if (gpuSelect) {

        gpuSelectedSummary.className = "flex items-center gap-1.5 px-2 py-1 rounded-full text-xs bg-emerald-100 text-emerald-700 transition-colors duration-300";
        gpuTextSummary.textContent = "GPU ✓";
    } else {

        gpuSelectedSummary.className = "flex items-center gap-1.5 px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-500 transition-colors duration-300";
        gpuTextSummary.textContent = "GPU";
    }

    // RAM
    if (ramSelect) {

        ramSelectedSummary.className = "flex items-center gap-1.5 px-2 py-1 rounded-full text-xs bg-emerald-100 text-emerald-700 transition-colors duration-300";
        ramTextSummary.textContent = "RAM ✓";
    } else {

        ramSelectedSummary.className = "flex items-center gap-1.5 px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-500 transition-colors duration-300";
        ramTextSummary.textContent = "RAM";
    }

    // end code for performance analysis section
}
// This function updates both Bottleneck and FPS sections based on selected components
function updateSectionView(cpuComp, gpuComp, ramComp = null) {
    const sectionBottleneck = document.getElementById("section-bottleneck");
    const initialBottleneckView = document.getElementById("bottleneck-placeholder");
    const sectionFps = document.getElementById("section-fps");
    const initialFpsView = document.getElementById("fps-placeholder");

    const isBottleneckReady = !!(cpuComp && gpuComp);
    const isFpsReady = !!(cpuComp && gpuComp && ramComp);

    const isBottleneckTabActive = document.getElementById("btn-bottleneck").classList.contains("bg-white");
    const isFpsTabActive = document.getElementById("btn-fps").classList.contains("bg-white");

    // Always hide everything before logic applies
    sectionBottleneck.classList.add("hidden");
    initialBottleneckView.classList.add("hidden");
    sectionFps.classList.add("hidden");
    initialFpsView.classList.add("hidden");

    // Bottleneck Section
    if (isBottleneckTabActive) {
        if (isBottleneckReady) {
            sectionBottleneck.classList.remove("hidden");
        } else {
            initialBottleneckView.classList.remove("hidden");
        }
    } else  // FPS Section
    if (isFpsTabActive) {
        if (isFpsReady) {
            sectionFps.classList.remove("hidden");
        } else {
            initialFpsView.classList.remove("hidden");
        }
    }
}

// Modify tab switching handlers to call updateSectionView with the latest selections
btnBottleneck.addEventListener("click", () => {
    btnBottleneck.classList.add("bg-white", "text-indigo-700", "shadow-sm");
    btnFps.classList.remove("bg-white", "text-indigo-700", "shadow-sm");
    btnFps.classList.add("text-slate-600");

    const cpuId = document.querySelector('.custom-select[data-component-id="cpu"]')?.dataset.selectedValue;
    const gpuId = document.querySelector('.custom-select[data-component-id="gpu"]')?.dataset.selectedValue;
    const ramId = document.querySelector('.custom-select[data-component-id="ram"]')?.dataset.selectedValue;

    setTimeout(() => updateSectionView(cpuId, gpuId, ramId), 0);
});

btnFps.addEventListener("click", () => {
    btnFps.classList.add("bg-white", "text-indigo-700", "shadow-sm");
    btnBottleneck.classList.remove("bg-white", "text-indigo-700", "shadow-sm");
    btnBottleneck.classList.add("text-slate-600");

    const cpuId = document.querySelector('.custom-select[data-component-id="cpu"]')?.dataset.selectedValue;
    const gpuId = document.querySelector('.custom-select[data-component-id="gpu"]')?.dataset.selectedValue;
    const ramId = document.querySelector('.custom-select[data-component-id="ram"]')?.dataset.selectedValue;

    setTimeout(() => updateSectionView(cpuId, gpuId, ramId), 0);
});

toggleBtn.addEventListener("click", () => {
    isVisible = !isVisible;

    if (isVisible) {
        section.classList.remove("hidden");
        chevronIcon.classList.remove("rotate-180");
    } else {
        section.classList.add("hidden");
        chevronIcon.classList.add("rotate-180");
    }
});

window.updateBottleneckSummaryState = updateBottleneckSummaryState;