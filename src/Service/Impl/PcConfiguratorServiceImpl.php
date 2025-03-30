<?php

namespace App\Service\Impl;

use App\Entity\CompletedConfiguration;
use App\Entity\PCConfigComponent;
use App\Repository\CompletedConfigurationRepository;
use App\Repository\ComponentRepository;
use App\Service\PCConfiguratorService;
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

        $cpuComponent = $this->componentRepository->findComponentById((int)$componentsValues['cpu']);
        $gpuComponent = $this->componentRepository->findComponentById((int)$componentsValues['gpu']);

        $cpuWattage = (int)$cpuComponent->getPowerWattage();
        $gpuWattage = (int)$gpuComponent->getPowerWattage();

        $totalWattage = $cpuWattage + $gpuWattage;

        $userPcConfiguration->setTotalWattage($totalWattage);

        $this->entityManager->persist($userPcConfiguration);
        $this->entityManager->flush();

        $componentTypes = ['ram', 'motherboard', 'storage', 'psu'];

        // insert the component which already get
        $configComponent = new PCConfigComponent();
        //insertion for cpu
        $configComponent->setConfiguration($userPcConfiguration);
        $configComponent->setComponent($cpuComponent);

        $this->entityManager->persist($configComponent);

        //insertion for gpu
        $configComponent = new PCConfigComponent();

        $configComponent->setConfiguration($userPcConfiguration);
        $configComponent->setComponent($gpuComponent);

        $this->entityManager->persist($configComponent);

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

    public function getPcConfigurations(): array
    {
        return $this->completedConfigurationRepository->getAllPcConfigurations();
    }

    public function getPcConfigurationById(int $configurationId): array
    {
        return $this->completedConfigurationRepository->getPcConfigurationById($configurationId);
    }
}