<?php

namespace App\Service\Impl;

use App\Entity\CompletedConfiguration;
use App\Entity\PCConfigComponent;
use App\Repository\CompletedConfigurationRepository;
use App\Repository\ComponentImageRepository;
use App\Repository\ComponentRepository;
use App\Service\PCConfiguratorService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

class PcConfiguratorServiceImpl implements PCConfiguratorService
{
    private CompletedConfigurationRepository $completedConfigurationRepository;

    private ComponentRepository $componentRepository;

    private ComponentImageRepository $componentImageRepository;

    private EntityManagerInterface $entityManager;

    /**
     * @param CompletedConfigurationRepository $completedConfigurationRepository
     */
    public function __construct(CompletedConfigurationRepository $completedConfigurationRepository
                                , ComponentRepository $componentRepository
                                , ComponentImageRepository $componentImageRepository
                                , EntityManagerInterface $entityManager)
    {
        $this->completedConfigurationRepository = $completedConfigurationRepository;
        $this->componentRepository = $componentRepository;
        $this->componentImageRepository = $componentImageRepository;
        $this->entityManager = $entityManager;
    }


    public function savePcConfiguration(array $componentsValues): CompletedConfiguration
    {
        $userPcConfiguration = new CompletedConfiguration();

        $userPcConfiguration->setName($componentsValues['name']);

        $currentDate = DateTime::createFromFormat('Y-m-d H:i:s', (new \DateTime())->format('Y-m-d H:i:s'));

        $userPcConfiguration->setCreatedAt($currentDate);

        $userPcConfiguration->setTotalWattage($componentsValues['power_wattage']);

        $userPcConfiguration->setLowestPrice((int)$componentsValues['lowestPrice']);

        $userPcConfiguration->setHighestPrice((int)$componentsValues['highestPrice']);

        $this->entityManager->persist($userPcConfiguration);
        $this->entityManager->flush();

        $componentTypes = ['cpu', 'gpu', 'ram', 'motherboard', 'storage', 'psu', 'pc_case'];

        // store left components
        foreach ($componentTypes as $componentType) {

            if(!empty($componentsValues[$componentType])){

                $configComponent = new PCConfigComponent();

                $component = $this->componentRepository->findComponentById((int)$componentsValues[$componentType]);

                $configComponent->setConfiguration($userPcConfiguration);
                $configComponent->setComponent($component);

                $this->entityManager->persist($configComponent);
            }
        }

        $this->entityManager->flush();

        return $userPcConfiguration;
    }

    public function getPcConfigurations(int $limit, int $offset): array
    {
        return $this->completedConfigurationRepository->getAllPcConfigurations($limit, $offset);
    }

    public function getPcConfigurationById(int $configurationId): array
    {
        $resultArray = $this->completedConfigurationRepository->getPcConfigurationById($configurationId);

        $filteredResultArray = array_filter($resultArray, function ($item) {

            return !is_null($item['component_id']);
        });

        $result = [];

        foreach ($filteredResultArray as $item) {

            $componentData = [
                'component_id'   => $item['component_id'],
                'component_name' => $item['component_name']
            ];

            if($item['component_type'] === 'pc_case'){

                $imageUrl = $this->componentImageRepository->getImageUrlByComponentId($item['component_id']);

                $componentData['image_url'] = $imageUrl;
            }

            if(!isset($result[$item['component_type']])){

                $result[$item['component_type']] = $componentData;
            }
        }

        return $result;

    }

    public function getPcConfigurationDetails(int $configurationId): CompletedConfiguration
    {
        return $this->completedConfigurationRepository->getPcConfigurationObjectById($configurationId);
    }

    public function getTotalsCountConfigurations(): int
    {
        return $this->completedConfigurationRepository->getTotalsCountConfigurations();
    }
}