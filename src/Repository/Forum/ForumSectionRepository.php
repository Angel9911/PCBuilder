<?php

namespace App\Repository\Forum;

use App\Entity\Forum\ForumSection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class ForumSectionRepository extends ServiceEntityRepository
{
    private EntityManagerInterface $entityManager;

    /**
     * @param ManagerRegistry $registry
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(ManagerRegistry $registry, EntityManagerInterface $entityManager)
    {
        parent::__construct($registry,ForumSection::class);
        $this->entityManager = $entityManager;
    }

    /**
     * @return array
     */
    public function findAllSections(): array
    {
       return $this->createQueryBuilder('se')
            ->leftJoin('se.subsections', 'subsec')
            ->leftJoin('subsec.topics', 'topic')
            ->leftJoin('topic.user', 'user')
            ->leftJoin('user.account', 'account')
            ->leftJoin('topic.comments', 'comments')
            ->addSelect('subsec', 'topic', 'user')
           // ->addSelect('COUNT(comments.id) AS commentsCount')
           // ->groupBy('se.id, subsec.id, topic.id, user.id')
            ->getQuery()
            ->getResult();
    }
}