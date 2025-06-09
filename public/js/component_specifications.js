const iconMap = {
        "Socket": "cpu",
        "Memory Type": "memory-stick",
        "Power Wattage": "zap",
        "TDP": "zap",
        "Max Frequency": "clock",
        "Chipset": "git-branch",
        "Capacity Gb": "database",
        "Speed Mhz": "gauge",
        "Type": "memory-stick",
        // Fallback will be 'circle'
};

document.addEventListener("DOMContentLoaded", () => {

    document.querySelectorAll('.spec-box').forEach(box => {
        const label = box.getAttribute('data-spec-label');
        const iconName = iconMap[label] || "circle";

        const iconElement = box.querySelector('[data-lucide]');
        if (iconElement) {
            iconElement.setAttribute('data-lucide', iconName);
        }
    });

   if (typeof lucide !== "undefined" && lucide.createIcons) {
        lucide.createIcons();
    }

    if (typeof componentOffers !== "undefined" && componentOffers && Object.keys(componentOffers).length > 0) {

        const offersContainer = document.getElementById("vendor-offers");

        const offerTemplate = document.getElementById("offer-template");

        renderOffers(componentOffers, offerTemplate, offersContainer);
    }
});