document.addEventListener('DOMContentLoaded', () => {
    const menuToggle = document.getElementById('mobile-menu-toggle');
    const menu = document.getElementById('mobile-menu');

    if (menuToggle && menu) {
        menuToggle.addEventListener('click', () => {
            if (menu.classList.contains('hidden')) {
                menu.classList.remove('hidden');
                menu.classList.remove('animate-fade-slide');
                void menu.offsetWidth;
                menu.classList.add('animate-fade-slide');
            } else {
                menu.classList.add('hidden');
                menu.classList.remove('animate-fade-slide');
            }
        });
    }
});