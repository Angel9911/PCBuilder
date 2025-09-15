<?php

namespace App\Constraints;

final class ComponentCatalogFilter
{
    public const CATEGORIES = [
        'cpu' => [
            'table' => 'cpu',
            'slug_expr'  => 'c.slugify_name',

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
                    'label'   => 'Core count',
                    'kind'    => 'range',
                    'source'  => 'column',
                    'expr'    => 't.core_count', // placeholder – column doesn’t exist yet
                    'enabled' => false,          // disabled until you add the column
                ],
            ],
        ],
        'gpu' => [
            'table' => 'gpu',
            'slug_expr'  => 'c.slugify_name',
            // We need components for brand + name-based "series"
            'base_joins' => [
                ['type' => 'INNER', 'table' => 'components', 'alias' => 'c', 'on' => 'c.id = t.component_id'],
            ],

            'filters' => [
                // Brand (board AIB brand stored on the components row; e.g., MSI, ASUS, PNY, …)
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

                // Chip manufacturer (GPU silicon vendor – NVIDIA / AMD)
                'chip_manufacturer' => [
                    'label'   => 'Chip Manufacturer',
                    'kind'    => 'checkbox',
                    'source'  => 'join',
                    'expr'    => 'cm.name',
                    'enabled' => true,
                    'joins'   => [
                        ['type' => 'LEFT', 'table' => 'chip_makers', 'alias' => 'cm', 'on' => 'cm.id = t.chip_maker_id'],
                    ],
                ],

                // Series (computed from components.name, removing the vendor prefix)
                //  "NVIDIA GeForce RTX 4090" -> "GeForce RTX 4090"
                //  "AMD Radeon RX 6700 XT"   -> "Radeon RX 6700 XT"
                'series' => [
                    'label'   => 'Series',
                    'kind'    => 'checkbox',
                    'source'  => 'column',
                    'expr'    => "regexp_replace(c.name, '^(NVIDIA|AMD)\\s+', '')",
                    'enabled' => true,
                ],

                // Video output types (HDMI 2.1, DP 1.4a, …) via the m:n table
                'video_output' => [
                    'label'   => 'Interface Type',
                    'kind'    => 'checkbox',
                    'source'  => 'junction',
                    'expr'    => 'vot.name',    // we’ll compare by type name
                    'enabled' => true,
                    'junction' => [
                        // chain: gpu -> gpu_video_outputs -> video_output_types
                        ['type'=>'LEFT', 'table' => 'gpu_video_outputs', 'alias' => 'gvo', 'on' => 'gvo.gpu_id = t.id'],
                        ['type'=>'LEFT', 'table' => 'video_output_types', 'alias' => 'vot', 'on' => 'vot.id = gvo.output_type_id'],
                    ],
                ],

                // VRAM capacity (GB)
                'vram_gb' => [
                    'label'   => 'Memory Capacity (GB)',
                    'kind'    => 'range',
                    'source'  => 'column',
                    'expr'    => 't.vram_gb',
                    'enabled' => true,
                ],

                // VRAM type (GDDR6X, GDDR6, …)
                'vram_type' => [
                    'label'   => 'Memory Type',
                    'kind'    => 'checkbox',
                    'source'  => 'column',
                    'expr'    => 't.vram_type',
                    'enabled' => true,
                ],

                // Card length (mm)
                'length_mm' => [
                    'label'   => 'Length (mm)',
                    'kind'    => 'range',
                    'source'  => 'column',
                    'expr'    => 't.length_mm',
                    'enabled' => true,
                ],

                // Board power
                'power_wattage' => [
                    'label'   => 'Power (W)',
                    'kind'    => 'range',
                    'source'  => 'column',
                    'expr'    => 't.power_wattage',
                    'enabled' => true,
                ],

                // Core/boost clock (MHz)
                'core_clock_mhz' => [
                    'label'   => 'Frequency (MHz)',
                    'kind'    => 'range',
                    'source'  => 'column',
                    'expr'    => 't.core_clock_mhz',
                    'enabled' => true,
                ],

                // (Optional toggles you can enable later)
                'pcie_version' => [
                    'label'   => 'PCIe Version',
                    'kind'    => 'checkbox',
                    'source'  => 'column',
                    'expr'    => 't.pcie_version',
                    'enabled' => false,
                ],
                'slot_width' => [
                    'label'   => 'Slot Width',
                    'kind'    => 'range',
                    'source'  => 'column',
                    'expr'    => 't.slot_width',
                    'enabled' => false,
                ],
                'cooling_type' => [
                    'label'   => 'Cooling',
                    'kind'    => 'checkbox',
                    'source'  => 'column',
                    'expr'    => 't.cooling_type',
                    'enabled' => false,
                ],
                'tdp' => [
                    'label'   => 'TDP',
                    'kind'    => 'range',
                    'source'  => 'column',
                    'expr'    => 't.tdp',
                    'enabled' => false,
                ],
            ],
        ],
        'motherboard' => [
            'table' => 'motherboard',
            'slug_expr'  => 'c.slugify_name',

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
            'slug_expr'  => 'c.slugify_name',
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
            'slug_expr'  => 'c.slugify_name',
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
                'connector_type' => [
                    'label'   => 'Connector Type',
                    'kind'    => 'checkbox',
                    'source'  => 'column',
                    'expr'    => 't.connector_type',
                    'enabled' => false,
                ],

                // ---- Optional extras (keep off for now; uncomment if you want) ----
                // 'bus_type' => [
                //     'label'   => 'Bus Type',
                //     'kind'    => 'checkbox',
                //     'source'  => 'column',
                //     'expr'    => 't.bus_type',
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
        ],
        'psu' => [
            'table' => 'psu',
            'slug_expr'  => 'c.slugify_name',
            // join components mainly to expose brand via component_brands
            'base_joins' => [
                ['type' => 'INNER', 'table' => 'components',       'alias' => 'c',  'on' => 'c.id = t.component_id'],
            ],

            'filters' => [
                // Brand (AIB/retail brand on components)
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

                // Power (W)
                'power_wattage' => [
                    'label'   => 'Power (W)',
                    'kind'    => 'range',
                    'source'  => 'column',
                    'expr'    => 't.power_wattage',
                    'enabled' => true,
                ],

                // Form factor (ATX, SFX, SFX-L, …) via FK
                'form_factor' => [
                    'label'   => 'Form Factor',
                    'kind'    => 'checkbox',
                    'source'  => 'join',
                    'expr'    => 'pf.name',
                    'enabled' => true,
                    'joins'   => [
                        ['type' => 'LEFT', 'table' => 'psu_form_factors', 'alias' => 'pf', 'on' => 'pf.id = t.form_factor_id'],
                    ],
                ],

                // Length (mm)
                'length_mm' => [
                    'label'   => 'Length (mm)',
                    'kind'    => 'range',
                    'source'  => 'column',
                    'expr'    => 't.length_mm',
                    'enabled' => true,
                ],

                // 80 PLUS rating (Gold, Platinum, Titanium, …)
                'efficiency_rating' => [
                    'label'   => 'Energy Efficiency',
                    'kind'    => 'checkbox',
                    'source'  => 'column',
                    'expr'    => 't.efficiency_rating',
                    'enabled' => true,
                ],

                // Modular type (Fully Modular / Semi-Modular / Non-Modular)
                'modular_type' => [
                    'label'   => 'Module Type',
                    'kind'    => 'checkbox',
                    'source'  => 'column',
                    'expr'    => 't.modular_type',
                    'enabled' => true,
                ],

                // (Optional) Fanless: enable if you want a Yes/No toggle
                 'fanless' => [
                     'label'   => 'Fanless',
                     'kind'    => 'radio',
                     'source'  => 'column',
                     'expr'    => 't.fanless',
                     'enabled' => false,
                 ],
            ],
        ],
        'pc_case' => [
            'table' => 'pc_case',
            'slug_expr'  => 'c.slugify_name',
            // Join components to expose brand (through component_brands)
            'base_joins' => [
                ['type' => 'INNER', 'table' => 'components', 'alias' => 'c', 'on' => 'c.id = t.component_id'],
            ],

            'filters' => [
                // Brand (from components.brand_id)
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

                // PC Case format (ATX, mATX, ITX, …) via junction pc_case_form_factors → form_factors
                'form_factor' => [
                    'label'   => 'PC Case Format',
                    'kind'    => 'checkbox',
                    'source'  => 'junction',
                    'expr'    => 'ff.name',
                    'enabled' => true,
                    'junction'=> [
                        // t (pc_case) → pc_case_form_factors (pcf) → form_factors (ff)
                        ['type' => 'INNER', 'table' => 'pc_case_form_factors', 'alias' => 'pcf', 'on' => 'pcf.pc_case_id = t.id'],
                        ['type' => 'INNER', 'table' => 'form_factors',         'alias' => 'ff',  'on' => 'ff.id = pcf.form_factor_id'],
                    ],
                ],

                // Color
                'color' => [
                    'label'   => 'Color',
                    'kind'    => 'checkbox',
                    'source'  => 'column',
                    'expr'    => 't.color',
                    'enabled' => true,
                ],

                // Max GPU length (mm)
                'gpu_clearance_mm' => [
                    'label'   => 'Max GPU Length (mm)',
                    'kind'    => 'range',
                    'source'  => 'column',
                    'expr'    => 't.gpu_clearance_mm',
                    'enabled' => true,
                ],

                // Max CPU cooler height (mm)
                'max_cooler_height_mm' => [
                    'label'   => 'Max CPU Cooler Height (mm)',
                    'kind'    => 'range',
                    'source'  => 'column',
                    'expr'    => 't.max_cooler_height_mm',
                    'enabled' => true,
                ],

                // Front panel outputs (USB types) via junction pc_case_usb_types → usb_header_types
                'front_panel_output' => [
                    'label'   => 'Front Panel Output',
                    'kind'    => 'checkbox',
                    'source'  => 'junction',
                    'expr'    => 'uht.name',
                    'enabled' => true,
                    'junction'=> [
                        // t (pc_case) → pc_case_usb_types (pcu) → usb_header_types (uht)
                        ['type' => 'INNER', 'table' => 'pc_case_usb_types', 'alias' => 'pcu', 'on' => 'pcu.pc_case_id = t.id'],
                        ['type' => 'INNER', 'table' => 'usb_header_types',  'alias' => 'uht', 'on' => 'uht.id = pcu.usb_type_id'],
                    ],
                ],

                // Length (mm) – first number before 'x', keep dot, strip other chars, ceil, cast to int
                'length_mm' => [
                    'label'   => 'Length (mm)',
                    'kind'    => 'range',
                    'source'  => 'column',
                    'expr'    =>
                        "CEIL((" .
                        "NULLIF(regexp_replace(split_part(t.dimensions_mm, 'x', 1), '[^0-9\\.]', '', 'g'), '')" .
                        ")::numeric)::int",
                    'enabled' => true,
                ],

                // Width (mm) – second number
                'width_mm' => [
                    'label'   => 'Width (mm)',
                    'kind'    => 'range',
                    'source'  => 'column',
                    'expr'    =>
                        "CEIL((" .
                        "NULLIF(regexp_replace(split_part(t.dimensions_mm, 'x', 2), '[^0-9\\.]', '', 'g'), '')" .
                        ")::numeric)::int",
                    'enabled' => true,
                ],

                // Height (mm) – third number
                'height_mm' => [
                    'label'   => 'Height (mm)',
                    'kind'    => 'range',
                    'source'  => 'column',
                    'expr'    =>
                        "CEIL((" .
                        "NULLIF(regexp_replace(split_part(t.dimensions_mm, 'x', 3), '[^0-9\\.]', '', 'g'), '')" .
                        ")::numeric)::int",
                    'enabled' => true,
                ],
            ],
        ],
        'cpu_cooling' => [],
    ];

    public static function get(string $type): array
    {
        if (!isset(self::CATEGORIES[$type])) {
            throw new \InvalidArgumentException("Unknown component type: {$type}");
        }
        return self::CATEGORIES[$type];
    }
}