<?php

namespace App\Constraints;

final class CacheConstraints
{
    public static string $COMPLETED_PC_CONFIGURATION_KEY = 'completed_pc_configurations';
    public static string $PC_CONFIGURATION_KEY = 'pc_configurations';
    public static string $OFFERS_COMPONENT_KEY = 'vendors_offers_component';
    public static string $COMPONENT_KEY = 'components';
    public static string $PERIPHERY_KEY = 'peripherals';
    public static string $COMPONENT_TYPE_FILTER_KEY = 'components_filters';
    public static string $PERIPHERY_TYPE_FILTER_KEY = 'peripherals_filters';
    public static string $BOTTLENECK_CALCULATION = 'bottleneck_calculation';

    public static function getProductCacheKeyByProductType(string $type): ?string
    {
        return match (strtolower($type)) {
            'components'  => self::$COMPONENT_KEY,
            'peripherals' => self::$PERIPHERY_KEY,
            default       => null,
        };
    }

    public static function getProductFiltersCacheKeyByProductType(string $type): ?string
    {
        return match (strtolower($type)) {
            'components'  => self::$COMPONENT_TYPE_FILTER_KEY,
            'peripherals' => self::$PERIPHERY_TYPE_FILTER_KEY,
            default       => null,
        };
    }
}