<?php

namespace App\Service\Impl;

use App\Entity\CompletedConfiguration;
use App\Entity\PCConfigComponent;
use App\Repository\CompletedConfigurationRepository;
use App\Repository\ComponentRepository;
use App\Service\PCConfiguratorService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

class PcConfiguratorServiceImpl implements PCConfiguratorService
{
    private CompletedConfigurationRepository $completedConfigurationRepository;

    private ComponentRepository $componentRepository;

    private EntityManagerInterface $entityManager;

    /**
     * @param CompletedConfigurationRepository $completedConfigurationRepository
     */
    public function __construct(CompletedConfigurationRepository $completedConfigurationRepository
                                , ComponentRepository $componentRepository
                                , EntityManagerInterface $entityManager)
    {
        $this->completedConfigurationRepository = $completedConfigurationRepository;
        $this->componentRepository = $componentRepository;
        $this->entityManager = $entityManager;
    }


    public function savePcConfiguration(array $componentsValues): CompletedConfiguration
    {
        $userPcConfiguration = new CompletedConfiguration();

        $userPcConfiguration->setName($componentsValues['name']);

        $currentDate = DateTime::createFromFormat('Y-m-d H:i:s', (new \DateTime())->format('Y-m-d H:i:s'));

        $userPcConfiguration->setCreatedAt($currentDate);

        $userPcConfiguration->setTotalWattage($componentsValues['power_wattage']);

        $userPcConfiguration->setLowestPrice((int)$componentsValues['lowest_price']);

        $userPcConfiguration->setHighestPrice((int)$componentsValues['highest_price']);

        $this->entityManager->persist($userPcConfiguration);
        $this->entityManager->flush();

        $componentTypes = ['cpu', 'gpu', 'ram', 'motherboard', 'storage', 'psu'];

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
        return $this->completedConfigurationRepository->getPcConfigurationById($configurationId);
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