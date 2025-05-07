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

        if(!empty($componentsParams)) {

            $validComponentsIds = ValidatorUtils::filterValidKeys($componentsParams, ConfigurationConstraint::$AVAILABLE_MANDATORY_PC_COMPONENTS_IDS);

            // Find invalid keys (difference between provided and allowed)
            $missingFields = array_diff(array_keys($componentsParams), ConfigurationConstraint::$AVAILABLE_MANDATORY_PC_COMPONENTS_IDS);

            if (!empty($missingFields)) {

                return $this->json([
                    'error' => 'Invalid components',
                    'fields' => implode(', ', $missingFields),
                ], 400);
            }

            $invalidComponentTypes = array_filter(
                $validComponentsIds,
                function ($value, $key) {
                    return !ValidatorUtils::validateAsNumber($value);
                },
                ARRAY_FILTER_USE_BOTH
            );

            if(!empty($invalidComponentTypes)) {

                return $this->json([
                    'error' => 'Invalid component values',
                    'fields' => implode(', ', $invalidComponentTypes),
                ], 400);
            }

            $compatiblePcComponents = $this->componentService->getCompatibleComponents($validComponentsIds);

            if (!empty($compatiblePcComponents)) {

                return $this->json($compatiblePcComponents);
            }
        }

        $compatiblePcComponents = $this->componentService->getCompatibleComponents($componentsParams);

        return $this->json($compatiblePcComponents);
    }

    protected function populateComponentsFields(): array
    {

        return [
            'cpus' => $this->componentService->getComponentsByType('cpu'),
            'motherboards' => $this->componentService->getComponentsByType('motherboard'),
            'psus' => $this->componentService->getComponentsByType('psu'),
            'gpus' => $this->componentService->getComponentsByType('gpu'),
            'rams' => $this->componentService->getComponentsByType('ram'),
            'storages' => $this->componentService->getComponentsByType('storage'),
            'pc_cases' => $this->componentService->getComponentsByType('pc_case')
        ];
    }

}
