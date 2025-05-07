<?php

namespace App\Service\Impl;

use App\Entity\Component;
use App\Repository\ComponentRepository;
use App\Service\ComponentService;
use Doctrine\DBAL\Exception;
use App\Constraints\ComponentConstraints;

class ComponentServiceImpl implements ComponentService
{
    private ComponentRepository $componentRepository;

    private static array $UNITS = [
        'power_wattage' => 'W',
        'length_mm' => 'mm',
        'capacity_gb' => 'GB',
        'speed_mhz' => 'MHz',
        'max_memory_supported' => 'GB',
        'gpu_clearance_mm' => 'mm',
        'max_cooler_height_mm' => 'mm',
        'psu_length_limit_mm' => 'mm',
    ];

    /**
     * @param ComponentRepository $componentRepository
     */
    public function __construct(ComponentRepository $componentRepository)
    {
        $this->componentRepository = $componentRepository;
    }


    public function getAllComponents(): array
    {
        return $this->componentRepository->findAllComponents();
    }

    public function getComponentsByType(string $type): array
    {
        return $this->componentRepository->findComponentsByType($type);
    }

    public function getCompatibleComponents(array $filterParams): array
    {
        try{

            //$compatibleParts = $this->componentRepository->findCompatibleComponents($filterParams);

            $compatibleParts = $this->componentRepository->findCompatibleComponents($filterParams);

            return $compatibleParts;

        }catch (\Exception $exception){
            return [];
        }
    }

    /**
     * @throws Exception
     */
    public function getComponentsDetailsByType(string $componentType, int $limit = 12, int $offset = 0): array
    {
        $components = $this->componentRepository->getComponentSpecs($componentType, $limit, $offset);

        $responseComponentsFilters = [
            'components' => [],
            'filters' => []  // we'll convert this to an array of objects below
        ];

        if (!empty($components)) {

            $specs = []; // Used for cardbox specifications

            $rawFilters = [];

            foreach ($components as $component) {

                // Add the component to response
                $componentFitlers = $this->getComponentTypesFilter();

                $filters = $componentFitlers[$component['component_type']] ?? [];

                $filteredData = [
                    'id' => $component['id'],
                    'component_id' => $component['component_id'],
                    'name' => $component['name'],
                ];

                foreach ($filters as $filter) {
                    if (isset($component[$filter])) {
                        $filteredData[$filter] = $component[$filter];
                    }
                }

                // Format specifications (keys prettified, with optional units)
                $specs = [];
                foreach ($filteredData as $key => $value) {
                    if (in_array($key, ['id', 'component_id', 'name'])) {
                        continue;
                    }

                    $label = ucwords(str_replace('_', ' ', $key));
                    if (isset(self::$UNITS[$key])) {
                        $value .= self::$UNITS[$key];
                    }

                    $specs[$label] = $value;
                }
                // Append to response (component_type not included)
                $responseComponentsFilters['components'][] = [
                    'id' => $component['id'],
                    'component_id' => $component['component_id'],
                    'name' => $component['name'],
                    'specifications' => $specs
                ];
                //$responseComponentsFilters['components'][] = $filteredData;

                // Gather filterable fields
                foreach ($component as $key => $value) {
                    if (in_array($key, ['id', 'component_id', 'name', 'component_type'])) {
                        continue;
                    }

                    // Normalize string sets: "{A,B,C}"
                    if (preg_match('/^\{(.+)\}$/', $value, $matches)) {
                        $values = explode(',', $matches[1]);
                    } else {
                        $values = [$value];
                    }

                    foreach ($values as $val) {
                        $val = trim($val);

                        if (!isset($rawFilters[$key])) {
                            $rawFilters[$key] = [];
                        }

                        if (!in_array($val, $rawFilters[$key], true)) {
                            $rawFilters[$key][] = $val;
                        }
                    }
                }
            }

            // Transform into array of filter objects
            foreach ($rawFilters as $filterKey => $filterValues) {
                $responseComponentsFilters['filters'][] = [
                    'label' => ucwords(str_replace('_', ' ', $filterKey)),
                    'key' => $filterKey, // optional, used for form names
                    'values' => $filterValues
                ];
            }
        }

        return $responseComponentsFilters;
    }

    public function getTotalsCountComponentsByType(string $componentType): int
    {
        return $this->componentRepository->getTotalsCountComponent($componentType);
    }


    private function getComponentTypesFilter(): array
    {
        return [
        'cpu' => ComponentConstraints::$CPU_FILTERS_COMPONENT,
        'motherboard' => ComponentConstraints::$MOTHERBOARD_FILTERS_COMPONENT,
        'gpu' => ComponentConstraints::$GPU_FILTERS_COMPONENT,
        'pc_case' => ComponentConstraints::$PC_CASE_FILTERS_COMPONENT,
        'psu' => ComponentConstraints::$PSU_FILTERS_COMPONENT,
        'storage' => ComponentConstraints::$STORAGE_FILTERS_COMPONENT,
        'ram' => ComponentConstraints::$RAM_FILTERS_COMPONENT,
        ];
    }
}