<?php

namespace App\Service\Impl;

use App\Entity\Forum\ForumTopic;
use App\Repository\Forum\ForumSectionRepository;
use App\Repository\Forum\ForumTopicRepository;
use App\Service\ForumSectionService;

class ForumSectionServiceImpl implements ForumSectionService
{
    // TODO: Will be good to rename the class because this class will handle with whole forum logic
    private ForumSectionRepository $forumSectionRepository;

    private ForumTopicRepository $forumTopicRepository;

    /**
     * @param ForumSectionRepository $forumSectionRepository
     * @param ForumTopicRepository $forumTopicRepository
     */
    public function __construct(ForumSectionRepository $forumSectionRepository
                                ,ForumTopicRepository $forumTopicRepository )
    {
        $this->forumSectionRepository = $forumSectionRepository;
        $this->forumTopicRepository = $forumTopicRepository;
    }


    /**
     * @return array
     */
    public function getAllForumSectionsAndSubsections(): array
    {
        return $this->forumSectionRepository->findAllSections();
    }

    /**
     * @param int $topicId
     * @return ForumTopic|null
     */
    public function getTopicDetails(int $topicId): ?ForumTopic
    {
        return $this->forumTopicRepository->findTopicById($topicId);
    }
}