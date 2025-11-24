<?php

declare(strict_types=1);

namespace App\Controller;

use App\Private_lib\redis\RedisWrapper;
use App\Service\ForumSectionService;
use App\Service\PCConfiguratorService;
use App\utils\ProductCache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    private ForumSectionService $forumSectionService;

    private PCConfiguratorService $pcConfiguratorService;
    private RedisWrapper $redis;
    /**
     * @param ForumSectionService $forumSectionService
     */
    public function __construct(ForumSectionService $forumSectionService
                                , PCConfiguratorService $pcConfiguratorService
                                , RedisWrapper $redis)
    {
        $this->forumSectionService = $forumSectionService;

        $this->pcConfiguratorService = $pcConfiguratorService;

        $this->redis = $redis;
    }


    #[Route('/', name: 'home' , methods: ['GET'])]
    public function index(): Response
    {

        $specificConfigurations = ProductCache::getConfigCachedCards($this->redis, "user", [103,109,111]);

        if(empty($specificConfigurations)){

            $specificConfigurations = $this->pcConfiguratorService->getPcConfigurations(0,0, [103,109,111]); // We pass directly configurations IDS to display the best configurations on home page.

            ProductCache::putConfigCacheCards($this->redis, "user", $specificConfigurations);
        }
        
        return $this->render('pages/home_page.html.twig', [
            'configurations' => $specificConfigurations,
        ]);
    }
    #[Route('/forum', name: 'forum' , methods: ['GET'])]
    public function forum(): Response
    {
        return $this->render('pages/forum_page/forum_page.html.twig', [
            'sections' => $this->forumSectionService->getAllForumSectionsAndSubsections()
        ]);
    }
    #[Route('/test', name: 'test' , methods: ['GET'])]
    public function test(): Response
    {
        return $this->render('pages/test.html.twig', [
            'sections' => $this->forumSectionService->getAllForumSectionsAndSubsections()
        ]);
    }
}