<?php

namespace App\Constraints;

final class PeripheryConstraints
{
    public static array $MOUSE_KEY_SPECIFICATIONS = ['connection_type', 'dpi_max', 'color'];
    public static array $MOUSE_FILTERS_PERIPHERY = ['sensor', 'dpi_min', 'dpi_max', 'response_time_ms', 'programmable_buttons', 'weight_grams', 'color'];
    public static array $KEYBOARD_FILTERS_PERIPHERY = ['switch_type', 'is_mechanical', 'form_factor', 'lighting_type', 'has_multimedia_keys', 'anti_ghosting', 'waterproof', 'color'];
    public static array $KEYBOARD_KEY_SPECIFICATIONS = ['connection_type', 'switch_type', 'lighting_type'];

    public static array $HEADSET_KEY_SPECIFICATIONS = ['design', 'noise_cancelling', 'has_microphone'];
    public static array $HEADSET_FILTERS_PERIPHERY = ['sensor', 'dpi_min', 'dpi_max', 'response_time_ms', 'programmable_buttons', 'weight_grams', 'color'];

    public static array $WEBCAM_KEY_SPECIFICATIONS = ['resolution', 'autofocus', 'built_in_mic'];
    public static array $WEBCAM_FILTERS_PERIPHERY = ['sensor', 'dpi_min', 'dpi_max', 'response_time_ms', 'programmable_buttons', 'weight_grams', 'color'];

    public static array $PERIPHERY_LABELS = [
        'mouse' => 'Мишка',
        'keyboard' => 'Клавиатура',
    ];

    public static array $PERIPHERY_ICONS = [
        'mouse' => [
            [
                "label" => "Dpi Max",
                "icon" => "flash_red_icon_original.svg",
                "bgClass" => "bg-red-100",
                "textClass" => "text-red-600"
            ],
            [
                "label" => "Connection Type",
                "icon" => "wifi_blue_icon.svg",
                "bgClass" => "bg-blue-100",
                "textClass" => "text-blue-600"
            ],
            [
                "label" => "Color",
                "icon" => "rgb_backlight.svg",
                "bgClass" => "bg-purple-100",
                "textClass" => "text-purple-600"
            ]
        ],
        'keyboard' => [
            [
                "label" => "Switch Type",
                "icon" => "joystick_icon_cpy.svg", // You can export this Lucide icon as SVG or rename it as needed
                "bgClass" => "bg-blue-100",
                "textClass" => "text-blue-600"
            ],
            [
                "label" => "Lighting Type",
                "icon" => "rgb_backlight.svg", // You can use the palette Lucide icon SVG
                "bgClass" => "bg-purple-100",
                "textClass" => "text-purple-600"
            ],
            [
                "label" => "Connection Type",
                "icon" => "cabel_icon.svg", // You can use the cable Lucide icon SVG
                "bgClass" => "bg-emerald-100",
                "textClass" => "text-emerald-600"
            ]
        ],
        'webcam' => [
            [
                "label" => "Resolution",
                "icon" => "4k_resolution_icon.png",
                "bgClass" => "bg-red-100",
                "textClass" => "text-red-600"
            ],
            [
                "label" => "Autofocus",
                "icon" => "autofocus_icon.png",
                "bgClass" => "bg-blue-100",
                "textClass" => "text-blue-600"
            ],
            [
                "label" => "Built In Mic",
                "icon" => "microphone_purple_icon.png",
                "bgClass" => "bg-purple-100",
                "textClass" => "text-purple-600"
            ]
        ],
        'headset' => [
            [
                "label" => "Design",
                "icon" => "headphones_icon.png",
                "bgClass" => "bg-blue-100",  //text-emerald-600
                "textClass" => "text-blue-600"  //text-emerald-600
            ],
            [
                "label" => "Noise Cancelling",
                "icon" => "shield_icon.png",
                "bgClass" => "bg-amber-100",
                "textClass" => "text-amber-600"
            ],
            [
                "label" => "Has Microphone",
                "icon" => "microphone_green_icon.png",
                "bgClass" => "bg-cyan-100",
                "textClass" => "text-cyan-600"
            ]
        ]
    ];

}