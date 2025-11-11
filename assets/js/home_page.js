document.addEventListener('DOMContentLoaded', () => {

    const menuToggle = document.getElementById('menuToggle');
    const mobileMenu = document.getElementById('mobile-menu');
    const userIcon = document.getElementById("open-auth-modal");
    const closeMenu = document.getElementById('closeMenu');
    if (menuToggle && mobileMenu) {
        menuToggle.addEventListener('click', () => {
            const isOpen = menuToggle.classList.contains('open');

            if (!isOpen) {
                menuToggle.classList.add('open');
                mobileMenu.classList.remove('translate-x-full');
                mobileMenu.classList.add('translate-x-0');
                mobileMenu.classList.remove('hidden');
                userIcon.classList.add('hidden');
            } else {
                menuToggle.classList.remove('open');
                mobileMenu.classList.add('translate-x-full');
                mobileMenu.classList.add('hidden');
                //setTimeout(() => mobileMenu.classList.add('hidden'), 500);
                userIcon.classList.remove('hidden');
            }
        });

        /*closeMenu.addEventListener('click', () => {
            menuToggle.classList.remove('open');
            mobileMenu.classList.add('translate-x-full');
            setTimeout(() => mobileMenu.classList.add('hidden'), 500);
            userIcon.classList.remove('hidden');
        });*/
    }

});