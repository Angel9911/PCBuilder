document.addEventListener("DOMContentLoaded", function () {
    // Initialize the combobox fields if there are given AI recommendation components or given completed configuration
    if (typeof pcConfiguration !== "undefined" && pcConfiguration && Object.keys(pcConfiguration).length > 0) {

        Object.keys(pcConfiguration).forEach(component => {
            let componentData = pcConfiguration[component];
            let selectElement = document.querySelector(`select[data-component-id="${component.toLowerCase()}"]`);


            if (selectElement) {
                let componentOption = Array.from(selectElement.options).find(option => option.textContent.trim() === componentData.name);


                if (componentOption) {
                    componentOption.selected = true;

                    // Trigger change event to load offers
                    setTimeout(() => {
                        selectElement.dispatchEvent(new Event("change"));
                        //console.log(`🔄 Change Event Dispatched for AI-selected: ${componentData.name}`);
                    }, 100);
                } else {
                    console.warn(`⚠️ No matching option found for ${componentData.name} in ${component}`);
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
            .catch(error => console.error("❌ Failed to load offer template:", error))
            .finally(() => {

                hideSpinner(); // Hide spinner after request completes
            });

    } else {
        //console.log("✅ Offer template already exists in the DOM.");
    }


    // Select all component dropdowns
    let componentSelectors = document.querySelectorAll("[data-component-id]");

    attachComponentOffersEvent(componentSelectors);

    if (!isAiConfiguration) {

        componentSelectors.forEach(select => { // TODO: Here there is a problem, because the previous method doesn't working and
            select.addEventListener("change", function () {

                updateCompatibleComponents(); // 🔥 Only fetch if NOT from AI or if not from completed configuration

            });
        });
    }

    savePcConfiguration();

    resetAiQuestionnaire();

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

            selectBox.addEventListener("change", function () {
                isAiConfiguration = false;
                let componentValue = this.value;

                offersContainer.classList.add("hidden");
                hideButton.classList.add("hidden");
                offersContainer.innerHTML = "";

                if (!componentValue) return;

                showSpinner(); // Show spinner before fetching offers

                fetch(`/configurator/component/offers/${encodeURIComponent(this.value)}`)
                    .then(response => response.json())
                    .then(data => {
                        let offers = data[componentValue] || [];

                        //console.log("Fetched offers:", offers); // Debugging
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
                                console.error("Offer template not loaded yet.");
                                return;
                            }

                            let offerElement = offerTemplate.content.cloneNode(true);
                            //console.log("Cloned template:", offerElement); //  Debugging

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
                        //console.log("Offers inserted into:", offersContainer);// Debugging
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
            let selectedValue = select.value;
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

        Object.keys(data).forEach(componentType => {
            let componentName = componentType.replace("_ids", ""); // Convert "cpu_ids" to "cpu"
            let selectElement = document.querySelector(`select[data-component-id="${componentName}"]`);
            let spinnerElement = document.querySelector(`[data-spinner-for="${componentName}"]`);

            if (selectElement) {
                let selectedValue = selectElement.value; // Preserve user's selection
                let options = Array.from(selectElement.options);
                // Remove all options except the first placeholder
                options.slice(1).forEach(option => option.remove());

                // ✅ Fix: Iterate over object entries instead of using .forEach()
                Object.entries(data[componentType]).forEach(([id, names]) => {
                    let option = document.createElement("option");
                    option.value = id;
                    option.textContent = names[0]; // First element in name array
                    selectElement.appendChild(option);
                });

                // Restore previous selection if still valid
                if (selectedValue && Object.keys(data[componentType]).includes(selectedValue)) {
                    selectElement.value = selectedValue;
                } else {
                    selectElement.value = ""; // Reset if no longer valid
                }

            }
            // Hide spinner when dropdown is updated
            if (spinnerElement) {
                spinnerElement.classList.add("hidden");
            }
        });
    }

    function savePcConfiguration() {
        const requiredComponents = ['cpu', 'gpu', 'motherboard', 'ram', 'psu', 'storage'];


        let saveButton = document.getElementById('saveConfiguration');
        let modal = document.getElementById('saveConfigModal');
        let cancelSaveButton = document.getElementById('cancelSave');
        let confirmSaveButton = document.getElementById('confirmSave');

        // Show modal when clicking "Запази Конфигурация"
        saveButton.addEventListener('click', function () {

            const validationErrors = validateComponents(requiredComponents);

            if (validationErrors.length > 0) {
                showComponentErrors(validationErrors);
            } else {
                modal.classList.remove('hidden'); // Show modal only if valid
            }

        });

        // Close modal if "Отказ" is clicked
        cancelSaveButton.addEventListener('click', function () {
            modal.classList.add('hidden');
        });


        confirmSaveButton.addEventListener('click', function () {

            // Get the configuration name
            let configName = document.getElementById('configName').value.trim();

            // Validate the configuration name
            if (configName === "") {
                alert("Моля, въведете име на конфигурацията."); // Alert for empty configuration name
                return; // Stop further execution
            }

            // Gather the selected component values
            const requestData = fetchComponentData();
            // Proceed to save the configuration
            saveConfiguration(requestData);

        });

    }

    function fetchComponentData() {
        let cpu = document.getElementById('cpu-select').value;
        let motherboard = document.getElementById('motherboard').value;
        let ram = document.getElementById('ram').value;
        let gpu = document.getElementById('gpu').value;
        let storage = document.getElementById('storage').value;
        let psu = document.getElementById('psu').value;

        return {
            cpu: cpu,
            motherboard: motherboard,
            ram: ram,
            gpu: gpu,
            storage: storage,
            psu: psu
        };
    }

    function getComponentLabel(id) {
        const labels = {
            cpu: "процесор",
            gpu: "видеокарта",
            ram: "рам памет",
            psu: "захранване",
            storage: "сторно устройство",
            cooling: "охлаждане",
            motherboard: "дънна платка",
            case: "кутия"
        };
        return labels[id] || id;
    }

    function validateComponents(components) {
        const errors = [];

        components.forEach(component => {

            const select = document.getElementById(`${component}`);

            const value = select?.value;

            if (!value) {
                errors.push({component, message: `Моля, изберете ${getComponentLabel(component)}.`});
            }
        });

        return errors;
    }

    function showComponentErrors(errors) {
        errors.forEach(({component, message}) => {

            const select = document.getElementById(`${component}`);

            // Add red border background
            select.classList.add("border-red-500", "bg-red-50");

            // Check if error message already exists
            if (!document.getElementById(`${component}-error`)) {
                const errorMessage = document.createElement("p");
                errorMessage.id = `${component}-error`;
                errorMessage.className = "text-red-500 text-sm mt-2";
                errorMessage.textContent = message;

                select.parentElement.appendChild(errorMessage);
            }
        });
    }

    function saveConfiguration(requestData) {

        showSpinner(); // show spinner
        // Send the request
        fetch('/configurator/save', {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify(requestData)
        }).then(response => {

            if (response.ok) {
                // Redirect to PC build configuration page
                window.location.href = '/completed/build';
            } else {
                // Handle error response
                return response.json().then(errorData => {
                   // handleErrorResponse(errorData);
                });
            }
        }).catch(error => console.log(error))
            .finally(() => {

                hideSpinner()// hide spinner
            });
    }

    // Show the spinner
    function showSpinner() {
        document.getElementById("loading-spinner").classList.remove("hidden");
    }

    // Hide the spinner
    function hideSpinner() {
        document.getElementById("loading-spinner").classList.add("hidden");
    }

    function resetAiQuestionnaire() {
        document.getElementById("start-config").addEventListener("click", function () {
            window.location.href = "/"; // Redirects to home page
        });
    }
});


