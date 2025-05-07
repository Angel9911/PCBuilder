<?php

namespace App\Controller;

use App\Service\ComponentService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;

class ComponentController extends AbstractController
{

    private ComponentService $componentService;

    public function __construct(ComponentService $componentService)
    {
        $this->componentService = $componentService;
    }

    #[Route('/component/{component}', name: 'component.filter', methods: ['GET'])]
    public function getComponentsDetails($component, Request $request): Response
    {

        $page = max(1, (int) $request->get('page', 1));

        $limit = 12;

        $offset = ($page - 1) * $limit;

        $component = (string) $component;

        $components = $this->componentService->getComponentsDetailsByType($component, $limit, $offset);

        $totalsCount = $this->componentService->getTotalsCountComponentsByType($component);

        $totalPages = ceil($totalsCount / $limit);

        //return $this->json($components);
        return $this->render('pages/component_filters_page/component_filters.html.twig', [
            'components' => $components['components'],
            'filters' => $components['filters'],
            'componentType' => $component,
            'totalPages' => $totalPages,
            'currentPage' => $page,
        ]);
    }
}