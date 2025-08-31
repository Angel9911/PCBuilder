<?php

namespace App\Constraints;

final class ComponentCatalogFilter
{
    public const CATEGORIES = [
        'cpu' => [
            'table' => 'cpu',

            // We need components for brand and for "Series" derived from c.name
            'base_joins' => [
                ['type' => 'INNER', 'table' => 'components', 'alias' => 'c',  'on' => 'c.id = t.component_id'],
            ],

            'filters' => [
                // Brand (board/manufacturer brand tied to the component)
                'brand' => [
                    'label'   => 'Brand',
                    'kind'    => 'checkbox',
                    'source'  => 'join',
                    'expr'    => 'cb.name',
                    'enabled' => true,
                    'joins'   => [
                        ['type' => 'LEFT', 'table' => 'component_brands', 'alias' => 'cb', 'on' => 'cb.id = c.brand_id'],
                    ],
                ],

                // Series (Серия) — computed from components.name:
                //  - Intel Core i7-13700K -> "Intel Core i7"
                //  - AMD Ryzen 9 7950X   -> "AMD Ryzen 9"
                'series' => [
                    'label'   => 'Model',
                    'kind'    => 'checkbox',
                    'source'  => 'column', // still a column expression; we compute via regexp in expr
                    'expr'    =>
                        "COALESCE(" .
                        "NULLIF(substring(c.name from '^(Intel\\s+Core\\s+i[0-9]+)'), '')," .
                        "NULLIF(substring(c.name from '^(AMD\\s+Ryzen\\s+(?:3|5|7|9))'), '')," .
                        "NULLIF(substring(c.name from '^(AMD\\s+Threadripper)'), ''))",
                    'enabled' => true,
                ],

                // Socket
                'socket' => [
                    'label'   => 'Socket',
                    'kind'    => 'checkbox',
                    'source'  => 'column',
                    'expr'    => 't.socket',
                    'enabled' => true,
                ],

                // Supported Memory Type
                'memory_type' => [
                    'label'   => 'Supported Memory Type',
                    'kind'    => 'checkbox',
                    'source'  => 'column',
                    'expr'    => 't.memory_type',
                    'enabled' => true,
                ],

                // Includes cooler (Yes/No)
                'includes_cooler' => [
                    'label'   => 'Including Cooler',
                    'kind'    => 'radio',
                    'source'  => 'column',
                    'expr'    => 't.includes_cooler',
                    'enabled' => true,
                ],

                // Power (range)
                'power_wattage' => [
                    'label'        => 'Power Wattage',
                    'kind'         => 'range',
                    'source'       => 'column',
                    'expr'         => 't.power_wattage',
                    'enabled'      => true,
                    'max_distinct' => 50,
                ],

                // TDP (range)
                'tdp' => [
                    'label'        => 'TDP',
                    'kind'         => 'range',
                    'source'       => 'column',
                    'expr'         => 't.tdp',
                    'enabled'      => true,
                    'max_distinct' => 50,
                ],

                // (Future) Брой ядра / core_count — add when column exists
                'core_count' => [
                    'label'   => 'Брой ядра',
                    'kind'    => 'range',
                    'source'  => 'column',
                    'expr'    => 't.core_count', // placeholder – column doesn’t exist yet
                    'enabled' => false,          // disabled until you add the column
                ],
            ],
        ],
        'gpu' => [],
        'motherboard' => [
            'table' => 'motherboard',

            // Always join components (to reach component brand etc.)
            'base_joins' => [
                ['type' => 'INNER', 'table' => 'components', 'alias' => 'c',  'on' => 'c.id = t.component_id'],
            ],

            'filters' => [
                // Manufacturer / Brand (checkbox)
                'brand' => [
                    'label'   => 'Brand',
                    'kind'    => 'checkbox',
                    'source'  => 'join',
                    'expr'    => 'cb.name',
                    'enabled' => true,
                    'joins'   => [
                        ['type'=>'LEFT','table'=>'component_brands','alias'=>'cb','on'=>'cb.id = c.brand_id'],
                    ],
                ],

                // Chipset (checkbox) — column on motherboard
                'chipset' => [
                    'label'   => 'Chipset',
                    'kind'    => 'checkbox',
                    'source'  => 'column',
                    'expr'    => 't.chipset',
                    'enabled' => true,
                ],

                // Socket (checkbox) — column on motherboard
                'socket' => [
                    'label'   => 'Socket',
                    'kind'    => 'checkbox',
                    'source'  => 'column',
                    'expr'    => 't.socket',
                    'enabled' => true,
                ],

                // Form factor (checkbox)
                // t.form_factor_id → join to form_factors (adjust table/name if yours differs)
                'form_factor' => [
                    'label'   => 'Form Factor',
                    'kind'    => 'checkbox',
                    'source'  => 'join',
                    'expr'    => 'ff.name',
                    'enabled' => true,
                    'joins'   => [
                        ['type'=>'LEFT','table'=>'form_factors','alias'=>'ff','on'=>'ff.id = t.form_factor_id'],
                    ],
                ],

                // Supported memory type (checkbox)
                'memory_type' => [
                    'label'   => 'Supported Memory Type',
                    'kind'    => 'checkbox',
                    'source'  => 'column',
                    'expr'    => 't.memory_type',
                    'enabled' => true,
                ],

                // Max memory slots (range)
                'memory_slots' => [
                    'label'        => 'Max Memory Slots',
                    'kind'         => 'range',
                    'source'       => 'column',
                    'expr'         => 't.memory_slots',
                    'enabled'      => true,
                    'max_distinct' => 20,
                ],

                // Max memory supported (range)
                'max_memory_supported' => [
                    'label'        => 'Max Memory Supported (GB)',
                    'kind'         => 'range',
                    'source'       => 'column',
                    'expr'         => 't.max_memory_supported',
                    'enabled'      => true,
                    'max_distinct' => 50,
                ],

                // Ports (checkbox) — many-to-many via motherboard_usb_headers → usb_header_types
                // We expose the USB header type names as “Ports”
                'ports' => [
                    'label'   => 'Ports',
                    'kind'    => 'checkbox',
                    'source'  => 'junction',
                    'expr'    => 'uht.name',
                    'enabled' => true,
                    // base join already has components; add needed joins:
                    'joins' => [
                        // none required beyond base for this facet
                    ],
                    'junction' => [
                        ['type'=>'INNER','table'=>'motherboard_usb_headers','alias'=>'muh','on'=>'muh.motherboard_id = t.id'],
                        ['type'=>'INNER','table'=>'usb_header_types','alias'=>'uht','on'=>'uht.id = muh.usb_header_type_id'],
                    ],
                ],
            ],
        ],
        'ram' => [
            'table' => 'ram',

            // Always join components to reach brand
            'base_joins' => [
                ['type' => 'INNER', 'table' => 'components', 'alias' => 'c',  'on' => 'c.id = t.component_id'],
            ],

            'filters' => [
                // Manufacturer / Brand
                'brand' => [
                    'label'   => 'Brand',
                    'kind'    => 'checkbox',
                    'source'  => 'join',
                    'expr'    => 'cb.name',
                    'enabled' => true,
                    'joins'   => [
                        ['type' => 'LEFT', 'table' => 'component_brands', 'alias' => 'cb', 'on' => 'cb.id = c.brand_id'],
                    ],
                ],

                // Type (DDR4 / DDR5)
                'type' => [
                    'label'   => 'Type',
                    'kind'    => 'checkbox',
                    'source'  => 'column',
                    'expr'    => 't.type',
                    'enabled' => true,
                ],

                // Number of modules (1, 2, 4...)
                'modules' => [
                    'label'   => 'Modules',
                    'kind'    => 'checkbox',
                    'source'  => 'column',
                    'expr'    => 't.modules',
                    'enabled' => true,
                ],

                // Capacity (GB) – range + distinct ticks
                'capacity_gb' => [
                    'label'        => 'Capacity (GB)',
                    'kind'         => 'range',
                    'source'       => 'column',
                    'expr'         => 't.capacity_gb',
                    'enabled'      => true,
                    'max_distinct' => 50,
                ],

                // Frequency (MHz) – range + distinct ticks
                'speed_mhz' => [
                    'label'        => 'Frequency (MHz)',
                    'kind'         => 'range',
                    'source'       => 'column',
                    'expr'         => 't.speed_mhz',
                    'enabled'      => true,
                    'max_distinct' => 50,
                ],

                // --- Optional (kept for future use) ---
                'form_factor' => [
                    'label'   => 'Form Factor',
                    'kind'    => 'checkbox',
                    'source'  => 'column',
                    'expr'    => 't.form_factor',
                    'enabled' => false,
                ],
                'ecc' => [
                    'label'   => 'ECC',
                    'kind'    => 'radio',     // Yes/No
                    'source'  => 'column',
                    'expr'    => 't.ecc',
                    'enabled' => false,
                ],
                'registered' => [
                    'label'   => 'Registered (RDIMM)',
                    'kind'    => 'radio',     // Yes/No
                    'source'  => 'column',
                    'expr'    => 't.registered',
                    'enabled' => false,
                ],
            ],
        ],
        'storage' => [
            'table' => 'storage',

            // Always join components to reach brand
            'base_joins' => [
                ['type' => 'INNER', 'table' => 'components',        'alias' => 'c',  'on' => 'c.id = t.component_id'],
            ],

            'filters' => [
                // Manufacturer / Brand
                'brand' => [
                    'label'   => 'Brand',
                    'kind'    => 'checkbox',
                    'source'  => 'join',
                    'expr'    => 'cb.name',
                    'enabled' => true,
                    'joins'   => [
                        ['type' => 'LEFT', 'table' => 'component_brands', 'alias' => 'cb', 'on' => 'cb.id = c.brand_id'],
                    ],
                ],

                // Drive Type (e.g., SSD, HDD, NVMe SSD, SATA SSD)
                'type' => [
                    'label'   => 'Type',
                    'kind'    => 'checkbox',
                    'source'  => 'column',
                    'expr'    => 't.type',
                    'enabled' => true,
                ],

                // Capacity (GB)
                'capacity_gb' => [
                    'label'        => 'Capacity (GB)',
                    'kind'         => 'range',
                    'source'       => 'column',
                    'expr'         => 't.capacity_gb',
                    'enabled'      => true,
                    'max_distinct' => 50,
                ],

                // Read Speed (MB/s) — column not present yet; keep disabled
                'read_speed_mb_s' => [
                    'label'        => 'Read Speed (MB/s)',
                    'kind'         => 'range',
                    'source'       => 'column',
                    'expr'         => 't.read_speed_mb_s', // placeholder column (add later)
                    'enabled'      => false,
                    'max_distinct' => 50,
                ],

                // Write Speed (MB/s) — column not present yet; keep disabled
                'write_speed_mb_s' => [
                    'label'        => 'Write Speed (MB/s)',
                    'kind'         => 'range',
                    'source'       => 'column',
                    'expr'         => 't.write_speed_mb_s', // placeholder column (add later)
                    'enabled'      => false,
                    'max_distinct' => 50,
                ],

                // Interface (e.g., SATA, PCIe 4.0 x4, PCIe 3.0 x4)
                'interface' => [
                    'label'   => 'Interface',
                    'kind'    => 'checkbox',
                    'source'  => 'column',
                    'expr'    => 't.interface',
                    'enabled' => true,
                ],

                // Form Factor (e.g., 2.5", M.2 2280)
                'form_factor' => [
                    'label'   => 'Form Factor',
                    'kind'    => 'checkbox',
                    'source'  => 'column',
                    'expr'    => 't.form_factor',
                    'enabled' => true,
                ],

                // ---- Optional extras (keep off for now; uncomment if you want) ----
                // 'bus_type' => [
                //     'label'   => 'Bus Type',
                //     'kind'    => 'checkbox',
                //     'source'  => 'column',
                //     'expr'    => 't.bus_type',
                //     'enabled' => false,
                // ],
                // 'connector_type' => [
                //     'label'   => 'Connector Type',
                //     'kind'    => 'checkbox',
                //     'source'  => 'column',
                //     'expr'    => 't.connector_type',
                //     'enabled' => false,
                // ],
                // 'pcie_version' => [
                //     'label'   => 'PCIe Version',
                //     'kind'    => 'checkbox',
                //     'source'  => 'column',
                //     'expr'    => 't.pcie_version',
                //     'enabled' => false,
                // ],
                // 'lane_count' => [
                //     'label'        => 'PCIe Lanes',
                //     'kind'         => 'range',
                //     'source'       => 'column',
                //     'expr'         => 't.lane_count',
                //     'enabled'      => false,
                //     'max_distinct' => 20,
                // ],
                // 'nvme' => [
                //     'label'   => 'NVMe',
                //     'kind'    => 'radio',   // Yes/No
                //     'source'  => 'column',
                //     'expr'    => 't.nvme',
                //     'enabled' => false,
                // ],
            ],
        ]
    ];

    public static function get(string $type): array
    {
        if (!isset(self::CATEGORIES[$type])) {
            throw new \InvalidArgumentException("Unknown component type: {$type}");
        }
        return self::CATEGORIES[$type];
    }
}