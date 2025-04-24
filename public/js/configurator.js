function fetchComponentData() {
    let cpu = document.getElementById('cpu').value;
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
function saveConfiguration(requestData) {

    showSpinner(); // show spinner
    console.log(requestData);
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

        requestData.name = configName;
        // Proceed to save the configuration
        saveConfiguration(requestData);

    });

}
// Make available globally
window.savePcConfiguration = savePcConfiguration;
