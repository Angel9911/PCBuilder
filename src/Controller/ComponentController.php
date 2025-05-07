<?php

namespace App\Controller;

use App\Service\ComponentService;
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
    public function getComponentsDetails($component): Response
    {
        $component = (string) $component;

        $components = $this->componentService->getComponentsDetailsByType($component);

        //return $this->json($components);
        return $this->render('pages/component_filters_page/component_filters.html.twig', [
            'components' => $components['components'],
            'filters' => $components['filters'],
        ]);
    }
}