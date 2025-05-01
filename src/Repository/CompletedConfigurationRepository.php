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

    private function findPcConfigurations(int $limit = 8, int $offset = 0): array
    {
        $resultArray = $this->createQueryBuilder('config')
            ->select('config.id', 'config.name', 'config.totalWattage', 'config.createdAt')
            ->orderBy('config.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getArrayResult();

        return $resultArray;
    }

    private function findComponentsByPcConfigurations(array $pcIds): array
    {
        $resultArray = $this->createQueryBuilder('config')
            ->leftJoin('config.components', 'pcComp')
            ->leftJoin('pcComp.component', 'component')
            ->leftJoin('component.type', 'ct')
            ->select('config.id AS config_id', 'component.id AS component_id', 'component.name AS component_name','ct.name AS component_type')
            ->where('config.id IN (:ids)')
            ->setParameter('ids', $pcIds)
            ->getQuery()
            ->getArrayResult();

        return $resultArray;
    }

    public function getAllPcConfigurations(int $limit, int $offset): array
    {
        $configs = $this->findPcConfigurations($limit, $offset);
        $configIds = array_column($configs, 'id');

        $components = $this->findComponentsByPcConfigurations($configIds);

        // Initialize map
        $final = [];
        foreach ($configs as $conf) {
            $final[$conf['id']] = $conf;
            $final[$conf['id']]['components'] = [];
        }

        // Merge components into corresponding config
        foreach ($components as $comp) {

            $final[$comp['config_id']]['components'][$comp['component_type']][] = [
                'component_id' => $comp['component_id'],
                'component_name' => $comp['component_name'],
            ];
        }

        return array_values($final);
    }

    public function getTotalsCountConfigurations(): int
    {
        return (int) $this->createQueryBuilder('config')
            ->select('COUNT(DISTINCT config.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}