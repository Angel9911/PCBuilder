<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\ComponentService;
use App\utils\ObjectMapper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CompatibilityController extends AbstractController
{
    private ComponentService $componentService;

    /**
     * @param ComponentService $componentService
     */
    public function __construct(ComponentService $componentService)
    {
        $this->componentService = $componentService;
    }


    #[Route('/compatibility')]
    public function compatibility(): Response
    {
        return $this->render('compatibility/index.html.twig');
    }

    #[Route('/configurator/compatible', name: 'compatible', methods: ['GET'])]
    public function compatible(Request $request): JsonResponse
    {
        $componentsParams = $request->query->all();

        $validParams = array_intersect_key($componentsParams, array_flip([
            'cpu_id', 'gpu_id', 'ram_id', 'motherboard_id', 'storage_id', 'psu_id'
        ]));

        $compatiblePcComponents = $this->componentService->getCompatibleComponents($validParams);

        return $this->json($compatiblePcComponents);
    }

}
