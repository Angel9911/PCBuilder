function renderOffers(offers, offerTemplate, offersContainer) {
    if (!Array.isArray(offers) || offers.length === 0 || !offerTemplate || !offersContainer) {
        console.warn("renderOffers: missing or invalid input");
        return;
    }

    offers.forEach(offer => {
        if (Object.keys(offer).length === 0) return;

        const clone = offerTemplate.content.cloneNode(true);

        const logo = clone.querySelector(".vendor-logo");
        if (logo) {
            logo.src = offer.logo;
            logo.alt = offer.vendor_name;
        }

        clone.querySelector(".vendor_name").textContent = offer.vendor_name;
        clone.querySelector(".price").textContent = offer.price;
        clone.querySelector(".shipping-cost").textContent = offer.shipping_cost;

        const stockStatus = clone.querySelector(".stock-status");
        if (stockStatus) {
            stockStatus.className = "stock-status inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium";

            if (offer.stock_status === "In Stock") {
                stockStatus.classList.add("bg-green-100", "text-green-800");
                clone.querySelector(".stock-text").textContent = "In Stock";
            } else {
                stockStatus.classList.add("bg-red-100", "text-red-800");
                clone.querySelector(".stock-text").textContent = "Out of Stock";
            }
        }

        const link = clone.querySelector(".view-offer");
        if (link) {
            if (offer.stock_status === "Out of Stock") {
                const btn = document.createElement("button");
                btn.className = "inline-flex items-center px-4 py-2 border border-gray-200 text-sm font-medium rounded-md text-gray-400 bg-gray-50 cursor-not-allowed";
                btn.innerHTML = 'Out of Stock <i data-lucide="alert-circle" class="ml-2 h-4 w-4"></i>';
                link.parentNode.replaceChild(btn, link);
            } else {
                link.href = offer.link;
                link.classList.remove("cursor-not-allowed", "text-gray-400", "bg-gray-50");
            }
        }

        offersContainer.appendChild(clone);
    });
}

window.renderOffers = renderOffers;
