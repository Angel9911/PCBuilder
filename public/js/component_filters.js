function toggleFilter(button){
    const content = button.nextElementSibling;
    content.classList.toggle('hidden');
}
window.toggleFilter = toggleFilter;