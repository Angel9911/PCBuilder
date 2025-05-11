<?php

namespace App\Controller;

use App\Constraints\CacheConstraints;
use App\Private_lib\redis\RedisWrapper;
use App\Service\ComponentService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;

class ComponentController extends AbstractController
{

    private ComponentService $componentService;

    private RedisWrapper $redis;

    public function __construct(ComponentService $componentService
                                , RedisWrapper $redis)
    {
        $this->componentService = $componentService;

        $this->redis = $redis;
    }

    #[Route('/component/{component}', name: 'component.filter', methods: ['GET'])]
    public function getComponentsDetails($component, Request $request): Response
    {

        $page = max(1, (int) $request->get('page', 1));

        $limit = 12;

        $offset = ($page - 1) * $limit;

        $component = (string) $component;

        $componentTypeFilterKey = CacheConstraints::$COMPONENT_TYPE_FILTER_KEY . '_' . $component;

        // Check if data exists in Redis cache
        if ($this->redis->isKeyExist($componentTypeFilterKey)) {

            $result = $this->redis->get($componentTypeFilterKey);

        } else {

            // Fetch from database and cache the result
            $result = $this->componentService->getComponentsDetailsByType($component, $limit, $offset);

            $this->redis->set($componentTypeFilterKey, $result, 3600); // Cache for 1 hour
        }

        $totalsCount = $this->componentService->getTotalsCountComponentsByType($component);

        $totalPages = ceil($totalsCount / $limit);

        //return $this->json($components);
        return $this->render('pages/component_filters_page/component_filters.html.twig', [
            'components' => $result['components'],
            'filters' => $result['filters'],
            'componentType' => $component,
            'totalPages' => $totalPages,
            'currentPage' => $page,
        ]);
    }

    #[Route('/component/{component}', name: 'component.filter.by', methods: ['GET'])]
    public function getComponentFiltersDetails($component, Request $request): Response
    {

        $component = (string) $component;

        $filters = (int) $request->get('filter');
    }
}