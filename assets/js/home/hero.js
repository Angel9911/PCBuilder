function initHero() {
    const slideContainer = document.getElementById('hero-slides');
    const dotsContainer  = document.getElementById('hero-dots');
    const nextBtn        = document.getElementById('hero-next');
    const prevBtn        = document.getElementById('hero-prev');

    if (!slideContainer || !dotsContainer || !nextBtn || !prevBtn) {
        console.warn('[hero] Required DOM nodes missing. Aborting init.', {
            slideContainer: !!slideContainer,
            dotsContainer : !!dotsContainer,
            nextBtn       : !!nextBtn,
            prevBtn       : !!prevBtn
        });
        return;
    }

    const slides = [
        {
            title: "Building PC Configuration with AI",
            description: "Experience the future of PC building with our intelligent AI assistant...",
            image: "https://images.unsplash.com/photo-1587202372634-32705e3bf49c?w=1920&q=80",
            gradient: "from-blue-950/95 via-blue-950/85 to-transparent",
            buttons: [
                { text: "Build with AI", link: "/#", variant: "primary" },
                { text: "Build Manually", link: "/configurator/build", variant: "secondary" }
            ]
        },
        {
            title: "Guaranteed Component Compatibility",
            description: "Never worry about incompatible parts again. Our advanced compatibility algorithm validates every component combination in real-time.",
            image: "https://images.unsplash.com/photo-1591488320449-011701bb6704?w=1920&q=80",
            gradient: "from-purple-900/90 via-purple-900/70 to-transparent",
            buttons: [
                { text: "Build Your PC", link: "/configurator/build", variant: "primary" }
            ]
        },
        {
            title: "Choose from Pre-Built PC Configurations",
            description: "Explore curated Pre-Built PC configurations created by our community and experts.",
            image: "https://images.unsplash.com/photo-1593640495253-23196b27a87f?w=1920&q=80",
            gradient: "from-indigo-950/95 via-indigo-900/70 to-transparent",
            buttons: [
                { text: "View Completed PC Builds", link: "/completed/build", variant: "primary" }
            ]
        },
        {
            title: "AI-Powered Product Finder",
            description: "Overwhelmed by choices? Our AI Product Finder analyzes your requirements and recommends the top 5 hardware components or peripherals perfectly matched to your needs. Get detailed comparisons, performance ratings, and compatibility scores to make the best purchasing decision with confidence.",
            image: "https://images.unsplash.com/photo-1616588589676-62b3bd4ff6d2?w=1920&q=80",
            gradient: "from-blue-950/95 via-blue-950/85 to-transparent",
            buttons: [
                { text: "Find Best Component", link: "/product/gpu", variant: "primary" },
                { text: "Find Best Peripheral", link: "/product/keyboard", variant: "secondary" }
            ]
        }
    ];

    let currentSlide = 0;

    // Preload images (non-blocking)
    slides.forEach(s => { const i = new Image(); i.src = s.image; });

    // Build slides + dots
    slideContainer.innerHTML = '';
    dotsContainer.innerHTML  = '';

    slides.forEach((slide, i) => {
        const slideEl = document.createElement('div');
        slideEl.className = `absolute inset-0 transition-opacity duration-1000 ${i === 0 ? 'opacity-100 z-20' : 'opacity-0 z-10'}`;
        slideEl.innerHTML = `
  <div class="relative w-full h-full">
    <!-- Background image -->
    <img src="${slide.image}" alt="${slide.title}" class="absolute inset-0 w-full h-full object-cover z-0">

    <!-- Overlay gradients -->
    <div class="absolute inset-0 bg-gradient-to-r from-blue-950/95 via-blue-950/85 to-transparent z-10"></div>
    <div class="absolute inset-0 bg-gradient-to-br from-blue-900/40 via-indigo-900/30 to-transparent z-10"></div>

    <!-- Decorative blurred circles -->
    <div class="absolute inset-0 opacity-20 pointer-events-none z-20">
        <div class="absolute top-20 left-20 w-64 h-64 bg-cyan-400 rounded-full blur-3xl"></div>
        <div class="absolute bottom-20 left-40 w-96 h-96 bg-purple-400 rounded-full blur-3xl"></div>
    </div>

    <!-- Text content -->
    <div class="absolute inset-0 flex items-center px-8 md:px-16 z-[999]">
      <div class="max-w-7xl mx-auto">
        <div class="max-w-2xl text-white">
          <h2 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6 leading-tight drop-shadow-2xl">${slide.title}</h2>
          <p class="text-lg md:text-xl mb-8 leading-relaxed text-blue-50 drop-shadow-lg">${slide.description}</p>
          <div class="flex flex-col sm:flex-row gap-4">
            ${slide.buttons.map(b => `
            <a href="${b.link}" class="${b.variant === 'primary'
                ? 'inline-flex items-center justify-center gap-2 whitespace-nowrap font-medium focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 hover:bg-primary/90 h-10 group relative overflow-hidden bg-gradient-to-r from-cyan-500 via-blue-500 to-purple-600 hover:from-cyan-400 hover:via-blue-400 hover:to-purple-500 text-white border-0 shadow-2xl px-8 py-6 text-base rounded-xl transition-all duration-300 hover:scale-105 hover:shadow-cyan-500/50'
                : 'inline-flex items-center justify-center gap-2 whitespace-nowrap font-medium focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0 h-10 px-8 text-base rounded-xl transition-all duration-300 hover:scale-105 shadow-lg hover:shadow-xl border-2 border-white/60 bg-white/10 hover:bg-white/20 text-white backdrop-blur-md'
            }">${b.text}</a>`
        ).join('')}
          </div>
        </div>
      </div>
    </div>
  </div>
    `;
        slideContainer.appendChild(slideEl);

        const dot = document.createElement('button');
        dot.className = `w-2 h-2 rounded-full ${i === 0 ? 'w-10 bg-white' : 'bg-white/40'} transition-all duration-300`;
        dot.addEventListener('click', () => showSlide(i));
        dotsContainer.appendChild(dot);
    });

    function showSlide(index) {
        const slidesEl = slideContainer.querySelectorAll(':scope > div');
        const dotsEl   = dotsContainer.querySelectorAll(':scope > button');

        slidesEl.forEach((s, i) => {
            s.classList.toggle('opacity-100', i === index);
            s.classList.toggle('opacity-0',   i !== index);
            s.classList.toggle('z-20',        i === index);
            s.classList.toggle('z-10',        i !== index);
        });

        dotsEl.forEach((d, i) => {
            d.className = `w-2 h-2 rounded-full transition-all duration-300 ${i === index ? 'w-10 bg-white' : 'bg-white/40'}`;
        });

        currentSlide = index;
    }

    const nextSlide = () => showSlide((currentSlide + 1) % slides.length);
    const prevSlide = () => showSlide((currentSlide - 1 + slides.length) % slides.length);

    nextBtn.addEventListener('click', nextSlide);
    prevBtn.addEventListener('click', prevSlide);

    // Auto-advance
    setInterval(nextSlide, 7000);

    console.log('[hero] initialized');
}

// ✅ Safe init wrapper (same pattern as your rating modal)
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initHero);
} else {
    initHero();
}