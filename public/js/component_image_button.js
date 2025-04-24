// Handles image toggle per component (monitor, pc_case)
function setupImageToggle(componentId) {
    // Dictionary for image paths
    // TODO: For now it's hardcoded but must be fetched by server.
    const imageMap = {
        'monitor': {
            'LG UltraGear 27GN950-B': '/assets/images/monitor_1.png',
            'ASUS ROG Swift PG259QN': '/assets/images/monitor_1.png',
            'Dell Alienware AW3423DW': '/assets/images/monitor_1.png',
            'Samsung Odyssey G7 C32G75T': '/assets/images/monitor_1.png'
        },
        'pc_case': {
            'NZXT H510': '/assets/images/pc_case_1.png',
            'Corsair 4000D Airflow': '/assets/images/pc_case_1.png',
            'Fractal Design Meshify C': '/assets/images/pc_case_1.png',
            'Lian Li PC-O11 Dynamic': '/assets/images/pc_case_1.png',
            'Cooler Master MasterBox TD500': '/assets/images/pc_case_1.png'
        }
    };

    // const select = document.querySelector(`select[data-component-id="${componentId}"]`);
    const select = document.querySelector(`.custom-select[data-component-id="${componentId}"]`);
    const previewContainer = document.getElementById(`${componentId}-image-preview`);
    const previewImage = document.getElementById(`${componentId}-image`);
    //const viewBtn = document.getElementById(`#view-image`);
    const viewBtn = document.getElementById(`${componentId}-view-image`);


    if (!select || !previewContainer || !previewImage || !viewBtn) return;

    const defaultOption = select.querySelector('option')?.text?.trim() ?? 'Select';

    // Handle dropdown change
    select.addEventListener('change', () => {

        const selectedText = select.querySelector(".select-selected")?.textContent.trim();

        viewBtn.textContent = 'View Image';
        previewContainer.classList.add('hidden');

        if (selectedText === defaultOption) {
            previewImage.src = '';
            viewBtn.classList.add('hidden');
            return;
        }

        const imgSrc = imageMap[componentId]?.[selectedText];
        if (imgSrc) {
            previewImage.src = imgSrc;
            viewBtn.classList.remove('hidden');
        } else {
            previewImage.src = '';
            viewBtn.classList.add('hidden');
        }
    });

    // Handle View/Hide Image toggle
    viewBtn.addEventListener('click', () => {
        if (previewContainer.classList.contains('hidden')) {
            previewContainer.classList.remove('hidden');
            viewBtn.textContent = 'Hide Image';
        } else {
            previewContainer.classList.add('hidden');
            viewBtn.textContent = 'View Image';
        }
    });

    // Initial setup (in case something is pre-selected)
    const selectedText = select.querySelector('.select-selected')?.textContent.trim() ?? '';
    if (selectedText !== defaultOption && imageMap[componentId]?.[selectedText]) {
        previewImage.src = imageMap[componentId][selectedText];
        viewBtn.classList.remove('hidden');
    } else {
        previewImage.src = '';
        viewBtn.classList.add('hidden');
    }
}
// Make available globally
window.setupImageToggle = setupImageToggle;
