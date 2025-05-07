<?php

namespace App\Controller;

use App\Service\ForumSectionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ForumController extends AbstractController
{

    private ForumSectionService $forumSectionService;

    public function __construct(ForumSectionService $forumSectionService)

    {
        $this->forumSectionService = $forumSectionService;
    }

    #[Route('/forum/topic/{topicId}', name: 'forum_topic_details')]
    public function forum(int $topicId): Response
    {

        $topic = $this->forumSectionService->getTopicDetails($topicId);

        return $this->render('pages/forum_page/forum_user_topic_details.html.twig', [
            'topic' => $topic
        ]);
    }

    #[Route('/forum/topic/comment/{topicId}', name: 'forum.topic.comment_add')]
    public function topicComment(int $topicId, Request $request): Response
    {

    }
}