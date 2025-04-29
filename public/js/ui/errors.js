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
        const selected = wrapper?.querySelector('.select-selected');
        const selectedValue = selected?.getAttribute('data-value');

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

        // Add red border background
        selected.classList.add("border", "border-red-500", "bg-red-50");

        // Remove existing error message if any
        const oldError = document.getElementById(`${component}-error`);
        if (oldError) oldError.remove();

        // Add error message if not present
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