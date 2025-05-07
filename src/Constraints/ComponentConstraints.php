<?php

namespace App\Constraints;

final class ComponentConstraints
{

    public static array $CPU_FILTERS_COMPONENT = ['socket', 'power_wattage'];
    public static array $MOTHERBOARD_FILTERS_COMPONENT = ['socket', 'chipset', 'memory_type', 'storage_interfaces', 'max_memory_supported'];
    public static array $GPU_FILTERS_COMPONENT = ['power_wattage'];
    public static array $PC_CASE_FILTERS_COMPONENT = ['gpu_clearance_mm', 'max_cooler_height_mm	', 'psu_length_limit_mm'];
    public static array $PSU_FILTERS_COMPONENT = ['power_wattage'];
    public static array $STORAGE_FILTERS_COMPONENT = ['type', 'interface', 'capacity_gb'];
    public static array $RAM_FILTERS_COMPONENT = ['type', 'capacity_gb', 'speed_mhz'];
}