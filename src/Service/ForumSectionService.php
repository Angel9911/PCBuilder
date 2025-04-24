<?php

namespace App\Service;

interface ForumSectionService
{
    public function getAllForumSectionsAndSubsections();

    public function getTopicDetails(int $topicId);
}