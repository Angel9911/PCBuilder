<?php

/**
 * Returns the importmap for this application.
 *
 * - "path" is a path inside the asset mapper system. Use the
 *     "debug:asset-map" command to see the full list of paths.
 *
 * - "entrypoint" (JavaScript only) set to true for any module that will
 *     be used as an "entrypoint" (and passed to the importmap() Twig function).
 *
 * The "importmap:require" command can be used to add new entries to this file.
 */
return [
    '@hotwired/stimulus' => [
        'version' => '3.2.2',
    ],
    '@symfony/stimulus-bundle' => [
        'path' => './vendor/symfony/stimulus-bundle/assets/dist/loader.js',
    ],
    '@hotwired/turbo' => [
        'version' => '7.3.0',
    ],
    'app' => [
        'path' => './assets/app.js',
        'entrypoint' => true,
    ],
    'app/home_page' => [
        'path' => './assets/js/home_page.js',
    ],
    'app/pc_build_configuration' => [
        'path' => './assets/js/pc_build_configuration.js',
    ],
    'app/auth_login_registration' => [
        'path' => './assets/js/auth_login_registration.js',
    ],
    'app/ai_questionnaire' => [
        'path' => './assets/js/ai_questionnaire.js',
    ],
    'app/configurator' => [
        'path' => './assets/js/configurator.js',
    ],
    'app/configurator_summary' => [
        'path' => './assets/js/configurator_summary.js',
    ],
    'app/bottleneck_configurator' => [
        'path' => './assets/js/bottleneck_configurator.js',
    ],
    'app/performance_analysis' => [
        'path' => './assets/js/performance_analysis.js',
    ],
    'app/component_image_button' => [
        'path' => './assets/js/component_image_button.js',
    ],
    'app/ui/offer_template' => [
        'path' => './assets/js/ui/offer_template.js',
    ],
    'app/ui/errors' => [
        'path' => './assets/js/ui/errors.js',
    ],
    'app/new_product_filters' => [
        'path' => './assets/js/new_product_filters.js',
    ],
    'app/ai_product_field' => [
        'path' => './assets/js/ai_product_field.js',
    ],
    'app/component_scores_section' => [
        'path' => './assets/js/component_scores_section.js',
    ],
    'app/component_circle_scores_section' => [
        'path' => './assets/js/component_circle_scores_section.js',
    ],
    'app/component_specifications' => [
        'path' => './assets/js/component_specifications.js',
    ],
];
