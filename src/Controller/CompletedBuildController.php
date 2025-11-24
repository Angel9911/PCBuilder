<?php

declare(strict_types=1);

namespace App\Controller;

use App\Constraints\CacheConstraints;
use App\Constraints\ConfigurationConstraint;
use App\Private_lib\redis\RedisWrapper;
use App\Service\PCConfiguratorService;
use App\utils\ObjectMapper;
use App\utils\ProductCache;
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

        // NEW CACHE LOGIC
        $configIds = ProductCache::getConfigIdsFromCache($this->redis, "user");

        if($configIds === null) {

            $getConfigIds = $this->configuratorService->getPcConfigurationsIds();

            $configIds = ProductCache::setConfigIdsCache($this->redis, "user", $getConfigIds);
        }

        $currentConfigIdsPage = ProductCache::getPageIds($configIds, $page, $limit);

        $cachedPcConfigs = ProductCache::getConfigCachedCards($this->redis, "user", $currentConfigIdsPage);

        $missing = array_values(array_diff($currentConfigIdsPage, array_keys($cachedPcConfigs)));

        if(!empty($missing)) {

            // Fetch from database and cache the result
            $result = $this->configuratorService->getPcConfigurations($limit, $offset, $missing);

            ProductCache::putConfigCacheCards($this->redis, "user", $result);

            // Refresh cached page cards (now everything should be present)
            $cachedPcConfigs = ProductCache::getConfigCachedCards($this->redis, "user", $currentConfigIdsPage);
        }

        $totalCount = $this->configuratorService->getTotalsCountConfigurations(); // create this method

        $totalPages = ceil($totalCount / $limit);

        return $this->render('pages/completed_configuration_page/completed_configuration.html.twig' ,
        [
            'configurations' => $cachedPcConfigs,
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

        $pcConfiguration = ProductCache::getConfigCacheDetails($this->redis, "user", $buildId);

        if(empty($pcConfiguration)) {

            $pcConfiguration = $this->configuratorService->getPcConfigurationById($buildId);

            if(!empty($pcConfiguration)) {

                ProductCache::putConfigCacheCards($this->redis, "user", [$pcConfiguration]);
            }
        }

        if($pcConfiguration['name'] !== null){

            $pcConfigurationName = $pcConfiguration['name'];
        }

        if ($pcConfiguration['createdAt'] instanceof \DateTimeInterface) {

            $pcConfigurationCreatedAt = $pcConfiguration['createdAt']->format('Y-m-d');
        }

        if($pcConfiguration['lowestPrice'] !== null && (int)$pcConfiguration['lowestPrice'] > 0
            && $pcConfiguration['highestPrice'] !== null && (int)$pcConfiguration['highestPrice'] > 0){

            $pcConfigurationLowestPrice = (int)$pcConfiguration['lowestPrice'];

            $pcConfigurationHighestPrice = (int)$pcConfiguration['highestPrice'];
        }


        if($pcConfiguration['totalWattage'] !== null && (int)$pcConfiguration['totalWattage'] > 0){

            $pcConfigurationTotalWattage = (int)$pcConfiguration['totalWattage'];
        }

        return $this->render('pages/completed_configuration_page/completed_configuration_info.html.twig', [
            'configuration_id' => $pcConfiguration['id'],
            'configuration_name' => $pcConfigurationName ?? '',
            'configuration_date' => $pcConfigurationCreatedAt ?? '',
            'configuration_lowest_price' => $pcConfigurationLowestPrice ?? 0,
            'configuration_highest_price' => $pcConfigurationHighestPrice ?? 0,
            'configuration_total_wattage' => $pcConfigurationTotalWattage ?? 0,
            'configuration_rating' => $pcConfiguration['rating'],
            'cpu' => $pcConfiguration['components']['cpu']['name'],
            'motherboard' => $pcConfiguration['components']['motherboard']['name'],
            'psu' => $pcConfiguration['components']['psu']['name'],
            'gpu' => $pcConfiguration['components']['gpu']['name'],
            'ram' => $pcConfiguration['components']['ram']['name'],
            'storage' => $pcConfiguration['components']['storage']['name'],
            'pc_case' => $pcConfiguration['components']['pc_case'],
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

        $rateProductData = ObjectMapper::mapJsonToObject($request->getContent());

        $this->configuratorService->ratePcConfiguration($rateProductData['rating_config'], $rateProductData['user']);

        $configId = (int) $rateProductData['rating_config']['pc_config_id'];

        $cacheConfig = ProductCache::getConfigCacheDetails($this->redis, "user", $configId);

        $configRating = $this->configuratorService->getPcConfigurationRating($configId);

        if(!empty($configRating)) {

            $cacheConfig['rating'] = $configRating;

            ProductCache::putConfigCacheCards($this->redis, "user", [$cacheConfig]);
        }

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
