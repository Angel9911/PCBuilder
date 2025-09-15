import {
    updateSectionView
} from 'app/performance_analysis';
function updateBottleneckDisplay({ status, percentage }) {
    const section = document.getElementById("section-bottleneck");
    const box = document.getElementById("bottleneck-box");
    const label = document.getElementById("bottleneck-label");
    const percentWrapper = document.getElementById("bottleneck-percent-wrapper");
    const percentInline = document.getElementById("bottleneck-percent-inline");
    const bottleneckLevel = document.getElementById("bottleneck-level");
    const bar = document.getElementById("bottleneck-bar");
    const iconContainer = document.getElementById("bottleneck-icon");
    const message = document.getElementById("bottleneck-message");

    const cpuLabel = document.getElementById("bottleneck-cpu");
    const gpuLabel = document.getElementById("bottleneck-gpu");

    const styles = {
        well_matched: {
            border: "border-emerald-300/50",
            bg: "bg-gradient-to-br from-emerald-500/20 via-green-500/10 to-emerald-500/20",
            text: "text-emerald-800",
            bar: "bg-gradient-to-r from-emerald-400 to-green-500",
            label: "Excellent Match",
            message: "Perfect match! These components work excellently together with minimal bottlenecking.",
            bgBadge: "bg-emerald-100/80",
            textBadge: "text-emerald-800",
            icon: `<svg xmlns="http://www.w3.org/2000/svg" class="lucide lucide-check-circle h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>`
        },
        minor_bottleneck: {
            border: "border-amber-200",
            bg: "bg-gradient-to-br from-amber-100 to-yellow-100",
            text: "text-amber-700",
            bar: "bg-gradient-to-r from-amber-400 to-yellow-500",
            label: "Minor Bottleneck",
            bgBadge: "bg-amber-100/80",
            textBadge: "text-amber-800",
            message: "There's a slight performance limitation. You may want to consider upgrading either the CPU or GPU for better balance.",
            icon: `<svg xmlns="http://www.w3.org/2000/svg" class="lucide lucide-alert-circle h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`
        },
        significant_bottleneck: {
            border: "border-red-300/50",
            bg: "bg-gradient-to-br from-red-100 to-red-200",
            text: "text-red-700",
            bar: "bg-gradient-to-r from-red-400 to-red-600",
            label: "Significant Bottleneck",
            bgBadge: "bg-red-100/80",
            textBadge: "text-red-800",
            message: "There's a significant bottleneck. One component will limit the performance of the other considerably. Consider a more balanced combination.",
            icon: `<svg xmlns="http://www.w3.org/2000/svg" class="lucide lucide-alert-octagon h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="7.86 2 16.14 2 22 7.86 22 16.14 16.14 22 7.86 22 2 16.14 2 7.86 7.86 2"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`
        }
    };

    const s = styles[status];
    if (!s) {
        console.warn("Invalid bottleneck status:", status);
        section.classList.add("hidden");
        return;
    }

    // Apply dynamic classes
    box.className = `relative rounded-xl border p-4 sm:p-6 shadow-lg backdrop-blur-sm transition-all duration-500 ${s.border} ${s.bg}`;
    label.className = `text-sm sm:text-lg font-bold ${s.text}`;
    label.textContent = s.label;

    bottleneckLevel.className = `text-sm sm:text-lg font-bold ${s.text}`;

    if (percentInline && percentWrapper) {
        percentInline.textContent = `${percentage}%`;

        // Reset old classes
        percentWrapper.className = "px-2 sm:px-4 py-1 sm:py-2 rounded-full backdrop-blur-sm";
        percentInline.className = "text-sm sm:text-lg font-bold";

        // Apply new colors
        percentWrapper.classList.add(s.bgBadge);
        percentInline.classList.add(s.textBadge);
    }

    bar.className = `h-full bg-gradient-to-r from-emerald-400 to-green-500 transition-all duration-1000 ${s.bar}`;

    bar.style.width = `${percentage}%`;

    iconContainer.innerHTML = s.icon;
    iconContainer.className = `p-1.5 sm:p-2.5 rounded-xl bg-white/20 backdrop-blur-sm ring-1 sm:ring-2 ${s.text}`;

    message.textContent = s.message;

    // Inject selected CPU and GPU names
    const cpuId = document.querySelector('.custom-select[data-component-id="cpu"]')?.dataset.selectedValue;
    const gpuId = document.querySelector('.custom-select[data-component-id="gpu"]')?.dataset.selectedValue;
    const cpuName = allOptions["cpu"][cpuId];
    const gpuName = allOptions["gpu"][gpuId];

    cpuLabel.textContent = cpuName || "Unknown CPU";
    gpuLabel.textContent = gpuName || "Unknown GPU";

    section.classList.remove("hidden"); // Make sure it's visible FIRST
}

export function triggerBottleneckAICheck() {
    const cpuSelect = document.querySelector('.custom-select[data-component-id="cpu"]');
    const gpuSelect = document.querySelector('.custom-select[data-component-id="gpu"]');
    const ramSelect = document.querySelector('.custom-select[data-component-id="ram"]');


    const cpuId = cpuSelect?.dataset.selectedValue;
    const gpuId = gpuSelect?.dataset.selectedValue;
    const ramId = ramSelect?.dataset.selectedValue;


    // Only trigger if both are selected
    if (!cpuId || !gpuId) {
        updateBottleneckDisplay({ status: "not_ready", percentage: 0 });
        return;
    }

    const cpuName = allOptions["cpu"][cpuId];
    const gpuName = allOptions["gpu"][gpuId];

    const payload = JSON.stringify({
        cpu: cpuName,
        gpu: gpuName
    });

    fetch('/configurator/bottleneck', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: payload
    })
        .then(res => res.json())
        .then(data => {
            const normalizeStatus = (status) => {
                return String(status)
                    .toLowerCase()
                    .replace(/\s|-/g, "_"); // converts 'minor-bottleneck' or 'minor bottleneck' to 'minor_bottleneck'
            };

            const normalizedStatus = normalizeStatus(data.bottleneck_status);

            const validStatuses = ["well_matched", "minor_bottleneck", "significant_bottleneck"];

            if (!validStatuses.includes(normalizedStatus)) {
                console.warn("Unknown bottleneck status:", data.bottleneck_status);
                return;
            }
            updateBottleneckDisplay({
                status: normalizedStatus,
                percentage: data.bottleneck_percentage
            });

            updateSectionView(cpuId, gpuId, ramId);

        })
        .catch(err => {
            console.error("❌ Bottleneck API error:", err);
            updateBottleneckDisplay({ status: "not_ready", percentage: 0 });
        });
}

export function triggerFpsCalculation(){
    const cpuSelect = document.querySelector('.custom-select[data-component-id="cpu"]');
    const gpuSelect = document.querySelector('.custom-select[data-component-id="gpu"]');
    const ramSelect = document.querySelector('.custom-select[data-component-id="ram"]');

    const cpuId = cpuSelect?.dataset.selectedValue;
    const gpuId = gpuSelect?.dataset.selectedValue;
    const ramId = ramSelect?.dataset.selectedValue;


    updateSectionView(cpuId, gpuId, ramId);
}