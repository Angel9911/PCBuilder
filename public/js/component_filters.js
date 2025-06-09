document.addEventListener('DOMContentLoaded', () => {
    const checkboxes = document.querySelectorAll('input[type="checkbox"]');
    const componentType = document.getElementById('component-data').dataset.type;
    const clearAllBtn = document.getElementById('clearFilters');

    const detailLinks = document.querySelectorAll('a[href^="/component/"]');

    detailLinks.forEach(link => {
        handleViewDetailsButton(link);
    });

    checkboxes.forEach(cb => cb.addEventListener('change', () => handleFilterChange(1)));

    clearAllBtn.addEventListener('click', () => {
        clearAllFilters();
        handleFilterChange(1); // Reset to first page
    });

    function handleFilterChange(page = 1) {
        const urlParams = new URLSearchParams();

        // Gather all selected filters
        document.querySelectorAll('input[type="checkbox"]:checked').forEach(cb => {
            urlParams.append(cb.name, cb.value); // keep "socket[]" as is
        });

        // Always include page
        urlParams.set('page', page);

        showSpinner();

        const url = `/component/${componentType}?${urlParams.toString()}`;

        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(res => res.json())
            .then(data => {

                document.getElementById('component-container').innerHTML = data.components;
                document.getElementById('pagination-container').innerHTML = data.pagination;
            })
            .catch(err => console.error('Error loading components:', err))
            .finally( () => {
                hideSpinner(); // Hide spinner after request completes
            });
    }
});
window.addEventListener('pageshow', (event) => {

    hideSpinner();
});


function clearAllFilters() {
    document.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
    // Optional: trigger a re-fetch here
}
function toggleFilter(button) {
    const content = button.nextElementSibling;
    content.classList.toggle('hidden');

    const icon = button.querySelector('svg');
    if (icon) icon.classList.toggle('rotate-180');
}
function handleViewDetailsButton(button){

    button.addEventListener('click', (e) => {
        showSpinner();
    });
}

window.toggleFilter = toggleFilter;