<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\ForumSectionService;
use App\Service\PCConfiguratorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    private ForumSectionService $forumSectionService;

    private PCConfiguratorService $pcConfiguratorService;

    /**
     * @param ForumSectionService $forumSectionService
     */
    public function __construct(ForumSectionService $forumSectionService
                                , PCConfiguratorService $pcConfiguratorService)
    {
        $this->forumSectionService = $forumSectionService;

        $this->pcConfiguratorService = $pcConfiguratorService;
    }


    #[Route('/', name: 'home' , methods: ['GET'])]
    public function index(): Response
    {
        $specificConfigurations = $this->pcConfiguratorService->getPcConfigurations(0,0, [103,109,111]); // We pass directly configurations IDS to display the best configurations on home page.

/*        echo '<pre>';
        print_r($specificConfigurations);
        echo '</pre>';*/

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