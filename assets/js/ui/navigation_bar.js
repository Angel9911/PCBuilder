document.addEventListener('DOMContentLoaded', () => {
    const toggleBtnHardware = document.getElementById('hardwareToggle');
    const menu = document.getElementById('hardwareMenu');
    toggleBtnHardware.addEventListener('click', () => {
        menu.classList.toggle('hidden');
        menu.classList.toggle('show');
    });
});