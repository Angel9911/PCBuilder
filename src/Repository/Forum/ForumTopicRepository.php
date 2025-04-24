<?php

namespace App\Repository\Forum;

use App\Entity\Forum\ForumTopic;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class ForumTopicRepository extends ServiceEntityRepository
{
    private EntityManagerInterface $entityManager;

    public function __construct(ManagerRegistry $registry, EntityManagerInterface $entityManager){

        parent::__construct($registry, ForumTopic::class);

        $this->entityManager = $entityManager;

    }

    public function findTopicById(int $id): ?ForumTopic
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
}