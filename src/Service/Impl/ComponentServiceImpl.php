<?php

namespace App\Service\Impl;

use App\Constraints\ComponentCatalogFilter;
use App\Entity\ProductRating;
use App\Entity\User\User;
use App\Entity\User\UserAccount;
use App\Private_lib\BaseProduct;
use App\Private_lib\BaseProductService;
use App\Private_lib\helpers\UserHelper;
use App\Private_lib\redis\RedisWrapper;
use App\Repository\ComponentRepository;
use App\Repository\ProductRatingRepository;
use App\Repository\UserRepository;
use App\Repository\UserRoleRepository;
use App\Service\ComponentService;
use App\Service\OpenAIService;
use App\utils\ProductCache;
use Doctrine\DBAL\Exception;
use App\Constraints\ComponentConstraints;


class ComponentServiceImpl extends BaseProduct implements BaseProductService, ComponentService
{
    private ComponentRepository $componentRepository;
    private ProductRatingRepository $productRatingRepository;
    private OpenAIService $openAIService;
    private RedisWrapper $redis;
    private static array $UNITS = [
        'power_wattage' => 'W',
        'length_mm' => 'mm',
        'capacity_gb' => 'GB',
        'max_xmp_speed' => 'MHz',
        'max_memory_supported' => 'GB',
        'gpu_clearance_mm' => 'mm',
        'max_cooler_height_mm' => 'mm',
        'psu_length_limit_mm' => 'mm',
    ];
    private UserHelper $userHelper;

    /**
     * @param ComponentRepository $componentRepository
     * @param OpenAIService $openAIService
     * @param ProductRatingRepository $productRatingRepository
     * @param RedisWrapper $redis
     * @param UserHelper $userHelper
     */
    public function __construct(ComponentRepository $componentRepository
                                , OpenAIService $openAIService
                                , ProductRatingRepository $productRatingRepository
                                , RedisWrapper $redis
                                , UserHelper $userHelper)
    {
        $this->componentRepository = $componentRepository;
        $this->productRatingRepository = $productRatingRepository;

        $this->openAIService = $openAIService;

        $this->redis = $redis;

        $this->userHelper = $userHelper;
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

    private function getComponentTypesSpecsScores(string $type): array
    {
        $componentScores = [
            'cpu' => ComponentConstraints::$CPU_FILTERS_COMPONENT_SCORES,
            'gpu' => ComponentConstraints::$GPU_FILTERS_COMPONENT_SCORES,
            'storage' => ComponentConstraints::$STORAGE_FILTERS_COMPONENT_SCORES,
            'ram' => ComponentConstraints::$RAM_FILTERS_COMPONENT_SCORES,
            'psu' => ComponentConstraints::$PSU_FILTERS_COMPONENT_SCORES,
            'pc_case' => ComponentConstraints::$PC_CASE_FILTERS_COMPONENT_SCORES

        ];

        return $componentScores[$type] ?? [];
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

        $this->populateFormattedProductCache($components, $productType);

        $result = $this->getAdvancedFilterProducts(
            $productType,
            'component_type',
            $components,
            'component_id',
            'components',
            fn(int $productId) => $this->componentRepository->findComponentsRatings('components', $productId),
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
            'cpu' => ComponentConstraints::$CPU_FILTERS_COMPONENT['card_specifications'],
            'motherboard' => ComponentConstraints::$MOTHERBOARD_FILTERS_COMPONENT['card_specifications'],
            'gpu' => ComponentConstraints::$GPU_FILTERS_COMPONENT['card_specifications'],
            'pc_case' => ComponentConstraints::$PC_CASE_FILTERS_COMPONENT['card_specifications'],
            'psu' => ComponentConstraints::$PSU_FILTERS_COMPONENT['card_specifications'],
            'storage' => ComponentConstraints::$STORAGE_FILTERS_COMPONENT['card_specifications'],
            'ram' => ComponentConstraints::$RAM_FILTERS_COMPONENT['card_specifications'],
        ];

        return $componentFilters[$type] ?? [];
    }

    public function getProductMainSpecificationsByType(string $type): array
    {
        $componentFilters = [
            'cpu' => ComponentConstraints::$CPU_FILTERS_COMPONENT['main_specifications'],
            'motherboard' => ComponentConstraints::$MOTHERBOARD_FILTERS_COMPONENT['main_specifications'],
            'gpu' => ComponentConstraints::$GPU_FILTERS_COMPONENT['main_specifications'],
            'pc_case' => ComponentConstraints::$PC_CASE_FILTERS_COMPONENT['main_specifications'],
            'psu' => ComponentConstraints::$PSU_FILTERS_COMPONENT['main_specifications'],
            'storage' => ComponentConstraints::$STORAGE_FILTERS_COMPONENT['main_specifications'],
            'ram' => ComponentConstraints::$RAM_FILTERS_COMPONENT['main_specifications'],
        ];

        return $componentFilters[$type] ?? [];
    }

    public function getProductGeneralSpecificationsByType(string $type): array
    {
        $componentFilters = [
            'cpu' => ComponentConstraints::$CPU_FILTERS_COMPONENT['general_specifications'],
            'motherboard' => ComponentConstraints::$MOTHERBOARD_FILTERS_COMPONENT['general_specifications'],
            'gpu' => ComponentConstraints::$GPU_FILTERS_COMPONENT['general_specifications'],
            'pc_case' => ComponentConstraints::$PC_CASE_FILTERS_COMPONENT['general_specifications'],
            'psu' => ComponentConstraints::$PSU_FILTERS_COMPONENT['general_specifications'],
            'storage' => ComponentConstraints::$STORAGE_FILTERS_COMPONENT['general_specifications'],
            'ram' => ComponentConstraints::$RAM_FILTERS_COMPONENT['general_specifications'],
        ];

        return $componentFilters[$type] ?? [];
    }

    /**
     * @throws Exception
     */
    public function getProductDetailsByProductNameAndType(string $productName, string $productType): array
    {
        // Try to resolve ID from slug cache (fast lookup)
        $productId = ProductCache::getIdFromSlugCache($this->redis, 'components', $productType, $productName);

        // If not cached yet, fetch from DB by name
        if (!$productId) {

            $componentRow = $this->componentRepository->findComponentNameBySlugifyName($productName);

            if (!$componentRow) {

                return [];
            }

            $productId = (int) $componentRow[0]['component_id'];

            ProductCache::putSlugIdMapping($this->redis, 'components', $productType, $productName, $productId);
        }

        // Fetch full product record by ID
        $componentDetails = $this->componentRepository->getComponentSpecs($productType, 0, 0, [], [], null, $productId);

        if (empty($componentDetails)) {

            return [];
        }

        // Load and format images
        $componentImagesRaw = $this->componentRepository->findComponentImages($componentDetails['component_id']);
        $componentImages = $this->getImagesByProduct([['images' => $componentImagesRaw]]);

        // Format specs and scores
        $formatted = $this->formatProductSpecifications($componentDetails, $productType);

        $productRating = $this->componentRepository->findComponentsRatings('components', $componentDetails['component_id']) ?? 0;

        if($productRating > 0){

            $productReviews = $this->componentRepository->findComponentsReviews('components', $componentDetails['component_id']);

            $productRating = array_merge($productRating, $productReviews);
        }

        return [
            'id' => $componentDetails['id'],
            'component_id' => $componentDetails['component_id'],
            'name' => $componentDetails['name'],
            'component_images' => $componentImages,
            'rating' => $productRating,
            'component_scores' => $this->getComponentScores($productType, $componentDetails),
            'specifications' => $formatted
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


    public function rateProduct(string $baseProductType, array $productRatingData, array $userData): void
    {
        $user = $this->userHelper->getOrCreateAnonymousUser(
            $userData['name'],
            $userData['email']
        );

        //$componentData = $this->componentRepository->findComponentNameBySlugifyName($productRatingData['product']);

        $productRating = new ProductRating((int) $productRatingData['product_id'], $baseProductType, (int) $productRatingData['stairs']);

        $productRating->setUser($user);

        $productRating->setReview($productRatingData['comment']);

        $this->productRatingRepository->saveProductRating($productRating);

    }

    /**
     * @throws Exception
     */
    public function getProductRating(string $productType, int $productId): array
    {
        $componentData = $this->componentRepository->findComponentsRatings($productType, $productId);

        if (!$componentData) {

            throw new \InvalidArgumentException("Invalid product: {$productId}");
        }

        return $componentData;

    }

    /**
     * @throws Exception
     */
    public function getProductReviews(string $productType, int $productId): array
    {
        $componentData = $this->componentRepository->findComponentsReviews($productType, $productId);

        if (!$componentData) {

            throw new \InvalidArgumentException("Invalid product: {$productId}");
        }

        return $componentData;
    }
    /**
     * @throws Exception
     */
    public function populateFormattedProductCache(array $components, string $productType): void
    {
        $start = microtime(true);

        foreach ($components as $component) {

            $componentId = (int) $component['component_id'];
            $slug = $component['slugify_name'] ?? null;

            // 1. Skip if no slug (should never happen, but safe)
            if (empty($slug)) {
                continue;
            }

            // 2. Store slug→id mapping (no TTL)
            ProductCache::putSlugIdMapping(
                $this->redis,
                'components',
                $productType,
                $slug,
                $componentId
            );

            // 3. Check if already cached
            $cached = ProductCache::getProductDetails(
                $this->redis,
                'components',
                $productType,
                $componentId
            );

            if (!empty($cached)) {
                continue;
            }

            //  4. Merge joined attributes (if needed)
            $needsJoin = in_array($productType, ['gpu', 'motherboard', 'pc_case', 'psu']);
            $joined = $needsJoin
                ? $this->componentRepository->findJoinedAttributesForComponentDetails($productType, $componentId)
                : [];

            $merged = array_merge($component, $joined ?? []);

            // 5. Format using existing formatter
            $formatted = $this->formatProductSpecifications($merged, $productType);

            $getComponentImages = $this->componentRepository->findComponentImages($componentId);

            $componentImages = $this->getImagesByProduct($getComponentImages);


            // Fetch rating and scores (reuse closures from main service)
            $rating = $this->componentRepository->findComponentsRatings('components', $componentId);
            $scores = $this->getComponentScores($productType, $component);

            //  6. Store formatted details in cache (6h TTL)
            ProductCache::putProductDetails(
                $this->redis,
                'components',
                $productType,
                $componentId,
                [
                    'formatted_details' => [
                        'id' => $component['id'],
                        'component_id' => $componentId,
                        'name' => $component['name'],
                        'component_images' => $componentImages,
                        'rating' => $rating,
                        'component_scores' => $scores,
                        'specifications' => $formatted
                    ]
                ],
                21600
            );
        }

        $duration = round((microtime(true) - $start) * 1000, 2);
        //var_dump("Formatted and cached " . count($components) . " {$productType} products in {$duration} ms");

    }

    public function formatProductSpecifications(array $productData, string $type): array
    {
        $constraints = ComponentConstraints::${strtoupper($type) . '_FILTERS_COMPONENT'} ?? null;

        $productFilters = ComponentCatalogFilter::get($type)['filters'] ?? [];

        if (!$constraints) {
            return [
                'main_specifications'    => [],
                'general_specifications' => [],
            ];
        }

        $formatSpecGroup = function(array $keys) use ($productData, $productFilters) {
            $out = [];
            foreach ($keys as $key) {
                if (!array_key_exists($key, $productData)) {
                    continue; // skip if not present in the row
                }
                $value = $productData[$key];

                // format booleans
                if (is_bool($value)) {
                    $value = $value ? 'Yes' : 'No';
                }

                // append units where logical
                $units = match($key) {
                    'power_wattage'   => 'W',
                    'length_mm'       => 'mm',
                    'efficiency'      => '%',
                    'noise_level'     => 'dB',
                    default           => ''
                };

                // use label from filter config (fallback to key if missing)
                $label = $productFilters[$key]['label'] ?? ucfirst(str_replace('_', ' ', $key));

                $out[$label] = $units ? "{$value} {$units}" : $value;
            }
            return $out;
        };

        return [
            'main_specifications'    => $formatSpecGroup($constraints['main_specifications'] ?? []),
            'general_specifications' => $formatSpecGroup($constraints['general_specifications'] ?? []),
        ];
    }
}