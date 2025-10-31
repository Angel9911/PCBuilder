<?php

declare(strict_types=1);

namespace App\Controller;

use App\Constraints\CacheConstraints;
use App\Constraints\ConfigurationConstraint;
use App\Private_lib\redis\RedisWrapper;
use App\Service\PCConfiguratorService;
use App\utils\ObjectMapper;
use App\utils\ValidatorUtils;
use Doctrine\ORM\Cache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Predis\Client;
class CompletedBuildController extends AbstractController
{
    private PCConfiguratorService $configuratorService;

    private RedisWrapper $redis;

    /**
     * @param PCConfiguratorService $configuratorService
     * @param RedisWrapper $redis
     */
    public function __construct(PCConfiguratorService $configuratorService
                                , RedisWrapper $redis)
    {
        $this->configuratorService = $configuratorService;
        $this->redis = $redis;
    }


    #[Route('/completed/build', name: 'completed.build')]
    public function completed(Request $request): Response
    {

        $page = max(1, (int) $request->get('page', 1));

        $limit = 8;

        $offset = ($page - 1) * $limit;

        $configurationsPageKey = CacheConstraints::$COMPLETED_PC_CONFIGURATION_KEY . "_page_" . $page;

        // Check if data exists in Redis cache
        if ($this->redis->isKeyExist($configurationsPageKey)) {

            $result = $this->redis->get($configurationsPageKey);

        } else {

            // Fetch from database and cache the result
            $result = $this->configuratorService->getPcConfigurations($limit, $offset);

            $this->redis->set($configurationsPageKey, $result, 3600); // Cache for 1 hour
        }

        /*echo '<pre>';
        print_r($result);
        echo '</pre>';*/

        $totalCount = $this->configuratorService->getTotalsCountConfigurations(); // create this method

        $totalPages = ceil($totalCount / $limit);

        return $this->render('pages/completed_configuration_page/completed_configuration.html.twig' ,
        [
            'configurations' => $result,
            'totalPages' => $totalPages,
            'currentPage' => $page,
        ]);
    }

    #[Route('/completed/build/details/{buildId}', name: 'completed.build.details')]
    public function completedBuildDetails($buildId): Response
    {
        if(!ValidatorUtils::validateAsNumber($buildId)){

            return $this->json([
                'error' => 'Invalid ID. It must be a positive number.'
            ], 400);
        }

        $buildId = (int) $buildId;

        $cacheKey = CacheConstraints::$PC_CONFIGURATION_KEY. '_' .$buildId;
        $this->redis->delete($cacheKey);
        if($this->redis->isKeyExist($cacheKey)) {

            $pcConfiguration = $this->redis->get($cacheKey);
            //$result = $this->redis->get($cacheKey);
        } else {

            $pcConfiguration = $this->configuratorService->getPcConfigurationById($buildId);
            //$result = $this->configuratorService->getPcConfigurationById($buildId);

            $this->redis->set($cacheKey, $pcConfiguration, 3600);
        }

        //$pcConfiguration = $this->configuratorService->getPcConfigurationDetails($buildId);

        if($pcConfiguration[0]['name'] !== null){

            $pcConfigurationName = $pcConfiguration[0]['name'];
        }

        if ($pcConfiguration[0]['createdAt'] instanceof \DateTimeInterface) {

            $pcConfigurationCreatedAt = $pcConfiguration[0]['createdAt']->format('Y-m-d');
        }

        if($pcConfiguration[0]['lowestPrice'] !== null && (int)$pcConfiguration[0]['lowestPrice'] > 0
            && $pcConfiguration[0]['highestPrice'] !== null && (int)$pcConfiguration[0]['highestPrice'] > 0){

            $pcConfigurationLowestPrice = (int)$pcConfiguration[0]['lowestPrice'];

            $pcConfigurationHighestPrice = (int)$pcConfiguration[0]['highestPrice'];
        }


        if($pcConfiguration[0]['totalWattage'] !== null && (int)$pcConfiguration[0]['totalWattage'] > 0){

            $pcConfigurationTotalWattage = (int)$pcConfiguration[0]['totalWattage'];
        }


        return $this->render('pages/completed_configuration_page/completed_configuration_info.html.twig', [
            'configuration_id' => $pcConfiguration[0]['id'],
            'configuration_name' => $pcConfigurationName ?? '',
            'configuration_date' => $pcConfigurationCreatedAt ?? '',
            'configuration_lowest_price' => $pcConfigurationLowestPrice ?? 0,
            'configuration_highest_price' => $pcConfigurationHighestPrice ?? 0,
            'configuration_total_wattage' => $pcConfigurationTotalWattage ?? 0,
            'cpu' => $pcConfiguration[0]['components']['cpu']['name'],
            'motherboard' => $pcConfiguration[0]['components']['motherboard']['name'],
            'psu' => $pcConfiguration[0]['components']['psu']['name'],
            'gpu' => $pcConfiguration[0]['components']['gpu']['name'],
            'ram' => $pcConfiguration[0]['components']['ram']['name'],
            'storage' => $pcConfiguration[0]['components']['storage']['name'],
            'pc_case' => $pcConfiguration[0]['components']['pc_case'],
        ]);
    }

    #[Route('/completed/build/{buildId}', name: 'get.completed.build')]
    public function completedBuild($buildId, Request $request): Response
    {
        if(!ValidatorUtils::validateAsNumber($buildId)){

            return $this->json([
                'error' => 'Invalid ID. It must be a positive number.'
            ], 400);
        }

        $buildId = (int) $buildId;

        $session = $request->getSession();

        $cacheKey = CacheConstraints::$PC_CONFIGURATION_KEY . '_' . $buildId;

        // Check if the configuration exists in cache
        if ($this->redis->isKeyExist($cacheKey)) {

            $result = $this->redis->get($cacheKey);
        } else {

            // Fetch from database and store in cache
            $result = $this->configuratorService->getPcConfigurationById($buildId);

            $this->redis->set($cacheKey, $result, 3600); // Cache for 1 hour
        }


        $session->set('pc_configuration', $result);
        $session->set('isAiConfiguration', false);

        return $this->redirectToRoute('configurator.build');
    }

    #[Route('/completed/build/rate', name: 'get.completed.build')]
    public function rateBuild(Request $request): Response
    {
/*        {
            "rating_product": {
              "product": "intel-core-i7-13700k",
                "stairs": 4,
                "comment": ""
              },
              "user": {
                "name": "test",
                "email": "email"
              }
        }*/

        $rateProductData = ObjectMapper::mapJsonToObject($request->getContent());

        $this->configuratorService->ratePcConfiguration($rateProductData['rating_product'], $rateProductData['user']);

        return $this->json(['message' => 'You have successfully rated the configuration.']);
    }

    #[Route('/completed/ai/build', name: 'completed.build.ai', methods: ['POST'])]
    public function generateAiRecommendedPcConfigurations(Request $request): Response
    {
        $isUserRequirements = ObjectMapper::mapJsonToObject($request->getContent());

        if(!isset($isUserRequirements['user_requirements'])) {

            return $this->json([
                'error' => 'Invalid request.'
            ], 400);
        }

        $userRequirements = $isUserRequirements['user_requirements'];

        // Validate required keys
        $validUserRequirementsFields = ValidatorUtils::validateAsKey($userRequirements, array_keys(ConfigurationConstraint::$AI_FINDER_SECTION_REQUIREMENTS));
        $missingFields = array_diff(array_keys(ConfigurationConstraint::$AI_FINDER_SECTION_REQUIREMENTS), array_keys($validUserRequirementsFields));

        if(!empty($missingFields)) {

            return $this->json([
                'error' => 'Invalid fields.',
                'fields' => implode(', ', $missingFields)
            ], 400);
        }

/*        if(!in_array($userRequirements['budget_focused'], array_values(ConfigurationConstraint::$AI_FINDER_SECTION_REQUIREMENTS['budget_focused']))
            || in_array($userRequirements['primary_use'], array_values(ConfigurationConstraint::$AI_FINDER_SECTION_REQUIREMENTS['primary_use']))
            || strlen($userRequirements['specific_requirement']) > 500) {

            return $this->json([
                'error' => 'Invalid field value.',
                'field' => '// POINT THE FIELD WHICH IS NOT VALID. THEY COULD BE MULTIPLE',
            ]);
        }*/

        $recommendedPcConfigurations = $this->configuratorService->getAiRecommendedConfigurations($userRequirements);

        // Render the recommendations section Twig template
        $aiBlockHtml = $this->renderView('pages/shared/recommendations_section.html.twig', [
            'title'      => 'AI Recommended Configurations',
            'subtitle'   => 'Top matches based on your preferences',
            'items'      => $recommendedPcConfigurations,
            'type'       => 'config',
            'main_image' => '',
            'periphery_type_icons' => '',
            'user_query' => $userRequirements['specific_requirement'] ?? '',
        ]);

        return $this->json([
            'config_html' => $aiBlockHtml
        ]);
        //return $this->json($recommendedPcConfigurations);
    }


}
