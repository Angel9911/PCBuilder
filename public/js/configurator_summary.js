// Example state - normally you'd get this from backend or selection actions
const selectedComponents = [
    { name: 'CPU', price: 200 },
    { name: 'GPU', price: 350 },
];
const totalComponentsRequired = 9;

function updateBuildSummary() {
    const count = selectedComponents.length;
    const total = selectedComponents.reduce((sum, c) => sum + c.price, 0);
    const progress = (count / totalComponentsRequired) * 100;

    // Update count
    document.querySelector('#build-summary .components-count').textContent = `${count} of ${totalComponentsRequired}`;

    // Update total price
    document.querySelector('#build-summary .total-price').textContent = `$${total.toFixed(2)}`;

    // Update progress text and bar
    document.querySelector('#build-summary .progress-text').textContent = `${Math.round(progress)}%`;
    document.querySelector('#build-summary .progress-bar').style.width = `${progress}%`;
}
window.updateBuildSummary = updateBuildSummary;