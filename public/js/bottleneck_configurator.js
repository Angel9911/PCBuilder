function updateBottleneckDisplay({ status, percentage }) {
    const container = document.getElementById("bottleneck-analysis");
    const box = document.getElementById("bottleneck-box");
    const bg = document.getElementById("bottleneck-bg");
    const heading = document.getElementById("bottleneck-heading");
    const label = document.getElementById("bottleneck-status-label");
    const statusText = document.getElementById("bottleneck-status");
    const bar = document.getElementById("bottleneck-bar");
    const percent = document.getElementById("bottleneck-percent");
    const message = document.getElementById("bottleneck-message");

    const styles = {
        well_matched: {
            border: "border-emerald-200",
            bg: "bg-emerald-100 bg-gradient-to-r from-emerald-200 to-emerald-100",
            text: "text-emerald-700",
            bar: "bg-emerald-500",
            label: "Well Matched",
            message: "These components work well together. You'll get optimal performance from this combination."
        },
        minor_bottleneck: {
            border: "border-amber-200",
            bg: "bg-amber-100 bg-gradient-to-r from-amber-200 to-amber-100",
            text: "text-amber-700",
            bar: "bg-amber-500",
            label: "Minor Bottleneck",
            message: "There's a slight performance limitation. You may want to consider upgrading either the CPU or GPU for better balance."
        },
        significant_bottleneck: {
            border: "border-red-200",
            bg: "bg-red-100 bg-gradient-to-r from-red-200 to-red-100",
            text: "text-red-700",
            bar: "bg-red-500",
            label: "Significant Bottleneck",
            message: "There's a significant bottleneck. One component will limit the performance of the other considerably. Consider a more balanced combination."
        }
    };

    const s = styles[status];
    if (!s) {
        console.warn("Invalid status received:", status);
        return;
    }

    // Reset all dynamic classes first
    box.className = `bg-white shadow overflow-hidden sm:rounded-lg border-l-4 transition-colors duration-500 ${s.border}`;
    bg.className = `p-6 transition-colors duration-500 ${s.bg}`;
    heading.className = `text-lg font-semibold transition-colors duration-500 ${s.text}`;
    label.className = `text-sm font-medium transition-colors duration-500 ${s.text}`;
    statusText.className = `text-sm font-bold transition-colors duration-500 ${s.text}`;
    bar.className = `h-full transition-all duration-1000 ease-out ${s.bar}`;
    percent.className = `text-xs font-semibold transition-colors duration-500 ${s.text}`;

    // Set values
    statusText.textContent = s.label;
    percent.textContent = `${percentage}% bottleneck`;
    message.textContent = s.message;
    bar.style.width = `${percentage}%`;

    container.classList.remove("hidden");
}

function triggerBottleneckAICheck() {
    const cpuSelect = document.querySelector('.custom-select[data-component-id="cpu"]');
    const gpuSelect = document.querySelector('.custom-select[data-component-id="gpu"]');

    const cpuId = cpuSelect?.dataset.selectedValue;
    const gpuId = gpuSelect?.dataset.selectedValue;

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
            /*const translatedStatus = {
                "well-matched": "well_matched",
                "minor-bottleneck": "minor-bottleneck",
                "significant-bottleneck": "significant-bottleneck"
            }[data.bottleneck_status];*/
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
        })
        .catch(err => {
            console.error("❌ Bottleneck API error:", err);
            updateBottleneckDisplay({ status: "not_ready", percentage: 0 });
        });
}

window.updateBottleneckDisplay = updateBottleneckDisplay;
window.triggerBottleneckAICheck = triggerBottleneckAICheck;