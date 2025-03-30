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
    public function completed(): Response
    {
        $this->redis->delete(CacheConstraints::$COMPLETED_PC_CONFIGURATION_KEY);
        // Check if data exists in Redis cache
        if ($this->redis->isKeyExist(CacheConstraints::$COMPLETED_PC_CONFIGURATION_KEY)) {

            $result = $this->redis->get(CacheConstraints::$COMPLETED_PC_CONFIGURATION_KEY);

        } else {

            // Fetch from database and cache the result
            $result = $this->configuratorService->getPcConfigurations();
            $this->redis->set(CacheConstraints::$COMPLETED_PC_CONFIGURATION_KEY, $result, 3600); // Cache for 1 hour
        }

        return $this->render('pages/completed_configuration.html.twig' ,
        [
            'configurations' => $result
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
