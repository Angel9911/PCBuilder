document.addEventListener("DOMContentLoaded", function () {
    //setup early
    document.querySelectorAll(".custom-select").forEach(selectBox => {
        let selected = selectBox.querySelector(".select-selected");
        let items = selectBox.querySelectorAll(".select-item");

        selected.addEventListener("click", () => {

            selectBox.querySelector(".select-items").classList.toggle("select-hide");
        });

        items.forEach(item => {
            item.addEventListener("click", () => {
                selected.textContent = item.textContent;
                selectBox.dataset.selectedValue = item.dataset.value;

                selectBox.querySelector(".select-items").classList.add("select-hide");

                // ✅ REMOVE ERROR MESSAGE AND STYLING IF PRESENT
                const componentType = selectBox.getAttribute("data-component-id");
                const errorMsg = document.getElementById(`${componentType}-error`);
                if (errorMsg) errorMsg.remove();

                selectBox.classList.remove("error"); // remove red border class

                // ✅ Trigger change event
                selectBox.dispatchEvent(new CustomEvent("change", {
                    detail: {
                        value: item.dataset.value,
                        name: item.textContent
                    }
                }));
            });
        });
    });


    let isCompletedConfiguration = false;
    // Initialize the combobox fields if there are given AI recommendation components or given completed configuration
    if (typeof pcConfiguration !== "undefined" && pcConfiguration && Object.keys(pcConfiguration).length > 0) {
        isCompletedConfiguration = true;
        Object.keys(pcConfiguration).forEach(component => {
            let componentData = pcConfiguration[component];

            let selectBox = document.querySelector(`.custom-select[data-component-id="${component.toLowerCase()}"]`);


            if (selectBox) {

                let items = selectBox.querySelectorAll(".select-item");
                let componentOption = Array.from(items).find(item => item.textContent.trim() === componentData.name);


                if (componentOption) {
                    selectBox.querySelector(".select-selected").textContent = componentOption.textContent;
                    selectBox.dataset.selectedValue = componentOption.dataset.value;

                    // Trigger custom change event
                    selectBox.dispatchEvent(new CustomEvent("change", {
                        detail: {
                            value: componentOption.dataset.value,
                            name: componentOption.textContent
                        }
                    }));
                }
            }
        });
    } else {
        console.warn("the object is not defined or empty");
    }

    let offerTemplate = document.querySelector("#offer-template");

    if (!offerTemplate) {

        showSpinner(); // Show spinner before fetching

        fetch("/configurator/component/offer/template")
            .then(response => response.text())
            .then(html => {

                let tempDiv = document.createElement("div");

                tempDiv.innerHTML = html.trim();

                offerTemplate = tempDiv.querySelector("#offer-template");

            })
            .catch(error => console.error("❌ Failed to load offer completed_config_templates:", error))
            .finally(() => {

                hideSpinner(); // Hide spinner after request completes
            });

    } else {
        //console.log("✅ Offer completed_config_templates already exists in the DOM.");
    }


    // Select all component dropdowns
    let componentSelectors = document.querySelectorAll("[data-component-id]");

    attachComponentOffersEvent(componentSelectors);


        componentSelectors.forEach(select => {
            select.addEventListener("change", function () {
                    updateCompatibleComponents();
            });
        });

    savePcConfiguration();

    resetAiQuestionnaire();
    // Handles image toggle per component (monitor, pc_case)
    setupImageToggle('monitor');
    setupImageToggle('pc_case');

    function attachComponentOffersEvent(componentSelectors) {
        componentSelectors.forEach(selectBox => {

            let componentId = selectBox.getAttribute("data-component-id");

            let offersContainer = document.querySelector(`[data-offers-for='${componentId}']`);
            let hideButton = document.querySelector(`[data-toggle-offers][data-component-id='${componentId}']`);

            if (!offersContainer || !hideButton) {
                console.warn(`⚠️ Missing elements for component: ${componentId}`);
                return;
            }

            hideButton.classList.add("hidden");

            selectBox.addEventListener("change", function (e) {
                isCompletedConfiguration = false;

                const componentValue = e.detail?.value || this.dataset.selectedValue || "";

                offersContainer.classList.add("hidden");
                hideButton.classList.add("hidden");
                offersContainer.innerHTML = "";

                if (!componentValue) return;

                showSpinner(); // Show spinner before fetching offers

                fetch(`/configurator/component/offers/${encodeURIComponent(componentValue)}`)
                    .then(response => response.json())
                    .then(data => {

                        let offers = data[componentValue] || [];

                        if (!Array.isArray(offers)) {
                            console.error("Expected an array but got:", offers);
                            return;
                        }

                        if (offers.length === 0) {
                            offersContainer.innerHTML = "<p class='text-gray-500'>No offers available.</p>";
                            return;
                        }

                        offers.forEach(offer => {

                            if (Object.keys(offer).length === 0) {
                                console.log("Empty offer or missing required fields, skipping...");
                                return; // Skip the iteration if the offer is empty
                            }

                            if (!offerTemplate) {
                                console.error("Offer completed_config_templates not loaded yet.");
                                return;
                            }

                            let offerElement = offerTemplate.content.cloneNode(true);

                            offerElement.querySelector(".vendor-logo").src = `${offer.logo}`;
                            offerElement.querySelector(".vendor-logo").alt = offer.vendor_name;
                            console.log(offer.stock_status);
                            offerElement.querySelector(".stock-text").textContent = offer.stock_status ? "In Stock" : "Out of Stock";

                            let stockStatus = offerElement.querySelector(".stock-status");
                            stockStatus.className = "stock-status px-2 py-1 rounded-full text-xs font-medium";

                            if (offer.stock_status === 'In Stock') {
                                stockStatus.classList.add("bg-green-100", "text-green-800");
                            }
                            if (offer.stock_status === 'Out of Stock') {
                                stockStatus.classList.add("bg-red-100", "text-red-800");
                            }

                            offerElement.querySelector(".vendor_name").textContent = offer.vendor_name;
                            offerElement.querySelector(".price").textContent = offer.price;
                            offerElement.querySelector(".shipping-cost").textContent = offer.shipping_cost;

                            let linkElement = offerElement.querySelector(".view-offer");

                            if (offer.stock_status === "Out of Stock") {
                                // Replace link with a disabled button
                                let button = document.createElement("button");
                                button.className = "inline-flex items-center px-4 py-2 border border-gray-200 text-sm font-medium rounded-md text-gray-400 bg-gray-50 cursor-not-allowed";
                                button.innerHTML = 'Out of Stock <i data-lucide="alert-circle" class="ml-2 h-4 w-4"></i>';

                                linkElement.parentNode.replaceChild(button, linkElement);
                            } else {
                                // Set the href only for in-stock items
                                linkElement.href = offer.link;
                                linkElement.classList.remove("cursor-not-allowed", "text-gray-400", "bg-gray-50");
                            }

                            offersContainer.appendChild(offerElement);
                        });

                        offersContainer.classList.remove("hidden");
                        hideButton.classList.remove("hidden");
                    })
                    .catch(error => {
                        console.error("Error fetching offers:", error);
                        offersContainer.innerHTML = "<p class='text-red-500'>Failed to load offers.</p>";
                    })
                    .finally(() => {

                        hideSpinner(); // Hide spinner after request completes
                    });
            });

            // Hide Offers Button
            hideButton.addEventListener("click", function () {
                offersContainer.classList.add("hidden");
                hideButton.classList.add("hidden");
            });
        });
    }

    function updateCompatibleComponents() {

        let selectedComponents = {};
        // Gather selected components
        componentSelectors.forEach(select => {
            let componentType = select.getAttribute("data-component-id");
            //let selectedValue = select.value;
            let selectedValue = select.dataset.selectedValue || "";
            if (selectedValue) {
                selectedComponents[componentType + "_id"] = selectedValue;
            }
        });

        showSpinner(); // show spinner
        // Make AJAX request to fetch compatible components
        fetch(`/configurator/compatible?` + new URLSearchParams(selectedComponents))
            .then(response => response.json())
            .then(data => {

                updateDropdowns(data);


            })
            .catch(error => console.error("❌ Error fetching compatible components:", error))
            .finally(() => {

                hideSpinner();// hide spinner
            });
    }

    function updateDropdowns(data) {
        Object.keys(allOptions).forEach(componentType => {
            const selectBox = document.querySelector(`.custom-select[data-component-id="${componentType}"]`);
            const spinnerElement = document.querySelector(`[data-spinner-for="${componentType}"]`);
            if (!selectBox) return;

            const selectedDiv = selectBox.querySelector(".select-selected");
            const itemsContainer = selectBox.querySelector(".select-items");
            const currentSelectedValue = selectBox.dataset.selectedValue;

            // ✅ Use the currently shown value as the real default
            const defaultText = selectBox.dataset.defaultText || "Select an option";//selectedDiv.textContent.trim();

            itemsContainer.innerHTML = "";

            // ✅ Build compatibility map from server response
            const serverKey = `${componentType}_ids`;
            const compatibleItems = (data[serverKey] || []).reduce((acc, item) => {
                acc[item.component_id] = item.name;
                return acc;
            }, {});

            // ✅ Default "Select an option"
            const defaultItem = document.createElement("div");
            defaultItem.className = "select-item";
            defaultItem.dataset.value = "";
            defaultItem.textContent = defaultText;
            defaultItem.addEventListener("click", () => {
                selectedDiv.textContent = defaultText;
                selectBox.dataset.selectedValue = "";
                itemsContainer.classList.add("select-hide");

                selectBox.dispatchEvent(new CustomEvent("change", {
                    detail: {value: "", name: ""}
                }));
            });
            itemsContainer.appendChild(defaultItem);

            // ✅ Loop all known options
            Object.entries(allOptions[componentType]).forEach(([id, name]) => {
                const isCompatible = compatibleItems.hasOwnProperty(id);
                const item = document.createElement("div");
                item.className = `select-item ${isCompatible ? 'bg-blue-100 hover:bg-blue-200 cursor-pointer' : 'bg-red-100 text-gray-400 cursor-not-allowed'}`;
                item.dataset.value = id;
                item.textContent = name;

                if (isCompatible) {
                    item.addEventListener("click", () => {
                        selectedDiv.textContent = name;
                        selectBox.dataset.selectedValue = id;
                        itemsContainer.classList.add("select-hide");

                        // ✅ REMOVE ERROR MESSAGE AND STYLING IF PRESENT
                        const componentType = selectBox.getAttribute("data-component-id");
                        const errorMsg = document.getElementById(`${componentType}-error`);
                        if (errorMsg) errorMsg.remove();

                        selectBox.classList.remove("error"); // remove red border class

                        selectBox.dispatchEvent(new CustomEvent("change", {
                            detail: {value: id, name}
                        }));
                        // Trigger bottleneck analysis after component selection
                        triggerBottleneckAICheck();
                    });
                }

                itemsContainer.appendChild(item);
            });

            // ✅ Restore current selection if it's still present in compatible
            if (currentSelectedValue && compatibleItems[currentSelectedValue]) {
                selectedDiv.textContent = compatibleItems[currentSelectedValue];
            } else {
                // Either not compatible anymore or nothing selected
                selectedDiv.textContent = defaultText;
                selectBox.dataset.selectedValue = "";
            }

            if (spinnerElement) spinnerElement.classList.add("hidden");
        });
    }

    function resetAiQuestionnaire() {
        document.getElementById("start-config").addEventListener("click", function () {
            window.location.href = "/"; // Redirects to home page
        });
    }
});


