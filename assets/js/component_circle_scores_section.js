
    const buttons = document.querySelectorAll('.tab-btn');

    const panels  = document.querySelectorAll('.tab-panel');

    buttons.forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.dataset.tabTarget;

            // reset buttons
            buttons.forEach(b => b.classList.remove('text-indigo-600','border-indigo-600','bg-indigo-50/50'));
            buttons.forEach(b => b.classList.add('text-slate-600','hover:text-slate-900','hover:bg-slate-50'));

            // reset panels
            panels.forEach(p => p.classList.add('hidden'));
            panels.forEach(p => p.classList.remove('active'));

            // activate
            btn.classList.add('text-indigo-600','border-b-2','border-indigo-600','bg-indigo-50/50');
            const panel = document.querySelector(`.tab-panel[data-tab="${target}"]`);

            if (panel) panel.classList.remove('hidden');

        });
    });

    document.querySelectorAll('.circle-score-item').forEach(item => {
        const score = Math.min(100, Math.max(0, parseInt(item.dataset.score || '0', 10)));
        const circle = item.querySelector('.circle-score-bar');
        const status = item.querySelector('.circle-score-status');
        const svg    = circle.closest('svg');

        // ensure defs exists
        let defs = svg.querySelector('defs');
        if (!defs) {
            defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
            svg.prepend(defs);
        }

        // function to inject gradient if not already there
        function ensureGradient(id, stops) {
            let grad = defs.querySelector(`#${id}`);
            if (!grad) {
                grad = document.createElementNS('http://www.w3.org/2000/svg', 'linearGradient');
                grad.setAttribute('id', id);
                grad.setAttribute('x1', '0%');
                grad.setAttribute('y1', '0%');
                grad.setAttribute('x2', '100%');
                grad.setAttribute('y2', '0%');
                stops.forEach(stop => {
                    const s = document.createElementNS('http://www.w3.org/2000/svg', 'stop');
                    s.setAttribute('offset', stop.offset);
                    s.setAttribute('stop-color', stop.color);
                    grad.appendChild(s);
                });
                defs.appendChild(grad);
            }
        }

        // circle geometry
        const radius = 40;
        const circumference = 2 * Math.PI * radius;
        circle.style.strokeDasharray = `${circumference}`;
        circle.style.strokeDashoffset = circumference;
        circle.style.strokeDashoffset = circumference - (score / 100) * circumference;
        // animate
        setTimeout(() => {
            circle.style.strokeDashoffset = circumference - (score / 100) * circumference;
        }, 100);

        // thresholds
        let label = '';
        let colorClasses = '';
        let gradientId = '';

        if (score >= 80) {
            label = 'Excellent';
            colorClasses = 'bg-gradient-to-r from-emerald-500 to-green-600 text-white';
            gradientId = 'grad-excellent';
            ensureGradient(gradientId, [
                { offset: '0%',  color: '#10b981' }, // emerald-500
                { offset: '100%', color: '#16a34a' } // green-600
            ]);
        } else if (score >= 60) {
            label = 'Good';
            colorClasses = 'bg-gradient-to-r from-yellow-400 to-orange-500 text-white';
            gradientId = 'grad-good';
            ensureGradient(gradientId, [
                { offset: '0%',  color: '#facc15' }, // yellow-400
                { offset: '100%', color: '#f97316' } // orange-500
            ]);
        } else {
            label = 'Not Good';
            colorClasses = 'bg-gradient-to-r from-red-400 to-orange-500 text-white';
            gradientId = 'grad-bad';
            ensureGradient(gradientId, [
                { offset: '0%',  color: '#f87171' }, // red-400
                { offset: '100%', color: '#f97316' } // orange-500
            ]);
        }

        // apply gradient stroke
        circle.style.stroke = `url(#${gradientId})`;

        // update status badge
        status.textContent = label;
        status.className = `inline-block px-3 py-1 rounded-full text-sm font-medium ${colorClasses}`;
    });