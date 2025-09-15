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

//let selectedImage = "{{ componentSpecifications.component_images.main_image_url|e('js') }}";
function init() {
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
}

// safe-ready wrapper (works for both static and dynamically-imported modules)
if (document.readyState === 'loading') {

    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}


function changeMainImage(url) {
    selectedImage = url;
    document.getElementById('mainImage').src = url;
}

function openImageModal(url) {
    document.getElementById('modalImage').src = url;
    document.getElementById('imageModal').classList.remove('hidden');
}

function closeImageModal() {
    document.getElementById('imageModal').classList.add('hidden');
}

window.changeMainImage = changeMainImage;
window.openImageModal = openImageModal;
window.closeImageModal = closeImageModal;