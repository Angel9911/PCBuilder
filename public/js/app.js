// public/js/app.js
const page = document.body.dataset.page;
console.log(page);
(async () => {
    switch (page) {
        case 'home':
            await import('./home_page.js');
            await import('./ai_questionnaire.js');
            await import('./auth_login_registration.js');
            break;
        case 'configurator.build':
            await import('./pc_build_configuration.js');
            await import('./configurator.js');
            await import('./configurator_summary.js');
            await import('./bottleneck_configurator.js');
            await import('./performance_analysis.js');
            await import('./component_image_button.js');
            await import('./ui/offer_template.js');
            await import('./ui/errors.js');
            break;
        case 'component.filter':
            await import('./new_product_filters.js');
            await import('./ai_product_field.js');
            await import('./component_scores_section.js');
            break;
        default:
    }
})();