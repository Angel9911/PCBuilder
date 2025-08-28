<?php

namespace App\Controller;

use App\Constraints\CacheConstraints;
use App\Constraints\ComponentConstraints;
use App\Constraints\ConfigurationConstraint;
use App\Constraints\PeripheryConstraints;
use App\Private_lib\ProductServiceDispatcher;
use App\Private_lib\redis\RedisWrapper;
use App\Service\ComponentService;
use App\Service\Impl\PeripheryServiceImpl;
use App\Service\PeripheryService;
use App\Service\VendorScraperService;
use App\utils\ObjectMapper;
use App\utils\ProductCache;
use App\utils\SlugifyClass;
use App\utils\ValidatorUtils;
use Doctrine\DBAL\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;

class ComponentController extends AbstractController
{

    private ComponentService $componentService;

    private ProductServiceDispatcher $productServiceDispatcher;

    private VendorScraperService $vendorScraperService;

    private RedisWrapper $redis;

    public function __construct(ComponentService $componentService
                                , RedisWrapper $redis
                                , VendorScraperService $vendorScraperService
                                , ProductServiceDispatcher $productServiceDispatcher)
    {
        $this->componentService = $componentService;

        $this->redis = $redis;

        $this->vendorScraperService = $vendorScraperService;

        $this->productServiceDispatcher = $productServiceDispatcher;
    }

    /**
     * @throws Exception
     */
    #[Route('/product/{component}', name: 'component.filter', methods: ['GET', 'POST'])]
    public function getComponentsDetails($component, Request $request): Response
    {
        $componentType = (string) $component;

        $isValidComponentType = ValidatorUtils::validateAsString($componentType);

        if(!$isValidComponentType){

            return $this->json([
                'error' => 'Invalid component type',
                'field' =>  $componentType
            ]);
        }

        $productType = ConfigurationConstraint::getProductType($component);

        if ($productType === null) {
            return $this->json(['error' => 'Component type not found', 'field' => $componentType]);
        }

        $productService = $this->productServiceDispatcher->getService($productType);

        //$selectedComponents = $request->getSession()->get('pc_configuration');

        $compatibleSelectedComponents = $request->getSession()->get('compatible_components');

        $currentComponentCompatibleValues = [];

        if(!empty($compatibleSelectedComponents) && $productType === 'components'){

            $currentComponentCompatibleValues = $compatibleSelectedComponents[$componentType . '_ids'];

            $compatibleProductsIds = array_column($currentComponentCompatibleValues, 'component_id');

        }
/*        if(!empty($selectedComponents)) {

            foreach (ConfigurationConstraint::$AVAILABLE_MANDATORY_PC_COMPONENTS as $selectedComponentType) {

                if (isset($selectedComponents[$selectedComponentType]['component_id'])) {

                    $selectedComponentsIds[$selectedComponentType . '_id'] = $selectedComponents[$selectedComponentType]['component_id'];
                }
            }
            // TODO: Pagination doesn't working because when the logic goes on compatiblity the count of records is different.
            $compatibleSelectedComponents = $this->componentService->getCompatibleComponents($selectedComponentsIds);
        }*/

        $page = max(1, (int) $request->get('page', 1));
        $limit = 12;
        $offset = ($page - 1) * $limit;

        // Determine if it's an AJAX request
        $isAjax = $request->isXmlHttpRequest();

        // Merge filters from GET or POST
        $filters = $request->isMethod('POST') ? $request->request->all() : $request->query->all();

        // Determine if any filters are set (excluding 'page' param)
        $hasFilters = array_filter(array_keys($filters), fn($key) => $key !== 'page' && $key !== 'ajax_ai');

/*        echo '<pre>';
        print_r($filters);
        echo '</pre>';*/

        if ($hasFilters) {

            // Fetch filtered components
            $result = $this->componentService->getComponentsByFilters($component
                , $filters
                , $limit
                , $offset
                , !empty($currentComponentCompatibleValues) ? $currentComponentCompatibleValues : []);

            $totalCount = count($result); // Count of filtered components
        } else {
                // NEW WAY CACHING

                // CACHE MEY FILTERS
                $productFiltersCacheKey = CacheConstraints::getProductFiltersCacheKeyByProductType($productType);

                $productFilterTypeCacheKey = $productFiltersCacheKey . '_' . $component;

                $productIds = ProductCache::getIndexIdsFromCache($this->redis, $productType, $componentType);

                $filters = [];

                if($productIds === null){

                    $productIds = ProductCache::setIdsIndexCache($this->redis, $productService, $productType, $componentType);
                }

                $currentProductIdsPage = ProductCache::getPageIds($productIds, $page, $limit);

                if(!empty($compatibleProductsIds)){

                    $currentProductIdsPage = $compatibleProductsIds;
                }

                $totalCount = count($productIds);

                $cachedProducts = ProductCache::getCachedCards($this->redis, $productType, $componentType, $currentProductIdsPage);

                $missing = array_values(array_diff($currentProductIdsPage, array_keys($cachedProducts)));

                $this->redis->delete($productFilterTypeCacheKey);

                if(!empty($missing)){

                    $result = $productService->getAllProductsByType(
                        $component,
                        0,
                        0,
                        $productType === 'component' ? ($selectedComponentsIds[$componentType . '_id'] ?? []) : [],
                        $missing
                    );

                    // Put each row in the per-id cache
                    ProductCache::putCards($this->redis, $productType, $componentType, $result[$productType], 3600);

                    if (!$this->redis->isKeyExist($productFilterTypeCacheKey)) {

                        $filters = $result['filters'] ?? [];

                        $this->redis->set($productFilterTypeCacheKey, $result['filters'], 3600);
                    } else {

                        $filters = $this->redis->get($productFilterTypeCacheKey);
                    }

                    // Refresh cached page cards (now everything should be present)
                    $cachedProducts = ProductCache::getCachedCards($this->redis, $productType, $componentType, $currentProductIdsPage);

                } else {

                    // Filters
                    if ($this->redis->isKeyExist($productFilterTypeCacheKey)) {

                        $filters = $this->redis->get($productFilterTypeCacheKey);
                    } else {

                        // Fetch only filters once; re-use your existing service method
                        $tmp = $productService->getAllProductsByType(
                            $component,
                            0, // no pagination (service will still compute filters)
                            0,
                            $productType === 'component' ? ($selectedComponentsIds[$componentType . '_id'] ?? []) : [],
                            [] // no specific IDs
                        );
                        $filters = $tmp['filters'] ?? [];
                        $this->redis->set($productFilterTypeCacheKey, $filters, 3600);
                    }
                }

            // 6) Order cached cards exactly like the page IDs
            $rowsOrdered = [];
            foreach ($currentProductIdsPage as $id) {

                if (isset($cachedProducts[$id])) {

                    $rowsOrdered[] = $cachedProducts[$id]; // these are already formatted cards (you cached $result[$productType])
                }
            }

            // 7) Build the final $result structure your view expects
            $result = [
                $productType => $rowsOrdered,     // 'components' or 'peripherals'
                'filters'    => $filters ?? [],   // from cache or freshly computed
            ];

            //$ids = ProductCache::getIndexIdsFromCache($this->redis, $productType, $componentType) ?? ProductCache::setIdsIndexCache($this->redis, $productService, $productType, $componentType);
            //$totalCount = count($ids); // source of truth for pagination
        }

        $totalPages = ceil($totalCount / $limit);

        if ($isAjax) {

            // Determine product category (components vs peripherals)
            $productCategory = ConfigurationConstraint::getProductType($component);

            // Pick correct template for product cards
            $listTemplate = match ($productCategory) {
                'components'  => 'pages/component_filters_page/component_templates/component_list.html.twig',
                'peripherals' => 'pages/periphery_filter_page/periphery_template/periphery_list.html.twig',
                default => throw new \InvalidArgumentException("Unknown product category: $productCategory"),
            };

            // Decide the key in $result (components vs peripherals)
            $collectionKey = $productCategory === 'components' ? 'components' : 'peripherals';

            // Render product cards with correct template + data
            $productsHtml = $this->renderView($listTemplate, [
                $collectionKey => $result[$collectionKey],  // dynamic key
                'componentType' => $component,
                'main_image' => ConfigurationConstraint::$PRODUCT_TEST_MAIN_IMAGES[$component] ?? "",
            ]);
            // Pagination template is the same for both
            $paginationHtml = $this->renderView('pages/component_filters_page/component_templates/component_pagination.html.twig', [
                'totalPages'    => $totalPages,
                'currentPage'   => $page,
                'componentType' => $component,
            ]);

            // Optional: also inject AI block if requested (see previous answer)
            $aiBlockHtml = '';

            $aiSession = $request->getSession()->get('ai_recommended_products', []);

            if ($request->query->get('ajax_ai') && !empty($aiSession)) {

                $peripheryIcons = [];

                if (!empty(PeripheryConstraints::$PERIPHERY_ICONS[$component])) {
                    foreach (PeripheryConstraints::$PERIPHERY_ICONS[$component] as $iconConfig) {
                        if (isset($iconConfig['label'])) {
                            $peripheryIcons[$iconConfig['label']] = $iconConfig;
                        }
                    }
                }

                $aiBlockHtml = $this->renderView('pages/pages_templates/ai_recommended_products_section.html.twig', [
                    'peripherals' => $aiSession['recommendedProducts'] ?? [],
                    'user_query'  => $aiSession['user_requirement'] ?? '',
                    'main_image' => ConfigurationConstraint::$PRODUCT_TEST_MAIN_IMAGES[$component] ?? "",
                    'periphery_type_icons' => $peripheryIcons,
                ]);
            }

            return $this->json([
                $collectionKey   => $productsHtml,
                'pagination'     => $paginationHtml,
                'ai_recommended' => $aiBlockHtml,
            ]);
        }

        // Determine product category (component or peripheral)
        $productCategory = ConfigurationConstraint::getProductType($component);

        $templatePath = match ($productCategory) {
            'components' => 'pages/component_filters_page/component_filters.html.twig',
            'peripherals' => 'pages/periphery_filter_page/periphery_filters.html.twig',
            default => throw new \InvalidArgumentException("Unknown product category: $productCategory"),
        };

        // Optional: provide label only for components
        $componentLabel = $productCategory === 'components'
            ? ComponentConstraints::$COMPONENT_LABELS[$component] ?? PeripheryConstraints::$PERIPHERY_LABELS[$component]
            : '';

        // Prepare variable name: 'components' or 'peripherals'
        $collectionKey = $productCategory === 'components' ? 'components' : 'peripherals';

        $peripheryIcons = [];

        if (!empty(PeripheryConstraints::$PERIPHERY_ICONS[$component])) {
            foreach (PeripheryConstraints::$PERIPHERY_ICONS[$component] as $iconConfig) {
                if (isset($iconConfig['label'])) {
                    $peripheryIcons[$iconConfig['label']] = $iconConfig;
                }
            }
        }

        //return $this->json($result['filters']);

        return $this->render($templatePath, [
            $collectionKey => $result[$collectionKey],
            'filters' => $result['filters'] ?? [],
            'componentType' => $component,
            'componentLabel' => $componentLabel,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'main_image' => ConfigurationConstraint::$PRODUCT_TEST_MAIN_IMAGES[$component] ?? "",
            'periphery_type_icons' => $peripheryIcons
        ]);
    }

    #[Route('/product/{type}/{component}', name: 'component.details', methods: ['GET'])]
    public function getComponentFiltersDetails($type, $component, Request $request): Response
    {

        $isTypeValid = ValidatorUtils::validateAsString($type);

        $isComponentNameValid = ValidatorUtils::validateAsString($component);

        if (!$isComponentNameValid) {
            return $this->json([
                'error' => 'Invalid component name',
                'field' => $component
            ]);
        }

        if (!$isTypeValid) {
            return $this->json([
                'error' => 'Invalid component type',
                'field' => $type
            ]);
        }

        $productCategory = ConfigurationConstraint::getProductType($type);

        if ($productCategory === null) {

            return $this->json([
                'error' => 'Unknown product type',
                'field' => $type
            ]);
        }

        $service = $this->productServiceDispatcher->getService($type);

        $cacheKey = CacheConstraints::$COMPONENT_KEY . '_' . $type . '_' . $component;

        if (!$this->redis->isKeyExist($cacheKey)) {

            $productData = $service->getProductDetailsByProductNameAndType($component, $type);

            $this->redis->set($cacheKey, $productData, 10800); // 3 hours

        } else {

            $productData = $this->redis->get($cacheKey);
        }

        $componentOffersCacheKey = CacheConstraints::$OFFERS_COMPONENT_KEY . '_' . $productData['component_id'];

        if(!$this->redis->isKeyExist($componentOffersCacheKey)) {

            $componentOffers = $this->vendorScraperService->getVendorOffersByComponent($productData['component_id']);

            $this->redis->set($componentOffersCacheKey, $componentOffers, 10800);
        } else{

            $componentOffers = $this->redis->get($componentOffersCacheKey);
        }

        return $this->render('pages/component_filters_page/component_specifications.html.twig',[
            'componentSpecifications' => $productData,
            'componentOffers' => $componentOffers[$componentSpecifications['component_id']] ?? [],
            'offers_price_range' => $componentOffers['offers_price_range'],
        ]);
    }

    #[Route('/product/{type}/add', name: 'component.add', methods: ['POST'])]
    public function addComponentToConfiguration($type, Request $request): Response
    {

        $componentType = (string) $type;

        $isComponentTypeValid = ValidatorUtils::validateAsString($componentType);

        if(!$isComponentTypeValid) {

            return $this->json([
                'error' => 'Invalid component type',
                'field' => $componentType
            ]);
        }

        if(!in_array($componentType, ConfigurationConstraint::$AVAILABLE_MANDATORY_PC_COMPONENTS)){

            return $this->json([
                'error' => 'Component type not found',
                'field' => $componentType
            ]);
        }

        $componentSlugify = ObjectMapper::mapJsonToObject($request->getContent());

        if(empty($componentSlugify) || !isset($componentSlugify['name'])){

            return $this->json([
                'error' => 'Invalid or empty payload',
            ], 400);
        }

        $session = $request->getSession();

        if(!empty($session->get('pc_configuration', []))){

            $pcConfiguration = $session->get('pc_configuration', []);

        } else {

            $pcConfiguration = [];
        }

        $componentData = $this->componentService->getComponentNameBySlugifyName($componentSlugify['name']);

        $pcConfiguration[$componentType] = [
            'component_id' => $componentData[0]['component_id'],
            'name' => $componentData[0]['component_name'],
        ];

        $session->set('pc_configuration', $pcConfiguration);
        $session->set('isAiConfiguration', false);

        return $this->redirectToRoute('configurator.build');
    }
    #[Route('/product/ai/{product}', name: 'component.ai.recommended', methods: ['POST'])]
    public function generateAiRecommendationProducts($product, Request $request): Response
    {
        $productType = (string) $product;

        $isValidProductType = ValidatorUtils::validateAsString($productType);

        if(!$isValidProductType){

            return $this->json([
                'error' => 'Invalid product type',
                'field' =>  $productType
            ]);
        }

        $baseProductType = ConfigurationConstraint::getProductType($productType);

        if ($baseProductType === null) {
            return $this->json(['error' => 'Product type not found', 'field' => $productType]);
        }

        $productService = $this->productServiceDispatcher->getService($baseProductType);

        $userRequirement = ObjectMapper::mapJsonToObject($request->getContent());

        $recommendedProducts = $productService->getAiRecommendedProduct($productType, $userRequirement);

        $recommendedProductsIds = [];

        foreach ($recommendedProducts['recommended_products'] as $recommendedProduct) {
            $recommendedProductsIds[] = $recommendedProduct['id'];
        }

        $cachedProducts = ProductCache::getCachedCards($this->redis, $baseProductType, $productType, $recommendedProductsIds);

        $missing = array_values(array_diff($recommendedProductsIds, array_keys($cachedProducts)));

        if(!empty($missing)) {

            $result = $productService->getAllProductsByType(
                $productType,
                0,
                0,
                $productType === 'component' ? ($selectedComponentsIds[$productType . '_id'] ?? []) : [],
                $missing
            );

            // Put each row in the per-id cache
            ProductCache::putCards($this->redis, $baseProductType, $productType, $result[$baseProductType], 3600);
        }

        // Refresh cached page cards (now everything should be present)
        $cachedProducts = ProductCache::getCachedCards($this->redis, $baseProductType, $productType, $recommendedProductsIds);

        foreach ($recommendedProducts['recommended_products'] as $recommendedProduct) {

            $productId = $recommendedProduct['id'];

            //$cacheProductDetails = $cachedProducts[$productId];

            $cachedProducts[$productId]['ai_matching'] = $recommendedProduct['matching'];

            $cachedProducts[$productId]['ai_description'] = $recommendedProduct['short_description'];
        }

        // sort by ai_matching percentage
        usort($cachedProducts, function ($a, $b) {

           return $b['ai_matching'] <=> $a['ai_matching'];
        });

        $result = [
            'user_requirement' => $userRequirement['user_requirement'],
            'recommendedProducts' => $cachedProducts
        ];

        $request->getSession()->set('ai_recommended_products', $result);

        return $this->json(['ok' => true]);
        //return $this->redirectToRoute('component.filter');
    }

    private function getCompatIdsFor(string $componentType, Request $request): array {

        $scopes = $request->getSession()->get('compatible_components', []);

        $key = $componentType . '_ids';        // e.g. 'cpu_ids'

        return isset($scopes[$key]) ? array_values($scopes[$key]) : [];
    }

}