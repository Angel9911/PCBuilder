<?php

namespace App\Constraints;

final class VendorConstraints
{
    public static array $vendorsArray = [
        'Ardes' => [
            'product_name_style' => 'h1',
            'product_price_style' => '#price-tag',
            'product_status_style' => '.availability-check',
            'product_logo_style' => '.a-logo img'
            ],
        'Desktop' => [
            'product_name_style' => 'h1',
            'product_price_style' => '.price',
            'product_status_style' => '.offer-message-success',//offer-message
            'product_logo_style' => '#logo img'
        ],
        'Plasico' => [
             'product_name_style' => '.details-heading h1',
            'product_price_style' => '.prod-price .prices .price',
            'product_status_style' => '.sprp.available',
            'product_logo_style' => '#logo img'
        ],
        'JAR Computers' => [
            'product_name_style' => '#product_name h1',
            'product_price_style' => '.price-product',
            'product_status_style' => '.av-label',
            'product_logo_style' => '#logo .logo-image img' // Extract website logo
        ],
        'Ozone' => [
            'product_name_style' => 'h1[itemprop="name"]', // Extracts the product name
            'product_price_style' => '.regular-price .price', // Extracts the main price
            'product_status_style' => '.availability.in-stock', // Checks for stock availability
            'product_logo_style' => '.responsive-logo img' // Extracts website logo
        ]
    ];
}