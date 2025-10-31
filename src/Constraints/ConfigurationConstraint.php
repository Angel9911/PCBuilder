<?php

namespace App\Constraints;

final class ConfigurationConstraint
{
    public static array $AVAILABLE_MANDATORY_PC_COMPONENTS = [
        'cpu' => 'CPU',
        'cpu_cooling' => 'CPU Cooling',
        'motherboard' => 'Motherboard',
        'gpu' => 'GPU',
        'memory' => 'Memory',
        'ram' => 'RAM',
        'storage' => 'Storage',
        'psu' => 'PSU',
        'pc_case' => 'PC Case',
        'monitor' => 'Monitor',
    ];

    public static array $AI_FINDER_SECTION_REQUIREMENTS = [
        'budget_focused' => [
            'Balanced',
            'Budget Focused',
            'Performance Focused',
        ],
        'primary_use' => [
            'Gaming',
            'Work Productivity',
            'Content Creation',
            'General Use'
        ],
        'specific_requirement' => [],
    ];

    public static array $AVAILABLE_PERIPHERAL_COMPONENTS = [ 'mouse', 'keyboard', 'webcam', 'headset', 'microphone'];
    public static array $AVAILABLE_MANDATORY_PC_COMPONENTS_IDS = ['cpu_id', 'cpu_cooling_id', 'motherboard_id', 'gpu_id', 'ram_id', 'storage_id', 'psu_id', 'pc_case_id', 'monitor_id'];
    public static array $BOTTLENECK_REQUIRED_PC_COMPONENTS = ['cpu', 'gpu'];
    public static array $FPS_REQUIRED_PC_COMPONENTS = ['cpu', 'gpu', 'ram'];
    public static array $AVAILABLE_OPTIONAL_PC_COMPONENTS = ['network_card', 'sound_card'];
    public static array $AVAILABLE_OPTIONAL_PC_COMPONENTS_IDS = ['network_card_id', 'sound_card_id'];

    public static function getProductType(string $type): ?string
    {
        if (array_key_exists($type, self::$AVAILABLE_MANDATORY_PC_COMPONENTS)) {
            return 'components';
        }

        if (in_array($type, self::$AVAILABLE_PERIPHERAL_COMPONENTS, true)) {
            return 'peripherals';
        }

        return null;
    }

    //TODO: ONLY FOR TESTING ! ! !
    public static array $PRODUCT_TEST_MAIN_IMAGES = [
        'mouse' => 'https://images.pexels.com/photos/2115256/pexels-photo-2115256.jpeg',
        'keyboard' => 'https://images.pexels.com/photos/2115257/pexels-photo-2115257.jpeg',
        'headset' => 'https://images.pexels.com/photos/3394650/pexels-photo-3394650.jpeg', // modern gaming headset on desk
        'webcam' => 'https://cdn.pixabay.com/photo/2016/02/24/12/30/camera-1219748_640.jpg', // clamp-mount webcam on monitor (Pixabay, free to use)
    ];
}