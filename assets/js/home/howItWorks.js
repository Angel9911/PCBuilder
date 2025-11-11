function initHowItWorks() {
    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const delay = parseInt(entry.target.dataset.delay || 0, 10);
                    setTimeout(() => {
                        entry.target.classList.remove('opacity-0', 'translate-y-10', 'scale-0');
                        entry.target.classList.add('opacity-100', 'translate-y-0', 'scale-100');
                    }, delay);
                    observer.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.2 }
    );

    document.querySelectorAll('[data-animate]').forEach(el => observer.observe(el));
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initHowItWorks);
} else {
    initHowItWorks();
}