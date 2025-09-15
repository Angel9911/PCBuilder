
    // Tailwind gradient + text classes per band
    const BANDS = [
        { min: 85, bar: 'from-emerald-500 to-green-500', txt: 'text-emerald-700' }, // Excellent
        { min: 70, bar: 'from-blue-500 to-cyan-500',     txt: 'text-blue-700'     }, // Very Good
        { min: 55, bar: 'from-yellow-400 to-amber-500',  txt: 'text-amber-700'    }, // Good
        { min: 40, bar: 'from-orange-400 to-orange-600', txt: 'text-orange-700'   }, // Fair
        { min: 0,  bar: 'from-red-500 to-rose-600',      txt: 'text-rose-700'     }  // Basic
    ];

    const GRAD_PREFIX = 'from-';   // used to strip old gradients
    const TEXT_PREFIX = 'text-';

    function pickBand(v) {
        for (const b of BANDS) if (v >= b.min) return b;
        return BANDS[BANDS.length - 1];
    }

    function cleanseGradientClasses(el) {
        // remove any previous gradient text/bg classes we may have set
        const toRemove = [];
        el.classList.forEach(c => {
            if (c.startsWith('from-') || c.startsWith('to-')) toRemove.push(c);
            if (c.startsWith('text-') && c !== 'text-gray-700') toRemove.push(c);
        });
        toRemove.forEach(c => el.classList.remove(c));
    }

    function clamp01to100(n) {
        n = Number(n);
        if (Number.isNaN(n)) n = 0;
        if (n < 0) n = 0;
        if (n > 100) n = 100;
        return Math.floor(n);
    }

    function normalizeLabel(label) {
        // Only tweak this one name per your spec
        return (label === 'Future Proofing') ? 'Future-Proofing' : label;
    }

    /**
     * @param {ParentNode} [root=document]  // accept Document or any Element
     */
    function initScores(root = document) {

        const items = root.querySelectorAll('.score-item[data-score]');
        items.forEach((item) => {
            const raw = item.getAttribute('data-score');
            const labelRaw = item.getAttribute('data-label') || '';
            const v = clamp01to100(raw);
            const band = pickBand(v);

            // update label
            const labelEl = item.querySelector('.score-label');
            if (labelEl) labelEl.textContent = normalizeLabel(labelRaw);

            // set numeric value + color
            const valEl = item.querySelector('.score-value');

            //console.log(valEl);

            if (valEl) {
                valEl.textContent = String(v);
                cleanseGradientClasses(valEl);
                band.txt.split(' ').forEach(cls => valEl.classList.add(cls));
            }

            // set bar width + gradient
            const barEl = item.querySelector('.score-bar');
            if (barEl) {
                cleanseGradientClasses(barEl);
                // ensure bg-gradient-to-r exists once
                barEl.classList.add('bg-gradient-to-r');
                band.bar.split(' ').forEach(cls => barEl.classList.add(cls));
                barEl.style.width = v + '%';
            }
        });
    }
    // Expose if you need to re-run after AJAX
    window.initComponentScores = initScores;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initScores);
} else {
    initScores();
}