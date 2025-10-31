<?php

namespace App\Constraints;

final class ComponentConstraints
{

    public static array $CPU_FILTERS_COMPONENT = [
        'card_specifications' => ['socket', 'power_wattage'],
        'main_specifications' => ['socket', 'core_count', 'boost_clock_ghz', 'power_wattage'],
        'general_specifications' => ['name', 'socket', 'chipset', 'power_wattage', 'core_count', 'includes_cooler', 'integrated_graphics', 'integrated_graphics_model', 'base_clock_ghz', 'boost_clock_ghz']
    ];
    public static array $CPU_FILTERS_COMPONENT_SCORES = ['gaming_score', 'future_proofing'];
    public static array $MOTHERBOARD_FILTERS_COMPONENT = [
        'card_specifications' => ['socket', 'chipset', 'memory_type', 'storage_interfaces', 'max_memory_supported'],
        'main_specifications' => [],
        'general_specifications' => [],
    ];
    public static array $GPU_FILTERS_COMPONENT = [
        'card_specifications' => ['power_wattage'],
        'main_specifications' => ['vram_gb', 'vram_type', 'memory_bus', 'power_wattage'],
        'general_specifications' => ['chip_manufacturer', 'series', 'vram_gb', 'vram_type', 'memory_bus', 'power_wattage', 'core_clock_mhz', 'interface', 'video_output', 'cooling_type', 'length_mm', 'power_connectors']
    ];
    public static array $GPU_FILTERS_COMPONENT_SCORES = ['gaming_score', 'efficiency'];
    public static array $PC_CASE_FILTERS_COMPONENT = [
        'card_specifications' => ['gpu_clearance_mm', 'max_cooler_height_mm	', 'psu_length_limit_mm'],
        'main_specifications' => ['form_factor','gpu_clearance_mm', 'psu_length_limit_mm', 'color'],
        'general_specifications' => ['form_factor', 'gpu_clearance_mm', 'gpu_clearance_mm', 'psu_length_limit_mm', 'color', 'dimensions_mm', 'side_panel_material', ''],
    ];
    public static array $PC_CASE_FILTERS_COMPONENT_SCORES = ['airflow_score', 'future_proofing'];
    public static array $PSU_FILTERS_COMPONENT = [
        'card_specifications' => ['power_wattage'],
        'main_specifications' => ['power_wattage', 'efficiency_rating', 'modular_type', 'form_factor'],
        'general_specifications' => ['brand', 'power_wattage', 'modular_type', 'efficiency_rating', 'length_mm', 'form_factor', 'fanless']
    ];
    public static array $PSU_FILTERS_COMPONENT_SCORES = ['efficiency', 'noise_level'];
    public static array $STORAGE_FILTERS_COMPONENT = [
        'card_specifications' => ['type', 'interface', 'capacity_gb'],
        'main_specifications' => ['type', 'capacity_gb', 'read_speed_mb_s', 'write_speed_mb_s'],
        'general_specifications' => ['type', 'capacity_gb', 'read_speed_mb_s', 'write_speed_mb_s', 'connector_type', 'form_factor', 'interface'],
    ];
    public static array $STORAGE_FILTERS_COMPONENT_SCORES = ['speed_score', 'longevity'];
    public static array $RAM_FILTERS_COMPONENT = [
        'card_specifications' => ['type', 'capacity_gb', 'max_xmp_speed'],
        'main_specifications' => ['type', 'capacity_gb', 'max_xmp_speed', 'cas_latency'],
        'general_specifications' => ['brand', 'type', 'capacity_gb', 'max_xmp_speed', 'cas_latency', 'timings', 'form_factor']
    ];
    public static array $RAM_FILTERS_COMPONENT_SCORES = ['gaming_score', 'productivity_score'];

    public static array $COMPONENT_LABELS = [
        'cpu' => 'Процесор',
        'cpu_cooling' => 'Охлаждане на Процесора',
        'gpu' => 'Видео Карта',
        'motherboard' => 'Дънна Платка',
        'ram' => 'Рам Памет',
        'storage' => 'Памет',
        'monitor' => 'Монитор',
        'pc_case' => 'PC Кутия',
        'psu' => 'Захранване',
    ];
}