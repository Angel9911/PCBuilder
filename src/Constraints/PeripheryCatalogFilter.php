<?php

namespace App\Constraints;

/**
 * Final, declarative config for PERIPHERALS.
 * - Which fields are filterable per category (mouse/keyboard), how to source them, and how to aggregate them.
 * - Card "key specs" per category.
 * - Brand & connection_type are included with their required JOIN/JUNCTION metadata.
 */
class PeripheryCatalogFilter
{

    public const CATEGORIES = [
        'mouse' => [
            'table' => 'mouse',
            'filters' => [
                'brand' => [
                    'label'  => 'Brand',
                    'kind'   => 'checkbox',
                    'source' => 'join',
                    'expr'   => 'pb.name',
                    'enabled'=> true,
                    'joins'  => [
                        ['type'=>'LEFT','table'=>'peripherals','alias'=>'p','on'=>'p.id = t.peripheral_id'],
                        ['type'=>'LEFT','table'=>'brands','alias'=>'pb','on'=>'pb.id = p.brand_id'],
                    ],
                ],
                'connection_type' => [
                    'label'  => 'Connection Type',
                    'kind'   => 'checkbox',
                    'source' => 'junction',
                    'expr'   => 'c.name',
                    'enabled'=> true,
                    'joins'  => [
                        ['type'=>'INNER','table'=>'peripherals','alias'=>'p','on'=>'p.id = t.peripheral_id'],
                    ],
                    'junction' => [
                        ['type'=>'INNER','table'=>'peripheral_connections','alias'=>'pc','on'=>'pc.peripheral_id = p.id'],
                        ['type'=>'INNER','table'=>'periphery_connections','alias'=>'c','on'=>'c.id = pc.connection_id'],
                    ],
                ],
                // NEW: show as range (min/max) + distinct ticks
                'dpi' => [
                    'label'     => 'DPI',
                    'kind'      => 'range_span',
                    'source'    => 'column',
                    'min_expr'  => 't.dpi_min',
                    'max_expr'  => 't.dpi_max',
                    'enabled'   => true,
                    'max_distinct' => 50,
                ],
                // NEW: include button counts as checkboxes
                'button_count' => [
                    'label'   => 'Button Count',
                    'kind'    => 'checkbox',
                    'source'  => 'column',
                    'expr'    => 't.button_count',
                    'enabled' => true,
                ],
                // NEW: boolean as Yes/No
                'has_lighting' => [
                    'label'   => 'Lighting',
                    'kind'    => 'radio',   // Yes/No
                    'source'  => 'column',
                    'expr'    => 't.has_lighting',
                    'enabled' => true,
                ],
                // Keep but disable for now
                'programmable_buttons' => [
                    'label'   => 'Programmable Buttons',
                    'kind'    => 'radio',
                    'source'  => 'column',
                    'expr'    => 't.programmable_buttons',
                    'enabled' => false,
                ],
                'response_time_ms' => [
                    'label'   => 'Response Time (ms)',
                    'kind'    => 'range',
                    'source'  => 'column',
                    'expr'    => 't.response_time_ms',
                    'enabled' => false,
                    'max_distinct' => 50,
                ],
                'weight_grams' => [
                    'label'   => 'Weight',
                    'kind'    => 'range',
                    'source'  => 'column',
                    'expr'    => 't.weight_grams',
                    'enabled' => true,
                    'max_distinct' => 50,
                ],
                'sensor' => [
                    'label'   => 'Sensor',
                    'kind'    => 'checkbox',
                    'source'  => 'column',
                    'expr'    => 't.sensor',
                    'enabled' => true,
                ],
                'color' => [
                    'label'   => 'Color',
                    'kind'    => 'checkbox',
                    'source'  => 'column',
                    'expr'    => 't.color',
                    'enabled' => true,
                ],
            ],
        ],

        'keyboard' => [
            'table' => 'keyboard',
            'filters' => [
                // Keep brand + connection_type (checkbox)
                'brand' => [
                    'label'   => 'Brand',
                    'kind'    => 'checkbox',
                    'source'  => 'join',
                    'expr'    => 'pb.name',
                    'enabled' => true,
                    'joins'   => [
                        ['type'=>'LEFT','table'=>'peripherals','alias'=>'p','on'=>'p.id = t.peripheral_id'],
                        ['type'=>'LEFT','table'=>'brands','alias'=>'pb','on'=>'pb.id = p.brand_id'],
                    ],
                ],
                'connection_type' => [
                    'label'   => 'Connection Type',
                    'kind'    => 'checkbox',
                    'source'  => 'junction',
                    'expr'    => 'c.name',
                    'enabled' => true,
                    'joins'   => [
                        ['type'=>'INNER','table'=>'peripherals','alias'=>'p','on'=>'p.id = t.peripheral_id'],
                    ],
                    'junction'=> [
                        ['type'=>'INNER','table'=>'peripheral_connections','alias'=>'pc','on'=>'pc.peripheral_id = p.id'],
                        ['type'=>'INNER','table'=>'periphery_connections','alias'=>'c','on'=>'c.id = pc.connection_id'],
                    ],
                ],

                // ✅ Enabled — radios (Yes/No)
                'is_mechanical' => [
                    'label'   => 'Mechanical',
                    'kind'    => 'radio',
                    'source'  => 'column',
                    'expr'    => 't.is_mechanical',
                    'enabled' => true,
                ],
                'has_lighting' => [
                    'label'   => 'Lighting',
                    'kind'    => 'radio',
                    'source'  => 'column',
                    'expr'    => 't.has_lighting',
                    'enabled' => true,
                ],
                'anti_ghosting' => [
                    'label'   => 'Anti-Ghosting',
                    'kind'    => 'radio',
                    'source'  => 'column',
                    'expr'    => 't.anti_ghosting',
                    'enabled' => true,
                ],
                'waterproof' => [
                    'label'   => 'Waterproof',
                    'kind'    => 'radio',
                    'source'  => 'column',
                    'expr'    => 't.waterproof',
                    'enabled' => true,
                ],
                'has_cyrillic' => [
                    'label'   => 'Cyrillic (БГ)',
                    'kind'    => 'radio',
                    'source'  => 'column',
                    'expr'    => 't.has_cyrillic',
                    'enabled' => true,
                ],

                // ✅ Enabled — checkboxes
                'switch_type' => [
                    'label'   => 'Switch Type',
                    'kind'    => 'checkbox',
                    'source'  => 'column',
                    'expr'    => 't.switch_type',
                    'enabled' => true,
                ],
                'form_factor' => [
                    'label'   => 'Form Factor',
                    'kind'    => 'checkbox',
                    'source'  => 'column',
                    'expr'    => 't.form_factor',
                    'enabled' => true,
                ],
                'lighting_type' => [
                    'label'   => 'Lighting Type',
                    'kind'    => 'checkbox',
                    'source'  => 'column',
                    'expr'    => 't.lighting_type',
                    'enabled' => true,
                ],
                'color' => [
                    'label'   => 'Color',
                    'kind'    => 'checkbox',
                    'source'  => 'column',
                    'expr'    => 't.color',
                    'enabled' => true,
                ],

                // ⛔ Disabled (kept for future use)
                'cable_length_meters' => [
                    'label'   => 'Cable Length',
                    'kind'    => 'range',
                    'source'  => 'column',
                    'expr'    => 't.cable_length_meters',
                    'enabled' => false,
                    'max_distinct' => 50,
                ],
                'layout' => [
                    'label'   => 'Layout',
                    'kind'    => 'checkbox',
                    'source'  => 'column',
                    'expr'    => 't.layout',
                    'enabled' => false,
                ],
                'has_macro_keys' => [
                    'label'   => 'Macro Keys',
                    'kind'    => 'radio',
                    'source'  => 'column',
                    'expr'    => 't.has_macro_keys',
                    'enabled' => false,
                ],
                'has_multimedia_keys' => [
                    'label'   => 'Multimedia Keys',
                    'kind'    => 'radio',
                    'source'  => 'column',
                    'expr'    => 't.has_multimedia_keys',
                    'enabled' => false,
                ],
            ],
        ],
        'headset' => [
            'table' => 'headset',
            'filters' => [
                'brand' => [
                    'label'   => 'Brand',
                    'kind'    => 'checkbox',
                    'source'  => 'join',
                    'expr'    => 'pb.name',
                    'enabled' => true,
                    'joins'   => [
                        ['type'=>'LEFT','table'=>'peripherals','alias'=>'p','on'=>'p.id = t.peripheral_id'],
                        ['type'=>'LEFT','table'=>'brands','alias'=>'pb','on'=>'pb.id = p.brand_id'],
                    ],
                ],
                'connection_type' => [
                    'label'   => 'Connection Type',
                    'kind'    => 'checkbox',
                    'source'  => 'junction',
                    'expr'    => 'c.name',
                    'enabled' => true,
                    'joins'   => [
                        ['type'=>'INNER','table'=>'peripherals','alias'=>'p','on'=>'p.id = t.peripheral_id'],
                    ],
                    'junction'=> [
                        ['type'=>'INNER','table'=>'peripheral_connections','alias'=>'pc','on'=>'pc.peripheral_id = p.id'],
                        ['type'=>'INNER','table'=>'periphery_connections','alias'=>'c','on'=>'c.id = pc.connection_id'],
                    ],
                ],
                'design' => [
                    'label'   => 'Design / Type',
                    'kind'    => 'checkbox',
                    'source'  => 'column',
                    'expr'    => 't.design',
                    'enabled' => true,
                ],
                'noise_cancelling' => [
                    'label'   => 'Noise Cancelling',
                    'kind'    => 'radio',
                    'source'  => 'column',
                    'expr'    => 't.noise_cancelling',
                    'enabled' => true,
                ],
                'has_microphone' => [
                    'label'   => 'Microphone',
                    'kind'    => 'radio',
                    'source'  => 'column',
                    'expr'    => 't.has_microphone',
                    'enabled' => true,
                ],
                'battery_life_hours' => [
                    'label'   => 'Battery Life (hours)',
                    'kind'    => 'range',
                    'source'  => 'column',
                    'expr'    => 't.battery_life_hours',
                    'enabled' => true,
                    'max_distinct' => 50,
                ],
                'color' => [
                    'label'   => 'Color',
                    'kind'    => 'checkbox',
                    'source'  => 'column',
                    'expr'    => 't.color',
                    'enabled' => true,
                ],
            ],
        ],
        'webcam' => [
            'table' => 'webcam',
            'filters' => [
                'brand' => [
                    'label'   => 'Brand',
                    'kind'    => 'checkbox',
                    'source'  => 'join',
                    'expr'    => 'pb.name',
                    'enabled' => true,
                    'joins'   => [
                        ['type'=>'LEFT','table'=>'peripherals','alias'=>'p','on'=>'p.id = t.peripheral_id'],
                        ['type'=>'LEFT','table'=>'brands','alias'=>'pb','on'=>'pb.id = p.brand_id'],
                    ],
                ],
                'connection_type' => [
                    'label'   => 'Connection Type',
                    'kind'    => 'checkbox',
                    'source'  => 'junction',
                    'expr'    => 'c.name',
                    'enabled' => true,
                    'joins'   => [
                        ['type'=>'INNER','table'=>'peripherals','alias'=>'p','on'=>'p.id = t.peripheral_id'],
                    ],
                    'junction'=> [
                        ['type'=>'INNER','table'=>'peripheral_connections','alias'=>'pc','on'=>'pc.peripheral_id = p.id'],
                        ['type'=>'INNER','table'=>'periphery_connections','alias'=>'c','on'=>'c.id = pc.connection_id'],
                    ],
                ],
                'resolution' => [
                    'label'   => 'Resolution',
                    'kind'    => 'checkbox',
                    'source'  => 'column',
                    'expr'    => 't.resolution',
                    'enabled' => true,
                ],
                'autofocus' => [
                    'label'   => 'Autofocus',
                    'kind'    => 'radio',
                    'source'  => 'column',
                    'expr'    => 't.autofocus',
                    'enabled' => true,
                ],
                'built_in_mic' => [
                    'label'   => 'Microphone',
                    'kind'    => 'radio',
                    'source'  => 'column',
                    'expr'    => 't.built_in_mic',
                    'enabled' => true,
                ],
                'max_frame_rate_fps' => [
                    'label'   => 'Frame Rate (FPS)',
                    'kind'    => 'range',
                    'source'  => 'column',
                    'expr'    => 't.max_frame_rate_fps',
                    'enabled' => true,
                    'max_distinct' => 50,
                ],
                'mounting_options' => [
                    'label'   => 'Mount Type',
                    'kind'    => 'checkbox',
                    'source'  => 'column',
                    'expr'    => 't.mounting_options',
                    'enabled' => true,
                ],
                'color' => [
                    'label'   => 'Color',
                    'kind'    => 'checkbox',
                    'source'  => 'column',
                    'expr'    => 't.color',
                    'enabled' => true,
                ],
            ],
        ],
    ];
    /**
     * Optional normalizers you can reference in facets.
     * The SQL builder will substitute %s with the expression.
     */
    public const NORMALIZERS = [
        'upper_trim' => ['fn' => 'UPPER(TRIM(%s))'],
        'lower_trim' => ['fn' => 'LOWER(TRIM(%s))'],
        'caps_trim'  => ['fn' => 'INITCAP(TRIM(%s))'],
    ];

    public static function get(string $type): array
    {
        if (!isset(self::CATEGORIES[$type])) {
            throw new \InvalidArgumentException("Unknown periphery type: {$type}");
        }
        return self::CATEGORIES[$type];
    }

    public static function getFilters(string $type): array
    {
        return self::get($type)['filters'];
    }

    public static function getCardSpecs(string $type): array
    {
        return self::get($type)['card_specs'];
    }
}