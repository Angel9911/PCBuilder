<?php

namespace App\Service\Impl;

use App\Constraints\ComponentConstraints;
use App\Constraints\PeripheryConstraints;
use App\Private_lib\BaseProduct;
use App\Private_lib\BaseProductService;
use App\Repository\PeripheryRepository;
use App\Service\OpenAIService;
use App\Service\PeripheryService;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManager;

class PeripheryServiceImpl extends BaseProduct implements BaseProductService, PeripheryService
{
    private static array $UNITS = [
        'dpi_min' => 'DPI',
        'dpi_max' => 'DPI',
        'weight_grams' => 'g',
        'cable_length_meters' => 'm',
        'response_time_ms' => 'ms'
    ];
    private PeripheryRepository $peripheryRepository;

    private OpenAIService $openAIService;

    /**
     * @param PeripheryRepository $peripheryRepository
     */
    public function __construct(PeripheryRepository $peripheryRepository
                                , OpenAIService $openAIService)
    {

        $this->peripheryRepository = $peripheryRepository;

        $this->openAIService = $openAIService;
    }


    public static function getUnits(): array
    {
        return self::$UNITS;
    }

    /**
     * @param string $type
     * @return array
     */
    public function getProductKeySpecificationsByType(string $type): array
    {
        $peripheryFilters = [
            'mouse' => PeripheryConstraints::$MOUSE_KEY_SPECIFICATIONS,
            'keyboard' => PeripheryConstraints::$KEYBOARD_KEY_SPECIFICATIONS,
        ];

        return $peripheryFilters[$type] ?? [];
    }

    /**
     * @param string $type
     * @return array
     */
    public function getProductFiltersByType(string $type): array
    {
        $peripheryFilters = [
            'mouse' => PeripheryConstraints::$MOUSE_FILTERS_PERIPHERY,
            'keyboard' => PeripheryConstraints::$KEYBOARD_FILTERS_PERIPHERY,
        ];

        return $peripheryFilters[$type] ?? [];
    }

    /**
     * @throws Exception
     */
    public function getAllProductsByType(string $productType, int $limit = 0, int $offset = 0,  array $selectedCompatibleProducts = [], array $productIds = []): array
    {
        $peripherals = $this->peripheryRepository->getPeripherySpecs($productType, $limit, $offset, $productIds);

        $result = $this->getAdvancedFilterProducts(
            $productType,
            'periphery_type',
            $peripherals,
            'peripheral_id',
            'peripherals',
            fn(array $peripheryProduct) => [
                'brand_name' => $peripheryProduct['brand_name'],
                'description' => $peripheryProduct['description'],
            ],
            fn(int $pid) => $this->peripheryRepository->getPeripheralConnectionNames($pid)
        );

        $result['filters'] = $this->peripheryRepository->getAndLoadProductFiltersByType($productType);


        return $result;
    }

    /**
     * @throws Exception
     */
    public function getProductsByFilters(string $productType, array $filters, int $limit = 12, int $offset = 0, array $selectedComponents = []): array
    {
        $peripherals = $this->peripheryRepository->getPeripherySpecs($productType, $limit, $offset, $selectedComponents, $filters);

        $result = $this->getAdvancedFilterProducts(
            $productType,
            'periphery_type',
            $peripherals,
            'peripheral_id',
            'peripherals',
            fn(array $peripheryProduct) => [
                'brand_name' => $peripheryProduct['brand_name'],
                'description' => $peripheryProduct['description'],
            ],
            fn(int $pid) => $this->peripheryRepository->getPeripheralConnectionNames($pid)
        );

        $result['filters'] = $this->peripheryRepository->getAndLoadProductFiltersByType($productType);

        return $result;
    }

    /**
     * @throws Exception
     */
    public function getProductDetailsByProductNameAndType(string $productName, string $productType): array
    {
        $details = $this->peripheryRepository
            ->getPeripheryDetailsBySlugifyNameAndType($productName, $productType);

        if (empty($details)) return [];

        $peripheryImages = $this->getImagesByProduct($details);

        return [
            'id' => $details[0]['id'],
            'periphery_id' => $details[0]['periphery_id'],
            'name' => $details[0]['name'],
            'periphery_images' => [
                'main_image_url' => $peripheryImages['main_image_url'],
                'all_image_urls' => $peripheryImages['all_image_urls'],
            ],
            'specifications' => $this->formatSpecifications($details[0])
        ];
    }

    /**
     * @throws Exception
     */
    public function getProductsTypeCount(string $type): int
    {
        return $this->peripheryRepository->getTotalsCountPeriphery($type);
    }

    /**
     * @param string $type
     * @return array
     */
    public function getPeripheralsByType(string $type): array
    {
        return $this->peripheryRepository->getPeripheralsByType($type);
    }

    public function getAiRecommendedProduct(string $productType, array $userRequirements): array
    {
        $getAvailableProducts = $this->peripheryRepository->getPeripheralsByType($productType);

        $formatAvailableProducts['available_products'] = array_map(function ($p) {
            // Works if $p is array; also tolerates objects just in case
            $id = is_array($p) ? ($p['id'] ?? null) : ($p->id ?? null);
            $name = is_array($p) ? ($p['name'] ?? null) : ($p->name ?? null);

            return [
                'id'   => (int) $id,
                'name' => (string) $name,
            ];
        }, $getAvailableProducts);

        $formatAvailableProducts['product_specifications'] = array_values(array_unique($this->getProductFiltersByType($productType)));

        $recommendedProducts = $this->openAIService->generateRecommendedProducts($productType, $formatAvailableProducts, $userRequirements);

        return $recommendedProducts;

    }

    protected function getHardCodedArray(): array
    {
        return [
            'product_type' => 'mouse',
            'recommended_products' => [
                [
                    'id' => 6,
                    'name' => 'A4tech Bloody V3',
                    'matching' => 95,
                    'short_description' => 'Perfect for FPS games with ultra-low latency, lightweight design, and professional-grade sensor accuracy.'
                ],
                [
                    'id' => 7,
                    'name' => 'Trust GXT 166',
                    'matching' => 85,
                    'short_description' => 'Perfect for FPS games with ultra-low latency, lightweight design, and professional-grade sensor accuracy.'
                ],
                [
                    'id' => 8,
                    'name' => 'Canyon Mesin CND‑MS01',
                    'matching' => 75,
                    'short_description' => 'Perfect for FPS games with ultra-low latency, lightweight design, and professional-grade sensor accuracy.'
                ]
            ]
        ];
    }

    /**
     * @throws Exception
     */
    public function getProductsIdsByType(string $type): array
    {
        return $this->peripheryRepository->getProductsIdsByType($type);
    }

    /**
     * @throws Exception
     */
    public function getProductCardSpecsByTypeAndIds(string $peripheryType, array $ids): array
    {
        return $this->peripheryRepository->getProductSpecsByTypeAndIds($peripheryType, $ids);
    }
}