<?php

namespace App\Constraints;

final class ConfigurationConstraint
{
    public static array $AVAILABLE_MANDATORY_PC_COMPONENTS = ['cpu', 'cpu_cooling', 'motherboard', 'gpu', 'memory', 'ram', 'storage', 'psu', 'pc_case', 'monitor'];
    public static array $AVAILABLE_PERIPHERAL_COMPONENTS = [ 'mouse', 'keyboard', 'webcam', 'headset', 'microphone'];
    public static array $AVAILABLE_MANDATORY_PC_COMPONENTS_IDS = ['cpu_id', 'cpu_cooling_id', 'motherboard_id', 'gpu_id', 'ram_id', 'storage_id', 'psu_id', 'pc_case_id', 'monitor_id'];
    public static array $BOTTLENECK_REQUIRED_PC_COMPONENTS = ['cpu', 'gpu'];
    public static array $FPS_REQUIRED_PC_COMPONENTS = ['cpu', 'gpu', 'ram'];
    public static array $AVAILABLE_OPTIONAL_PC_COMPONENTS = ['network_card', 'sound_card'];
    public static array $AVAILABLE_OPTIONAL_PC_COMPONENTS_IDS = ['network_card_id', 'sound_card_id'];

    public static function getProductType(string $type): ?string
    {
        if (in_array($type, self::$AVAILABLE_MANDATORY_PC_COMPONENTS, true)) {
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
    ];
}