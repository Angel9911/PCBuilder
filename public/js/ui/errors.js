function getComponentLabel(id) {
    const labels = {
        cpu: "процесор",
        gpu: "видеокарта",
        ram: "рам памет",
        psu: "захранване",
        storage: "памет",
        cooling: "охлаждане",
        motherboard: "дънна платка",
        case: "кутия"
    };
    return labels[id] || id;
}
function validateComponents(components) {
    const errors = [];

    components.forEach(component => {
        const wrapper = document.querySelector(`.custom-select[data-component-id="${component}"]`);
        const selectedValue = wrapper?.dataset.selectedValue;

        if (!selectedValue) {
            errors.push({
                component,
                message: `Моля, изберете ${getComponentLabel(component)}.`
            });
        }
    });

    return errors;
}
function showComponentErrors(errors) {
    errors.forEach(({ component, message }) => {
        const wrapper = document.querySelector(`.custom-select[data-component-id="${component}"]`);
        const selected = wrapper?.querySelector('.select-selected');

        if (!selected || !wrapper) return;

        // Add red border via class
        wrapper.classList.add("error");

        // Remove existing error message if any
        const oldError = document.getElementById(`${component}-error`);
        if (oldError) oldError.remove();

        // Create and append error message
        const errorMessage = document.createElement("p");
        errorMessage.id = `${component}-error`;
        errorMessage.className = "text-red-500 text-sm mt-2";
        errorMessage.textContent = message;

        wrapper.parentElement.appendChild(errorMessage);
    });
}
// Make available globally
window.getComponentLabel = getComponentLabel;
window.validateComponents = validateComponents;
window.showComponentErrors = showComponentErrors;