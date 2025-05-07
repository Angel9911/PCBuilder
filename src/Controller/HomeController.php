<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\ForumSectionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    private ForumSectionService $forumSectionService;

    /**
     * @param ForumSectionService $forumSectionService
     */
    public function __construct(ForumSectionService $forumSectionService)
    {
        $this->forumSectionService = $forumSectionService;
    }


    #[Route('/', name: 'home' , methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('pages/home_page.html.twig');
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