function initRatingModal() {
    const ratingModal = document.getElementById('ratingModal');
    if (!ratingModal) {
        console.warn("⚠️ ratingModal element not found in DOM");
        return;
    }

    const modalTitle = document.getElementById('ratingModalTitle');
    const cancelBtn = document.getElementById('cancelRatingModal');
    const closeBtn = document.getElementById('closeRatingModal');
    const form = document.getElementById('ratingForm');
    const submitBtn = document.getElementById('submitRating');
    const modalStars = document.querySelectorAll('.modal-star-btn');

    let selectedRating = 0;
    let currentTarget = null;
    // Open modal when a star is clicked
    document.querySelectorAll('.star-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            currentTarget = btn.closest('#product-rating, #config-rating');

            if (currentTarget.id === 'product-rating') {
                modalTitle.textContent = "Rate This Product";
            } else {
                modalTitle.textContent = "Rate This PC Build";
            }

            ratingModal.classList.remove('hidden');
            ratingModal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        });
    });

    // Close modal helper
    const closeModal = () => {
        ratingModal.classList.add('hidden');
        ratingModal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
        selectedRating = 0;

        modalStars.forEach(star => {
            const svg = star.querySelector('svg');
            svg.classList.remove('text-yellow-400', 'fill-yellow-400');
            svg.classList.add('text-gray-300');
        });

        if (form) form.reset();
        if (submitBtn) submitBtn.disabled = true;
    };

    // Add close listeners
    cancelBtn?.addEventListener('click', closeModal);
    closeBtn?.addEventListener('click', closeModal);
    ratingModal.addEventListener('click', (e) => {
        if (e.target === ratingModal) closeModal();
    });

    // Modal star selection logic
    modalStars.forEach(star => {
        star.addEventListener('click', () => {
            selectedRating = parseInt(star.dataset.value);
            modalStars.forEach(s => {
                const svg = s.querySelector('svg');
                if (parseInt(s.dataset.value) <= selectedRating) {
                    svg.classList.add('text-yellow-400', 'fill-yellow-400');
                    svg.classList.remove('text-gray-300');
                } else {
                    svg.classList.remove('text-yellow-400', 'fill-yellow-400');
                    svg.classList.add('text-gray-300');
                }
            });
            submitBtn.disabled = selectedRating === 0;
        });
    });

    // Submit form logic
    form?.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!currentTarget) return;

        const userData = {
            name: form.name.value,
            email: form.email.value,
        };

        let endpoint = "";
        let payload = {};

        if (currentTarget.id === "product-rating") {
            endpoint = `/product/rate/${encodeURIComponent(currentTarget.dataset.productType)}`;
            payload = {
                rating_product: {
                    product_id: currentTarget.dataset.componentId,
                    stairs: selectedRating,
                    comment: form.comment.value
                },
                user: userData
            };

        } else if (currentTarget.id === "config-rating") {
            endpoint = "/completed/build/rate";

            payload = {
                rating_config: {
                    pc_config_id: currentTarget.dataset.configId,
                    stairs: selectedRating,
                    comment: form.comment.value
                },
                user: userData
            };
        }

        submitBtn.disabled = true;
        submitBtn.textContent = "Submitting...";

        try {
            const response = await fetch(endpoint, {
                method: "POST",
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            if (response.ok) {
                alert("Thank you for your feedback!");
                closeModal();
                setTimeout(() => window.location.reload(), 500);
            } else {
                alert("Something went wrong.");
            }
        } catch (err) {
            alert("Network error.");
        }

        submitBtn.textContent = "Submit Rating";
        submitBtn.disabled = false;
    });
}

// Safe init wrapper (works for static or dynamically injected HTML)
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initRatingModal);
} else {
    initRatingModal();
}
