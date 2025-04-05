<?php

namespace App\Repository;

use App\Entity\CompletedConfiguration;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CompletedConfigurationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CompletedConfiguration::class);
    }

    public function saveConfiguration(CompletedConfiguration $config): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($config);
        $entityManager->flush();
    }

    public function deleteConfiguration(CompletedConfiguration $config): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($config);
        $entityManager->flush();
    }


    /**
     * @param int $id
     * @return CompletedConfiguration
     */
    public function getPcConfigurationObjectById(int $id): CompletedConfiguration
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param int $pcId
     * @return array
     */
    public function getPcConfigurationById(int $pcId): array
    {
        $resultArray = $this->createQueryBuilder('config')
            ->leftJoin('config.components', 'pcComp')
            ->leftJoin('pcComp.component', 'component')
            ->leftJoin('component.type', 'ct')// Join the Component entity
            ->select( 'ct.name AS component_type','component.id AS component_id', 'component.name AS component_name')
            ->andWhere('config.id = :id')
            ->setParameter('id', $pcId)
            ->getQuery()
            ->getResult();

        $filteredResultArray = array_filter($resultArray, function ($item) {

            return !is_null($item['component_id']);
        });

        $result = [];

        foreach ($filteredResultArray as $item) {

            if(!isset($result[$item['component_type']])){

                $result[$item['component_type']] = [
                    'name' => $item['component_name']
                ];
            }
        }

        return $result;
    }

    public function getAllPcConfigurations(): array
    {
        $resultArray = $this->createQueryBuilder('config')
            ->leftJoin('config.components', 'pcComp')
            ->leftJoin('pcComp.component', 'component')
            ->leftJoin('component.type', 'ct')// Join the Component entity
            ->select('config.id', 'config.name', 'config.totalWattage', 'config.createdAt', 'ct.name AS component_type','component.id AS component_id', 'component.name AS component_name')
            ->getQuery()
            ->getResult();

        $filteredResultArray = array_filter($resultArray, function ($item) {

            return !is_null($item['component_id']);
        });

        $result = [];
        foreach ($filteredResultArray as $item) {

            if(!isset($result[$item['id']])) {

                $result[$item['id']] = [
                  'id' => $item['id'],
                  'name' => $item['name'],
                  'totalWattage' => $item['totalWattage'],
                  'createdAt' => $item['createdAt'],
                ];
            }

            if(!isset($result[$item['id']]['component_type']['components'])) {

                $result[$item['id']][$item['component_type']]['components'][] = [
                    'component_id' => $item['component_id'],
                    'component_name' => $item['component_name'],
                ];
            }
        }

        return $result;
    }
}