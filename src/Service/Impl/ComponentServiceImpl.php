<?php

namespace App\Service\Impl;

use App\Constraints\ComponentCatalogFilter;
use App\Private_lib\BaseProduct;
use App\Private_lib\BaseProductService;
use App\Repository\ComponentRepository;
use App\Service\ComponentService;
use App\Service\OpenAIService;
use Doctrine\DBAL\Exception;
use App\Constraints\ComponentConstraints;


class ComponentServiceImpl extends BaseProduct implements BaseProductService, ComponentService
{
    private ComponentRepository $componentRepository;
    private OpenAIService $openAIService;
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
    public function __construct(ComponentRepository $componentRepository
                                , OpenAIService $openAIService)
    {
        $this->componentRepository = $componentRepository;

        $this->openAIService = $openAIService;
    }


    public static function getUnits(): array
    {
        return self::$UNITS;
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

            $compatibleParts = $this->componentRepository->findCompatibleComponents($filterParams);

            return $compatibleParts;

        }catch (\Exception $exception){
            return [];
        }
    }

    /**
     * @throws Exception
     */
    public function getAllProductsByType(string $productType, int $limit = 0, int $offset = 0, array $selectedCompatibleProducts = [], array $productIds = []): array
    {
        // TODO: REWORK THIS
        if(!empty($productIds)){

            $selectedCompatibleProducts = $productIds;
        }

        $components = $this->componentRepository->getComponentSpecs($productType, $limit, $offset, [], $selectedCompatibleProducts);

        $result = $this->getAdvancedFilterProducts(
            $productType,
            'component_type',
            $components,
            'component_id',
            'components',
            fn(array $componentProduct) => $this->getComponentScores($productType, $componentProduct),
        );


        $result['filters'] = $this->componentRepository->getAndLoadProductFiltersByType($productType);

        return $result;
    }

    /**
     * @throws Exception
     */
    public function getComponentsByFilters(string $componentType, array $filters, int $limit = 12, int $offset = 0, array $selectedComponents = []): array
    {
        $components = $this->componentRepository->getComponentSpecs($componentType, $limit, $offset, $filters, $selectedComponents);

        $result = $this->getAdvancedFilterProducts(
            $componentType,
            'component_type',
            $components,
            'component_id',
            'components',
            fn(array $componentProduct) => $this->getComponentScores($componentType, $componentProduct),
        );

        $result['filters'] = $this->componentRepository->getAndLoadProductFiltersByType($componentType);

        return $result;

    }

    /**
     * @throws Exception
     */
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

    private function getComponentTypesSpecsScores(string $type): array
    {
        $componentScores = [
            'cpu' => ComponentConstraints::$CPU_FILTERS_COMPONENT_SCORES,
            'gpu' => ComponentConstraints::$GPU_FILTERS_COMPONENT_SCORES,
            'storage' => ComponentConstraints::$STORAGE_FILTERS_COMPONENT_SCORES,
            'ram' => ComponentConstraints::$RAM_FILTERS_COMPONENT_SCORES,
        ];

        return $componentScores[$type] ?? [];
    }


    public function updateComponentName(string $existingName, string $slugifyName): void
    {
        $this->componentRepository->updateComponentName($existingName, $slugifyName);
    }

    public function getComponentIdBySlugifyName(string $slugifyName): int
    {
        //$this->componentRepository->
    }

    public function getComponentNameBySlugifyName(string $slugifyName): array
    {
        return $this->componentRepository->findComponentNameBySlugifyName($slugifyName);
    }


    public function getProductKeySpecificationsByType(string $type): array
    {
        $componentFilters = [
            'cpu' => ComponentConstraints::$CPU_FILTERS_COMPONENT,
            'motherboard' => ComponentConstraints::$MOTHERBOARD_FILTERS_COMPONENT,
            'gpu' => ComponentConstraints::$GPU_FILTERS_COMPONENT,
            'pc_case' => ComponentConstraints::$PC_CASE_FILTERS_COMPONENT,
            'psu' => ComponentConstraints::$PSU_FILTERS_COMPONENT,
            'storage' => ComponentConstraints::$STORAGE_FILTERS_COMPONENT,
            'ram' => ComponentConstraints::$RAM_FILTERS_COMPONENT,
        ];

        return $componentFilters[$type] ?? [];
    }

    /**
     * @throws Exception
     */
    public function getProductDetailsByProductNameAndType(string $productName, string $productType): array
    {
        $componentDetails = $this->componentRepository->findComponentSpecificationsByNameAndType($productName, $productType);

        $componentImages = $this->getImagesByProduct($componentDetails);

        return [
            'id' => $componentDetails[0]['id'],
            'component_id' => $componentDetails[0]['component_id'],
            'name' => $componentDetails[0]['name'],
            'component_images' => [
                'main_image_url' => $componentImages['main_image_url'],
                'all_image_urls' => $componentImages['all_image_urls'],
            ],
            'specifications' => $this->formatSpecifications($componentDetails[0])
        ];
    }

    /**
     * @throws Exception
     */
    public function getProductsTypeCount(string $type): int
    {
        return $this->componentRepository->getTotalsCountComponent($type);
    }

    public function getAiRecommendedProduct(string $productType, array $userRequirements): array
    {
        $availableComponents = $this->componentRepository->findComponentsByType($productType);

        $formatAvailableProducts['available_products'] = array_map(function ($p) {
            // Works if $p is array; also tolerates objects just in case
            $id = is_array($p) ? ($p['id'] ?? null) : ($p->id ?? null);
            $name = is_array($p) ? ($p['name'] ?? null) : ($p->name ?? null);

            return [
                'id'   => (int) $id,
                'name' => (string) $name,
            ];
        }, $availableComponents);

        $formatAvailableProducts['product_specifications'] = array_keys(ComponentCatalogFilter::get($productType)['filters']);

        $recommendedProducts = $this->openAIService->generateRecommendedProducts($productType, $formatAvailableProducts, $userRequirements);

        return $recommendedProducts;
    }

    public function getProductFiltersByType(string $type): array
    {
        // TODO: Implement getProductFiltersByType() method.
    }

    /**
     * @throws Exception
     */
    public function getProductsIdsByType(string $type): array
    {
        return $this->componentRepository->getProductsIdsByType($type);
    }

    /**
     * @throws Exception
     */
    public function getProductCardSpecsByTypeAndIds(string $peripheryType, array $ids): array
    {
        return $this->componentRepository->getProductSpecsByTypeAndIds($peripheryType, $ids);
    }
    private function getComponentScores(string $componentType, array $componentProduct): array
    {
        $componentSpecScores = $this->getComponentTypesSpecsScores($componentType);

        if(empty($componentSpecScores)){
            return [];
        }

        $formatComponentScores = [];

        foreach ($componentSpecScores as $specScore) {

            if(array_key_exists($specScore, $componentProduct)){

                $labelSpecificationScore = ucwords(str_replace('_', ' ', $specScore));

                $formatComponentScores[$labelSpecificationScore] = $componentProduct[$specScore];
            }
        }

        return $formatComponentScores;
    }

    /**
     * @throws Exception
     */
    public function getProductsByFilters(string $productType, array $filters, int $limit = 12, int $offset = 0, array $selectedComponents = []): array
    {
        $components = $this->componentRepository->getComponentSpecs($productType, $limit, $offset, $filters, $selectedComponents);

        $result = $this->getAdvancedFilterProducts(
            $productType,
            'component_type',
            $components,
            'component_id',
            'components',
            fn(array $componentProduct) => $this->getComponentScores($productType, $componentProduct),
        );

        $result['filters'] = $this->componentRepository->getAndLoadProductFiltersByType($productType);

        return $result;
    }
}