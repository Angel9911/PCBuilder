// public/js/app.js
import {createIcons, icons } from 'lucide';

// ✅ Initialize Lucide icons globally
document.addEventListener('DOMContentLoaded', () => {
    createIcons({ icons }); // replaces all <i data-lucide="..."> with SVGs
});

const page = document.body.dataset.page;

(async () => {
    switch (page) {
        case 'home':
            await import('app/home_page');
            await import('app/ai_questionnaire');
            await import('app/auth_login_registration');
            await import('app/home/hero');
            await import('app/home/howItWorks');
            await import('app/home/completedBuilds');
            await import('app/home/productFinder');
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
            /*await import('app/recommendation_section');*/
            break;
        case 'component.details':
            await import('app/ui/rating_modal');
            await import('app/ui/offer_template');
            await import('app/component_specifications');
            await import('app/component_circle_scores_section');
            break;
        case 'completed.build':
            await import('app/ui/ai_config_form');
            /*await import('app/recommendation_section');*/
            break;
        case 'completed.build.details':
            await import('app/ui/rating_modal');
            break;
        default:
    }
})();