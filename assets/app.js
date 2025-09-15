// public/js/app.js
const page = document.body.dataset.page;

(async () => {
    switch (page) {
        case 'home':
            await import('app/home_page');
            await import('app/ai_questionnaire');
            await import('app/auth_login_registration');
            break;
        case 'configurator.build':
            await import('app/configurator');
            await import('app/configurator_summary');
            await import('app/bottleneck_configurator');
            await import('app/performance_analysis');
            await import('app/component_image_button');
            await import('app/ui/offer_template');
            await import('app/ui/errors');
            await import('app/pc_build_configuration');
            break;
        case 'component.filter':
            await import('app/ai_product_field');
            await import('app/component_scores_section');
            await import('app/new_product_filters');
            break;
        case 'component.details':
            await import('app/ui/offer_template');
            await import('app/component_specifications');
            await import('app/component_circle_scores_section');
            break;
        default:
    }
})();