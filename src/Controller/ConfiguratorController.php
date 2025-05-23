<?php

declare(strict_types=1);

namespace App\Controller;

use App\Constraints\CacheConstraints;
use App\Constraints\ConfigurationConstraint;
use App\Entity\CompletedConfiguration;
use App\Entity\Component;
use App\Form\PcConfigurationForm;
use App\Private_lib\redis\RedisWrapper;
use App\Service\ComponentService;
use App\Service\Impl\ComponentServiceImpl;
use App\Service\OpenAIService;
use App\Service\PCConfiguratorService;
use App\Service\VendorScraperService;
use App\utils\ObjectMapper;
use App\utils\ValidatorUtils;
use Doctrine\Common\Collections\Collection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Routing\Attribute\Route;

class ConfiguratorController extends AbstractController
{
    private OpenAIService $openAIService;

    private ComponentService $componentService;

    private VendorScraperService $vendorScraperService;

    private PCConfiguratorService $configuratorService;

    private RedisWrapper $redis;

    public function __construct(OpenAIService $openAIService
                    , ComponentService $componentService
                    , VendorScraperService $scraperService
                    , PCConfiguratorService $configuratorService
                    , RedisWrapper $redis)
    {
        $this->openAIService = $openAIService;
        $this->componentService = $componentService;
        $this->vendorScraperService = $scraperService;
        $this->configuratorService = $configuratorService;
        $this->redis = $redis;
    }

    #[Route('/configurator/build', name: 'configurator.build', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $session = $request->getSession();

        // Retrieve stored AI-generated recommendation and user answers
        $pcConfiguration = $session->get('pc_configuration', []);

        $explanation = $session->get('explanation');

        $isAiConfiguration= (bool)$session->get('isAiConfiguration');

        $componentsData = $this->populateComponentsFields();


        return $this->render('pages/pc_configurator_page/pc_build_configuration.html.twig', [
            'pcConfiguration' => $pcConfiguration,
            'isAiConfiguration' => $isAiConfiguration,
            'explanation' => $explanation,
            'selected_components_count' => empty($pcConfiguration) ? 0 : count($pcConfiguration),
            'max_components_count' => 9,
            'cpus' => $componentsData['cpus'],
            'motherboards' => $componentsData['motherboards'],
            'psus' => $componentsData['psus'],
            'gpus' => $componentsData['gpus'],
            'rams' => $componentsData['rams'],
            'storages' => $componentsData['storages'],
            'pc_cases' => $componentsData['pc_cases'],
            'monitors' => $componentsData['monitors']
        ]);
    }

    #[Route('/configurator/build/{buildId}', name: 'configurator', methods: ['GET'])]
    public function configureBuildId(array $pc, Request $request): Response
    {

    }

    #[Route('/configurator/ai', name: 'configurator.ai', methods: ['POST'])]
    public function generateAIRecommendation(Request $request): Response
    {

        $userRequirements = ObjectMapper::mapJsonToObject($request->getContent());

        $recommendationAi = $this->openAIService->generateRecommendedPcConfiguration($userRequirements);

        // Store the recommendation in the session and redirect
        $session = $request->getSession();
        $session->set('pc_configuration', $recommendationAi['components']);
        $session->set('explanation', $recommendationAi['explanation']);
        $session->set('isAiConfiguration', true);

        return $this->redirectToRoute('configurator.build');
    }

    #[Route('/configurator/bottleneck', name: 'configurator.bottleneck', methods: ['POST'])]
    public function calculateAIBottleneck(Request $request): Response
    {
        $bottleneckComponents = ObjectMapper::mapJsonToObject($request->getContent());

        if(empty($bottleneckComponents)){

            return $this->json([
                'error' => 'Invalid or empty payload',
            ], 400);
        }

        $validBottleneckComponents = ValidatorUtils::validateAsKey($bottleneckComponents, ConfigurationConstraint::$BOTTLENECK_REQUIRED_PC_COMPONENTS);

        $missingRequiredFields = array_diff(ConfigurationConstraint::$BOTTLENECK_REQUIRED_PC_COMPONENTS, array_keys($validBottleneckComponents));

        if(!empty($missingRequiredFields)){

            return $this->json([
                'error' => 'Missing required fields',
                'fields' => implode(', ', $missingRequiredFields)
            ], 400);
        }

        $invalidComponentTypes = array_filter(
            $validBottleneckComponents,
            function ($value, $key) {
                return ValidatorUtils::validateAsString($value);
            },
            ARRAY_FILTER_USE_BOTH
        );

        $missingValidFields = array_diff(ConfigurationConstraint::$BOTTLENECK_REQUIRED_PC_COMPONENTS, array_keys($invalidComponentTypes));

        if(!empty($missingValidFields)){

            return $this->json([
                'error' => 'Invalid component values',
                'fields' => implode(', ', $missingValidFields)
            ], 400);
        }

        $bottleneckCalculation = $this->openAIService->calculateBottleneckConfiguration($validBottleneckComponents);

        return $this->json($bottleneckCalculation);
    }

    #[Route('/configurator/manual', name: 'configurator.manual', methods: ['GET'])]
    public function manualPcConfiguration(Request $request): Response
    {
        // Clear session values related to AI-generated configuration
        $session = $request->getSession();
        $session->set('pc_configuration', []);
        $session->set('explanation',[]);// Remove explanation

        return $this->redirectToRoute('configurator.build');
    }

    #[Route('/configurator/component/offer/template', name: 'configurator.get_offer_template', methods: ['GET'])]
    public function getComponentOfferTemplate(): Response
    {
        return $this->render('pages/pc_configurator_page/pc_config_templates/offer-template.html.twig');
    }

    #[Route('/configurator/component/offers/{componentId}', name: 'configurator.get_offers', methods: ['GET'])]
    public function getComponentOffers($componentId): Response
    {

        if(!ValidatorUtils::validateAsNumber($componentId)){

            return $this->json([
                'error' => 'Invalid ID. It must be a positive number.',
            ], 400);
        }

        $componentId = (int) $componentId;

        $cacheKey = CacheConstraints::$OFFERS_COMPONENT_KEY . '_' . $componentId;

        if($this->redis->isKeyExist($cacheKey)){

            $result = $this->redis->get($cacheKey);//json_decode($this->redis->get($cacheKey));
        } else {

            $result = $this->vendorScraperService->getVendorOffersByComponent($componentId);

            $this->redis->set($cacheKey, $result, 10800);
        }

        return $this->json($result);
    }

    #[Route('/configurator/save', name: 'configurator.save', methods: ['POST'])]
    public function saveConfiguration(Request $request): Response
    {

        $componentsParams = ObjectMapper::mapJsonToObject($request->getContent());

        if(empty($componentsParams)){

            return $this->json([
                'error' => 'Invalid or empty payload',
            ], 400);
        }

        $validComponents = ValidatorUtils::validateAsKey($componentsParams, ConfigurationConstraint::$AVAILABLE_MANDATORY_PC_COMPONENTS);

        $missingFields = array_diff(ConfigurationConstraint::$AVAILABLE_MANDATORY_PC_COMPONENTS, array_keys($validComponents));

        if(!empty($missingFields)){

            return $this->json([
                'error' => 'Missing required fields',
                'fields' =>  implode(', ', $missingFields),
            ], 400);
        }

        if(isset($componentsParams['name'])){

            $isConfigurationNameValid = ValidatorUtils::validateAsString($componentsParams['name']);
        } else{

            return $this->json([
                'error' => 'PC Configuration name is missing',
                'fields' => 'name',
            ], 400);
        }

        if(!$isConfigurationNameValid){

            return $this->json([
                'error' => 'Invalid pc configuration name. Name must be a string',
                'fields' => 'name',
            ], 400);
        }

        $isComponentValsValid = ValidatorUtils::validateAsFieldType(
            $validComponents
            , ConfigurationConstraint::$AVAILABLE_MANDATORY_PC_COMPONENTS
            , 'number'
        );

        if(!empty($isComponentValsValid)){

            return $this->json([
                'error' => 'Invalid data types. There must be integers',
                'fields' => implode(', ', $isComponentValsValid),
            ], 400);
        }

        $limit = 8;
        $offset = 0;

        $cacheKey = CacheConstraints::$COMPLETED_PC_CONFIGURATION_KEY . '_page_1';

        if ($this->redis->isKeyExist($cacheKey)) {

            $cachedConfigurations = $this->redis->get($cacheKey);

            if (!is_array($cachedConfigurations)) {

                $cachedConfigurations = [];
            }
        } else {

            $cachedConfigurations = $this->configuratorService->getPcConfigurations($limit, $offset);
        }

        // merge name of configuration which components after make validation
        $validComponents = array_merge(
            ['name' => $componentsParams['name']],
            $validComponents
        );

        $newConfiguration = $this->configuratorService->savePcConfiguration($validComponents);

        $newConfigurationComponents = $this->configuratorService->getPcConfigurationById($newConfiguration->getId());

        $cachedConfigurations[$newConfiguration->getId()] = [
            'id' => $newConfiguration->getId(),
            'name' => $newConfiguration->getName(),
            'totalWattage' => $newConfiguration->getTotalWattage(),
        ];

        // Добави и компонентите
        foreach ($newConfigurationComponents as $type => $data) {
            $cachedConfigurations[$newConfiguration->getId()][$type] = $data;
        }
        // Save the updated array back to Redis
        $this->addNewConfigurationPageCache($cachedConfigurations);
        //$this->redis->set($cacheKey, $cachedConfigurations, 3600); // Cache for 1 hour

        return $this->json('Configuration saved successfully');
    }

    protected function addNewConfigurationPageCache(array $configuration, int $limit = 8): void
    {
        $page = 1;

        $exceedConfiguration = [$configuration];


        while (!empty($exceedConfiguration)) {

            $pageKey = CacheConstraints::$COMPLETED_PC_CONFIGURATION_KEY . '_page_' . $page;

            $cached = $this->redis->get($pageKey);

            if(!is_array($cached)){

                $cached = [];
            }

            $mergedConfigurations = array_merge($exceedConfiguration, $cached);

            if(count($mergedConfigurations) > $limit){

                $exceedConfiguration = array_splice($mergedConfigurations, $limit);
            } else {

                $exceedConfiguration = [];
            }

            $this->redis->set($pageKey, $mergedConfigurations, 3600); // the merged array consists 8 configurations

            $page++;
        }
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
            'pc_cases' => $this->componentService->getComponentsByType('pc_case'),
            'monitors' => $this->componentService->getComponentsByType('monitor')
        ];
    }
}
