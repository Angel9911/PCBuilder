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
// Make available globally
window.getComponentLabel = getComponentLabel;
window.validateComponents = validateComponents;
window.showComponentErrors = showComponentErrors;