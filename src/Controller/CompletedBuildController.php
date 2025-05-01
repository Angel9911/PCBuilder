<?php

declare(strict_types=1);

namespace App\Controller;

use App\Constraints\CacheConstraints;
use App\Private_lib\redis\RedisWrapper;
use App\Service\PCConfiguratorService;
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

        $this->redis->delete($configurationsPageKey);
        // Check if data exists in Redis cache
        if ($this->redis->isKeyExist($configurationsPageKey)) {

            $result = $this->redis->get($configurationsPageKey);

        } else {

            // Fetch from database and cache the result
            $result = $this->configuratorService->getPcConfigurations($limit, $offset);

            $this->redis->set($configurationsPageKey, $result, 3600); // Cache for 1 hour
        }

        $totalCount = $this->configuratorService->getTotalsCountConfigurations(); // create this method

        $totalPages = ceil($totalCount / $limit);

        return $this->render('pages/completed_configuration.html.twig' ,
        [
            'configurations' => $result,
            'totalPages' => $totalPages,
            'currentPage' => $page,
        ]);
    }

    #[Route('/completed/build/details/{buildId}', name: 'completed.build.details')]
    public function completedBuildDetails(int $buildId, Request $request): Response
    {

        $cacheKey = CacheConstraints::$PC_CONFIGURATION_KEY. '_' .$buildId;

        if($this->redis->isKeyExist($cacheKey)) {

            $result = $this->redis->get($cacheKey);
        } else {

            $result = $this->configuratorService->getPcConfigurationById($buildId);

            $this->redis->set($cacheKey, $result, 3600);
        }

        $pcConfiguration = $this->configuratorService->getPcConfigurationDetails($buildId);

        if($pcConfiguration->getName() !== null){

            $pcConfigurationName = $pcConfiguration->getName();
        }

        if($pcConfiguration->getCreatedAt() !== null){

            $pcConfigurationCreatedAt = $pcConfiguration->getCreatedAt();
        }

        return $this->render('pages/completed_configuration_info.html.twig', [
            'configuration_id' => $pcConfiguration->getId(),
            'configuration_name' => $pcConfigurationName ?? '',
            'configuration_date' => isset($pcConfigurationCreatedAt) ? $pcConfigurationCreatedAt->format('Y-m-d') : '',
            'cpu' => $result['cpu']['name'],
            'motherboard' => $result['motherboard']['name'],
            'psu' => $result['psu']['name'],
            'gpu' => $result['gpu']['name'],
            'ram' => $result['ram']['name'],
            'storage' => $result['storage']['name'],
        ]);
    }

    #[Route('/completed/build/{buildId}', name: 'get.completed.build')]
    public function completedBuild(int $buildId, Request $request): Response
    {

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

}
