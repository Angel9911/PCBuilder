<?php

namespace App\Service\Impl;

use App\Constraints\ConfigurationConstraint;
use App\Entity\CompletedConfiguration;
use App\Entity\CompletedConfigurationRating;
use App\Entity\PCConfigComponent;
use App\Entity\User\User;
use App\Entity\User\UserAccount;
use App\Private_lib\helpers\UserHelper;
use App\Repository\CompletedConfigRatingRepository;
use App\Repository\CompletedConfigurationRepository;
use App\Repository\ComponentImageRepository;
use App\Repository\ComponentRepository;
use App\Repository\UserRepository;
use App\Repository\UserRoleRepository;
use App\Service\OpenAIService;
use App\Service\PCConfiguratorService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

class PcConfiguratorServiceImpl implements PCConfiguratorService
{
    private static int $OFFSET = 0;
    private static int $LIMIT = 0;

    private CompletedConfigurationRepository $completedConfigurationRepository;

    private CompletedConfigRatingRepository $completedConfigRatingRepository;

    private ComponentRepository $componentRepository;

    private UserRepository $userRepository;

    private UserRoleRepository $roleRepository;

    private ComponentImageRepository $componentImageRepository;

    private OpenAIService $openAIService;

    private UserHelper $userHelper;

    private EntityManagerInterface $entityManager;

    /**
     * @param CompletedConfigurationRepository $completedConfigurationRepository
     */
    public function __construct(CompletedConfigurationRepository $completedConfigurationRepository
        , CompletedConfigRatingRepository                           $completedConfigRatingRepository
        , ComponentRepository                                    $componentRepository
        , ComponentImageRepository                               $componentImageRepository
        , UserRepository                                         $userRepository
        , UserRoleRepository                                     $roleRepository
        , OpenAIService                                          $openAIService
        , UserHelper                                             $userHelper
        , EntityManagerInterface                                 $entityManager)
    {
        $this->completedConfigurationRepository = $completedConfigurationRepository;
        $this->completedConfigRatingRepository = $completedConfigRatingRepository;
        $this->componentRepository = $componentRepository;
        $this->componentImageRepository = $componentImageRepository;
        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
        $this->openAIService = $openAIService;
        $this->userHelper = $userHelper;
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

            if (!empty($componentsValues[$componentType])) {

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

    public function getPcConfigurations(int $limit, int $offset, array $specificConfigurations = null): array
    {
        return $this->completedConfigurationRepository->getAllPcConfigurations($limit, $offset, $specificConfigurations);
    }

    public function getPcConfigurationById(int $configurationId): array
    {
        return $this->completedConfigurationRepository->getAllPcConfigurations(self::$LIMIT, self::$OFFSET, [$configurationId]);
    }

    public function getPcConfigurationDetails(int $configurationId): CompletedConfiguration
    {
        return $this->completedConfigurationRepository->getPcConfigurationObjectById($configurationId);
    }

    public function getTotalsCountConfigurations(): int
    {
        return $this->completedConfigurationRepository->getTotalsCountConfigurations();
    }

    public function getAiRecommendedConfigurations(array $userRequirements): array
    {
        $pcConfigurations = $this->completedConfigurationRepository->getAllPcConfigurations();

        $formatAvailableConfigurations = $this->formatPcConfigurations($pcConfigurations);

        $recommendedConfigurationsData = $this->openAIService->generateRecommendedPcConfigurations($formatAvailableConfigurations, $userRequirements);

        $formatAiRecommendationsConfigs = array_column($recommendedConfigurationsData['recommended_builds'], null, 'id');

        $recommendedConfigurationsComponents = $this->completedConfigurationRepository->getAllPcConfigurations(self::$LIMIT, self::$OFFSET, array_keys($formatAiRecommendationsConfigs));

        foreach ($recommendedConfigurationsComponents as &$recommendedConfig) {

            $id = $recommendedConfig['id'];

            if (isset($formatAiRecommendationsConfigs[$id])) {

                $recommendedConfig['ai_recommendation'] = $formatAiRecommendationsConfigs[$id];
            }
        }

        unset($recommendedConfig); // best practice when using reference in foreach

        return $recommendedConfigurationsComponents;
    }

    public function ratePcConfiguration(array $configRating, array $userData): void
    {
        $user = $this->userHelper->getOrCreateAnonymousUser(
            $userData['name'],
            $userData['email']
        );

        $pcConfigRating = new CompletedConfigurationRating($configRating['pc_config_id'], $configRating['stairs']);

        $pcConfigRating->setUser($user);

        $pcConfigRating->setReview($configRating['comment']);

        $this->completedConfigRatingRepository->savePcConfigRating($pcConfigRating);
    }

    public function getPcConfigurationRating(): array
    {
        // TODO: Implement getPcConfigurationRating() method.
    }

    private function formatPcConfigurations(array $configurations): array
    {
        $formatConfigurations = [];

        foreach ($configurations as $configuration) {

            $configurationParts = [];

            foreach (ConfigurationConstraint::$AVAILABLE_MANDATORY_PC_COMPONENTS as $componentKey => $componentLabel){

                if(!empty($configuration['components'][$componentKey]['name'])){

                    $configurationParts[] = "{$componentLabel}=" . $configuration['components'][$componentKey]['name'];
                }
            }

            $formatConfigurations[$configuration['id']] = $configuration['id'] . ':' . implode(';', $configurationParts);

            $configurationParts[$configuration['id']] = "Lowest Price=" . $configuration['lowestPrice'];
            $configurationParts[$configuration['id']] = "Highest Price=" . $configuration['highestPrice'];
            $configurationParts[$configuration['id']] = "Total Wattage=" . $configuration['totalWattage'];

        }

        return $formatConfigurations;
    }
}