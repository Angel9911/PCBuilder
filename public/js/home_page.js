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

    // ========== Hero Carousel ==========
    const slides = [
        {
            image: "https://images.pexels.com/photos/1029757/pexels-photo-1029757.jpeg",
            title: "AI-Powered PC Building",
            subtitle: "Let our AI help you build the perfect PC for your needs"
        },
        {
            image: "https://images.pexels.com/photos/777001/pexels-photo-777001.jpeg",
            title: "Expert Component Selection",
            subtitle: "Get personalized recommendations based on your requirements"
        },
        {
            image: "https://images.pexels.com/photos/1714208/pexels-photo-1714208.jpeg",
            title: "Best Price Guarantee",
            subtitle: "Compare prices across multiple vendors to get the best deal"
        }
    ];

    let currentSlide = 0;

    const slidesContainer = document.getElementById("carousel-slides");
    const dotsContainer = document.getElementById("carousel-dots");
    const prevBtn = document.getElementById("prev-slide");
    const nextBtn = document.getElementById("next-slide");

    slides.forEach(slide => {
        const img = new Image();
        img.src = slide.image;
    });

    function initialSlideSetup() {
        if (!slidesContainer || !dotsContainer) return;

        slides.forEach((slide, index) => {
            const slideEl = document.createElement("div");
            slideEl.className = `carousel-slide absolute inset-0 transition-opacity duration-1000 ${index === currentSlide ? "opacity-100 z-20" : "opacity-0 z-10"}`;
            slideEl.innerHTML = `
            <div class="absolute inset-0 bg-black/40 z-10"></div>
            <img src="${slide.image}" class="w-full h-full object-cover" loading="lazy" />
            <div class="absolute inset-0 z-20 flex items-center justify-center">
                <div class="text-center text-white max-w-4xl px-4">
                    <h1 class="text-4xl md:text-6xl font-bold mb-6">${slide.title}</h1>
                    <p class="text-xl md:text-2xl mb-8">${slide.subtitle}</p>
                    <div class="space-y-4">
                    <a id="start-config" href="#"
                        class="start-carousel-config-btn inline-block bg-blue-600 text-white px-8 py-4 rounded-lg text-lg font-semibold hover:bg-blue-700 transition-all transform hover:scale-105 shadow-lg hover:shadow-blue-500/50">
                        Start Building Now
                    </a>
                  
                    <a href="/configurator/manual" 
                        class="inline-block bg-white/90 text-blue-700 px-8 py-4 rounded-lg text-lg font-semibold hover:bg-white hover:shadow-md transition-all transform hover:scale-105 shadow">
                        Manual Configuration
                    </a>
            </div>
                </div>
            </div>
        `;
            slidesContainer.appendChild(slideEl);

            document.querySelectorAll('.start-carousel-config-btn').forEach(btn => {
                btn.addEventListener("click", function (e) {
                    e.preventDefault();
                    document.getElementById("main-content").classList.add("hidden");
                    document.getElementById("slideshow-content").classList.add("hidden");
                    document.getElementById("questionnaire").classList.remove("hidden");
                });
            });


            const dot = document.createElement("button");
            dot.className = `carousel-d
            ot w-3 h-3 rounded-full transition-all duration-300 ${index === currentSlide ? "bg-white w-8" : "bg-white/50"}`;
            dot.addEventListener("click", () => showSlide(index));
            dotsContainer.appendChild(dot);
        });

        // Modal buttons
        document.querySelectorAll('[id^="start-modal-btn-"]').forEach(btn => {
            btn.addEventListener('click', openModal);
        });
    }

    function prevSlide() {
        const newIndex = (currentSlide - 1 + slides.length) % slides.length;
        showSlide(newIndex);
    }

    function nextSlide() {
        const newIndex = (currentSlide + 1) % slides.length;
        showSlide(newIndex);
    }

    function showSlide(index) {
        const allSlides = document.querySelectorAll(".carousel-slide");
        const allDots = document.querySelectorAll(".carousel-dot");

        allSlides.forEach((slide, i) => {
            slide.classList.toggle("opacity-100", i === index);
            slide.classList.toggle("opacity-0", i !== index);
            slide.classList.toggle("z-20", i === index);
            slide.classList.toggle("z-10", i !== index);
        });

        allDots.forEach((dot, i) => {
            dot.className = `carousel-dot w-3 h-3 rounded-full transition-all duration-300 ${i === index ? "bg-white w-8" : "bg-white/50"}`;
        });

        currentSlide = index;
    }

    function autoSlide() {
        nextSlide();
    }

    function openModal() {
        // Replace with your actual modal logic
        alert("Modal for user requirements should open here!");
    }

    if (prevBtn) prevBtn.addEventListener('click', prevSlide);
    if (nextBtn) nextBtn.addEventListener('click', nextSlide);

    initialSlideSetup();
    setInterval(autoSlide, 7000); // Auto switch every 7 seconds

});