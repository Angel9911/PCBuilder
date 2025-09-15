// Show the spinner
function showSpinner() {
    document.getElementById("loading-spinner").classList.remove("hidden");
}

// Hide the spinner
function hideSpinner() {
    document.getElementById("loading-spinner").classList.add("hidden");
}
// Make available globally
window.showSpinner = showSpinner;
window.hideSpinner = hideSpinner;