function initCompletedBuilds() {
    const observer = new IntersectionObserver(
        entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = "1";
                    entry.target.style.transform = "translateY(0)";
                    //entry.target.style.transition = "all 0.8s ease";
                    observer.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.2 }
    );

    document.querySelectorAll("[data-animate-completed]")
        .forEach(el => observer.observe(el));
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initCompletedBuilds);
} else {
    initCompletedBuilds();
}