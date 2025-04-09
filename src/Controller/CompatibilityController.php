<?php

declare(strict_types=1);

namespace App\Controller;

use App\Constraints\ConfigurationConstraint;
use App\Service\ComponentService;
use App\utils\ObjectMapper;
use App\utils\ValidatorUtils;
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

        $validComponentsIds = ValidatorUtils::validateAsKey($componentsParams, ConfigurationConstraint::$AVAILABLE_MANDATORY_PC_COMPONENTS_IDS);

        // TODO: We have to check if each value which comes has valid type and itself valid

        $compatiblePcComponents = $this->componentService->getCompatibleComponents($validComponentsIds);

        return $this->json($compatiblePcComponents);
    }

}
