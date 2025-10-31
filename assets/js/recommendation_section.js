/**
 * Initialize the recommendation section carousel.
 * @param {Document|HTMLElement} ctx - context in which to search for carousel elements
 */
window.initRecommendationSection = function (ctx = document) {
    const track = ctx.querySelector('#recommendation-track');
    if (!track) return;

    const prev = ctx.querySelector('#prev-slide');
    const next = ctx.querySelector('#next-slide');
    const counter = ctx.querySelector('#slide-counter');

    const pages = track.children.length; // each page = 3 cards
    let current = 1;

    function updateSlide() {
        const offset = (current - 1) * -100;
        track.style.transform = `translateX(${offset}%)`;
        if (counter) counter.textContent = `${current} / ${pages}`;
    }

    prev?.addEventListener('click', () => {
        if (current > 1) current--;
        updateSlide();
    });

    next?.addEventListener('click', () => {
        if (current < pages) current++;
        updateSlide();
    });

    updateSlide();
};
