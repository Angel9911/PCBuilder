<?php

namespace App\Controller;

use App\Constraints\CacheConstraints;
use App\Constraints\ComponentConstraints;
use App\Constraints\ConfigurationConstraint;
use App\Private_lib\redis\RedisWrapper;
use App\Service\ComponentService;
use App\Service\VendorScraperService;
use App\utils\SlugifyClass;
use App\utils\ValidatorUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;

class ComponentController extends AbstractController
{

    private ComponentService $componentService;

    private VendorScraperService $vendorScraperService;

    private RedisWrapper $redis;

    public function __construct(ComponentService $componentService
                                , RedisWrapper $redis
                                , VendorScraperService $vendorScraperService)
    {
        $this->componentService = $componentService;

        $this->redis = $redis;

        $this->vendorScraperService = $vendorScraperService;
    }

    #[Route('/component/{component}', name: 'component.filter', methods: ['GET', 'POST'])]
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

        if(!in_array($component, ConfigurationConstraint::$AVAILABLE_MANDATORY_PC_COMPONENTS)){

            return $this->json([
                'error' => 'Component type not found',
                'field' =>  $componentType
            ]);
        }

        $page = max(1, (int) $request->get('page', 1));
        $limit = 12;
        $offset = ($page - 1) * $limit;

        // Determine if it's an AJAX request
        $isAjax = $request->isXmlHttpRequest();

        // Merge filters from GET or POST
        $filters = $request->isMethod('POST') ? $request->request->all() : $request->query->all();

        // Determine if any filters are set (excluding 'page' param)
        $hasFilters = array_filter(array_keys($filters), fn($key) => $key !== 'page');

        if ($hasFilters) {

            // Fetch filtered components
            $result = $this->componentService->getComponentsByFilters($component, $filters, $limit, $offset);

            $totalCount = count($result); // Count of filtered components
        } else {

            // Use Redis cache for non-filtered result
            $componentTypeFilterKey = CacheConstraints::$COMPONENT_TYPE_FILTER_KEY . '_' . $component;

            if (!$this->redis->isKeyExist($componentTypeFilterKey)) {

                $result = $this->componentService->getAdvanceFilterComponentsByType($component, $limit, $offset);

                $this->redis->set($componentTypeFilterKey, $result, 3600);

            } else {

                $result = $this->redis->get($componentTypeFilterKey);
            }

            $totalCount = $this->componentService->getTotalsCountComponentsByType($component);
        }

        $totalPages = ceil($totalCount / $limit);

        if ($isAjax) {

            // Return JSON for AJAX (used in filtering and pagination)
            $componentsHtml = $this->renderView('pages/component_filters_page/component_templates/component_list.html.twig', [
                'components' => $result['components'],
                'componentType' => $component
            ]);

            $paginationHtml = $this->renderView('pages/component_filters_page/component_templates/component_pagination.html.twig', [
                'totalPages' => $totalPages,
                'currentPage' => $page,
                'componentType' => $component,
            ]);

            return $this->json([
                'components' => $componentsHtml,
                'pagination' => $paginationHtml,
            ]);
        }

        // Render full page
        $componentLabel = ComponentConstraints::$COMPONENT_LABELS[$component] ?? '';

        //return $this->json($result);

        return $this->render('pages/component_filters_page/component_filters.html.twig', [
            'components' => $result['components'],
            'filters' => $result['filters'] ?? [],
            'componentType' => $component,
            'componentLabel' => $componentLabel,
            'totalPages' => $totalPages,
            'currentPage' => $page,
        ]);

    }

    #[Route('/component/{type}/{component}', name: 'component.details', methods: ['GET'])]
    public function getComponentFiltersDetails($type, $component, Request $request): Response
    {

        $isComponentTypeValid = ValidatorUtils::validateAsString((string) $type);

        $isComponentNameValid = ValidatorUtils::validateAsString((string) $component);

        if(!$isComponentNameValid) {

            return $this->json([
                'error' => 'Invalid component name',
                'field' => (string) $component
            ]);
        }

        if(!$isComponentTypeValid) {

            return $this->json([
                'error' => 'Invalid component type',
                'field' => (string) $type
            ]);
        }

        $componentType = (string) $type;

        $componentName = (string) $component;

        $componentCacheKey = CacheConstraints::$COMPONENT_TYPE_FILTER_KEY . '_' . $componentType . '_' . $componentName;

        if(!$this->redis->isKeyExist($componentCacheKey)) {

            $componentSpecifications = $this->componentService->getComponentDetailsByComponentName($componentName, $componentType);

            $this->redis->set($componentCacheKey, $componentSpecifications, 10800);
        } else{

            $componentSpecifications = $this->redis->get($componentCacheKey);
        }

        $componentOffersCacheKey = CacheConstraints::$OFFERS_COMPONENT_KEY . '_' . $componentSpecifications['component_id'];

        if(!$this->redis->isKeyExist($componentOffersCacheKey)) {

            $componentOffers = $this->vendorScraperService->getVendorOffersByComponent($componentSpecifications['component_id']);

            $this->redis->set($componentOffersCacheKey, $componentOffers, 10800);
        } else{

            $componentOffers = $this->redis->get($componentOffersCacheKey);
        }

        //return $this->json($componentSpecifications);

        return $this->render('pages/component_filters_page/component_specifications.html.twig',[
            'componentSpecifications' => $componentSpecifications,
            'componentOffers' => $componentOffers[$componentSpecifications['component_id']] ?? [],
            'offers_price_range' => $componentOffers['offers_price_range'],
        ]);
    }
}