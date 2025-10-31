function initRatingModal() {
    const ratingModal = document.getElementById('ratingModal');
    if (!ratingModal) {
        console.warn("⚠️ ratingModal element not found in DOM");
        return;
    }

    const cancelBtn = document.getElementById('cancelRatingModal');
    const closeBtn = document.getElementById('closeRatingModal');
    const form = document.getElementById('ratingForm');
    const submitBtn = document.getElementById('submitRating');
    const modalStars = document.querySelectorAll('.modal-star-btn');
    const productRatingSection = document.getElementById('product-rating');
    let selectedRating = 0;

    // Open modal when a star is clicked
    document.querySelectorAll('.star-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            ratingModal.classList.remove('hidden');
            ratingModal.classList.add('flex'); // ensure visible in Tailwind
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
        if (!selectedRating || !productRatingSection) return;

        const ratingData = {
            product_id: productRatingSection.dataset.componentId,
            stairs: selectedRating,
            comment: form.comment.value
        };

        const userData = {
            name: form.name.value,
            email: form.email.value,
        };

        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting...';

        const productType = productRatingSection.dataset.productType;

        try {

            const response = await fetch(`/product/rate/${encodeURIComponent(productType)}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({rating_product: ratingData, user: userData})//({ user_requirement: query })
            });

            if (response.ok) {
                alert('Thank you for your feedback!');
                closeModal();

                // Small delay for smooth UX, then reload
                setTimeout(() => {
                    window.location.reload();
                }, 500);

            } else {
                console.log(response);
                alert('Something went wrong. Please try again.');
            }
        } catch (err) {
            console.error(err);
            alert('Network error. Please try again.');
        } finally {
            submitBtn.textContent = 'Submit Rating';
            submitBtn.disabled = false;
        }
    });
}

// Safe init wrapper (works for static or dynamically injected HTML)
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initRatingModal);
} else {
    initRatingModal();
}
