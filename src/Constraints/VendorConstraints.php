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
        ]
    ];
}